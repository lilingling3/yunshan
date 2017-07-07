<?php
/**
 * 第三方站点管理
 *
 * Created by PhpStorm.
 * User: Ma
 * Date: 17/4/26
 * Time: 上午11:51
 */

namespace Auto\Bundle\ManagerBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Auto\Bundle\ManagerBundle\Entity\RentalStation;
use Auto\Bundle\ManagerBundle\Entity\PartnerStation;


class PartnerCommand extends ContainerAwareCommand
{

    const SEPARATOR = '|';// 缓存数据分割符

    public function configure()
    {
        $this
            ->setName('auto:partner')
            ->setDescription('save partner station to our db')
            ->addOption('partnerCode', 'c', InputOption::VALUE_REQUIRED, 'partner code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $partnerCode = $input->getOption('partnerCode');

        if (empty($partnerCode)) {
            echo "Please input partner code ...\n";
            exit;
        }

        $man = $this->getContainer()->get('doctrine')->getManager();

        $partner = $man->getRepository('AutoManagerBundle:Partner')
            ->findOneBy(['code' => $partnerCode]);

        if (empty($partner)) {
            echo 'No partner code is ' . $partnerCode . "\n";
            exit;
        }

        $stations = $this->getStationsFromPartner($partnerCode);

        if ($stations) {
            $this->savePartnerStations($partner, $stations);
        } else {
            echo "Got none stations exit ...\n";
        }
    }

    /**
     * 从第三方获取租赁点
     *
     * @param $partnerCode
     * @return array|null
     */
    private function getStationsFromPartner($partnerCode)
    {
        echo "Get data from partner " . $partnerCode . "\n";
        $stations = $this->getContainer()->get('auto_manager.partner_helper')->getStations($partnerCode);
        $stations = json_decode($stations, true);
        if (isset($stations['code']) && $stations['code'] == '200' && isset($stations['data']) && count($stations['data']) > 0) {

            $reData = [];
            foreach ($stations['data'] as $station) {
                $reData[$station['id']] = $station;
            }

            return $reData;
        }
        return null;
    }


    /**
     * 新加、更新第三方租赁点，更新数据不进行比对 直接根据id覆盖
     *
     * @param $partner
     * @param $stations
     */
    public function savePartnerStations($partner, $stations)
    {
        $partnerCode = $partner->getCode();
        $this->getContainer()->get('auto_manager.cache_helper')->cachePartnerStations($partnerCode);
        echo "Got " . count($stations) . ' stations, begin save to db ...' . "\n";

        $redis = $this->getContainer()->get('snc_redis.default');

        $redis_cmd = $redis->createCommand('HGETALL', ['station_id_to_partner_station_id']);
        $existStationIds = $redis->executeCommand($redis_cmd);

        $editStationIds = [];
        $removedStationIds = [];

        foreach ($existStationIds as $existStation) {
            $existStation = explode(self::SEPARATOR, $existStation);
            if ($partnerCode == $existStation[2]) {
                $editStationIds[$existStation[3]] = $existStation[0];
                if (!isset($stations[$existStation[0]])) {
                    $removedStationIds[$existStation[3]] = $existStation[0];
                }
            }
        }

        $newStationIds = array_diff(array_keys($stations), $editStationIds);

        echo 'all:' . implode(',', array_keys($stations)) . "\n";
        echo 'new:' . implode(',', $newStationIds) . "\n";
        echo 'edt:' . implode(',', $editStationIds) . "\n";
        echo 'del:' . implode(',', $removedStationIds) . "\n";


        $this->editPartnerStations($partner, $stations, $editStationIds, $removedStationIds);

        if (count($newStationIds) > 0)// @todo change -1 to 0
        {
            $this->addPartnerStations($partner, $stations, $newStationIds);
        }

        $this->getContainer()->get('auto_manager.cache_helper')->cachePartnerStations($partnerCode, true);

        echo "Saved over...\n";
    }

    /**
     * 添加第三方租赁点
     *
     * @param $partner
     * @param $stations
     * @param $newStationIds
     */
    private function addPartnerStations(&$partner, $stations, $newStationIds)
    {
        $man = $this->getContainer()
            ->get('doctrine')
            ->getManager();

        $areas = $man
            ->getRepository('AutoManagerBundle:Area')
            ->findAll();

        $company = $man
            ->getRepository('AutoManagerBundle:Company')
            ->findOneBy(['englishName' => $partner->getName()]);

        foreach ($stations as $station) {
            if (in_array($station['id'], $newStationIds)) {

                $area = $this
                    ->getContainer()
                    ->get('auto_manager.station_helper')
                    ->getNearestArea($station['lat'], $station['lon'], $areas);

                if ($area == null) {
                    continue;
                }

                $dbStation = new RentalStation();
                $dbStation->setCompany($company);
                $dbStation->setParkingSpaceTotal(1000);
                $dbStation->setUsableParkingSpace(1000);
                $dbStation->setImages([]);
                $dbStation->setBackType(RentalStation::DIFFERENT_PLACE_BACK);
                $mobile = empty($company) ? '' : $company->getContactMobile();
                $dbStation->setContactMobile($mobile);


                $dbStation->setName($station['name']);
                $dbStation->setOnline(1);
                $dbStation->setArea($area);
                $dbStation->setStreet($station['detail']);
                $dbStation->setLatitude($station['lat']);
                $dbStation->setLongitude($station['lon']);
                $man->persist($dbStation);

                $dbPartnerStations = new PartnerStation();
                $dbPartnerStations->setPartner($partner);
                $dbPartnerStations->setPartnerStation($station['id']);
                $dbPartnerStations->setStation($dbStation);
                $dbPartnerStations->setStatus(1);
                $man->persist($dbPartnerStations);
            }
        }
        $man->flush();
    }

    /**
     * 更新第三方租赁点，更新数据不进行比对 直接根据id覆盖
     *
     * @param $partner
     * @param $stations
     * @param $editStationIds
     * @param $removedStationIds
     */
    private function editPartnerStations(&$partner, $stations, $editStationIds, $removedStationIds)
    {
        $man = $this->getContainer()
            ->get('doctrine')
            ->getManager();

        $dbPartnerStations = $man->getRepository('AutoManagerBundle:PartnerStation')
            ->findBy(['partner' => $partner->getId(), 'partnerStation' => ($editStationIds)]);

        echo 'Those partner station need to change : ';
        foreach ($dbPartnerStations as $key => $station) {

            if (in_array($station->getPartnerStation(), ($removedStationIds))) {
                echo 'disable(del) station id :' . $station->getStation()->getId() . "\n";
                $dbPartnerStations[$key]->setStatus(0);
            } else {
                $dbPartnerStations[$key]->setStatus(1);
            }
            $man->persist($dbPartnerStations[$key]);
        }
        $man->flush();

        $dbStations = $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findBy(['id' => array_keys($editStationIds)]);

        foreach ($dbStations as $key => $station) {
            $partnerStationId = $editStationIds[$station->getId()];

            if (in_array($partnerStationId, $removedStationIds)) {
                $dbStations[$key]->setOnline(0);
            } else {
                $dbStations[$key]->setOnline(1);
                $dbStations[$key]->setName($stations[$partnerStationId]['name']);
                $dbStations[$key]->setStreet($stations[$partnerStationId]['detail']);
                $dbStations[$key]->setLatitude($stations[$partnerStationId]['lat']);
                $dbStations[$key]->setLongitude($stations[$partnerStationId]['lon']);
            }
            $man->persist($dbStations[$key]);
        }
        $man->flush();
    }
} 