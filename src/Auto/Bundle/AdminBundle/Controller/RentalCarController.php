<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/21
 * Time: 上午11:45
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ApiBundle\Controller\BaseController;
use Swoole\HttpServerBundle\Http\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Process\Process;
use Auto\Bundle\ManagerBundle\Form\RentalCarType;
use Auto\Bundle\ManagerBundle\Form\InspectionOneType;
use Auto\Bundle\ManagerBundle\Form\UpkeepOneType;


/**
 * @Route("/rentalcar")
 */
class RentalCarController extends BaseController
{

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rentalcar_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req, $page = 1)
    {
        $rentalStation = $req->query->get('rentalStation');
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');
        $online = $req->query->get('online');
        $carId = $req->query->get('carId');
        $partnerCode = $req->query->get('partnerCode');
        $companyId = $req->query->get('companyId');
        $province = empty($req->query->get('province')) ? '' : $req->query->get('province');
        $city = empty($req->query->getInt('city')) ? '' : $req->query->getInt('city');

        $licensePlaces = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();
        $companys = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->findBy(['kind' => 3]);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c')
                ->select('c');
        if ($partnerCode) {
            $partnerCarIds = $this->get('auto_manager.partner_helper')->getRentalCarIdsByPartnerCode($partnerCode);
            $qb
                ->andWhere('c.id in (:id)')
                ->setParameter('id', $partnerCarIds);
        }
        if ($licensePlace) {
            $qb
                ->andWhere($qb->expr()->eq('c.licensePlace', ':licensePlace'))
                ->setParameter('licensePlace', $licensePlace);

        }
        if ($plate_number) {
            $qb
                ->andWhere($qb->expr()->eq('c.licensePlate', ':licensePlate'))
                ->setParameter('licensePlate', $plate_number);

        }
        if ($rentalStation) {
            $qb
                ->join('c.rentalStation', 's')
                ->andWhere($qb->expr()->like('s.name', ':rentalStation'))
                ->setParameter('rentalStation', "%" . $rentalStation . "%");
        }
        if ($online) {
            $qb
                ->join('c.online', 'ol')
                ->andWhere($qb->expr()->eq('ol.status', ':online'))
                ->setParameter('online', $online);
        }
        if ($carId) {
            $qb
                ->andWhere($qb->expr()->eq('c.car', ':carId'))
                ->setParameter('carId', $carId);

        }
        if ($companyId) {
            $qb
                ->andWhere($qb->expr()->eq('c.company', ':companyId'))
                ->setParameter('companyId', $companyId);

        }

        if ($province !== '' && $province !== '请选择') {

            $distinctList = $this->get('auto_manager.area_helper')->getDistinctList($province, $city);

            $qb
                ->join('c.rentalStation', 't')
                ->andWhere($qb->expr()->in('t.area', ':area'))
                ->setParameter('area', $distinctList);
        }

        $rentalcars =
            new Paginator(
                $qb
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $mileage = [];
        $carStatus = [];
        $insurance = [];
        foreach ($rentalcars as $rentalcar) {
            if (!isset($mileage[$rentalcar->getId()])) {
                $mileage[$rentalcar->getId()] = 0;
            }
            if (!isset($carStatus[$rentalcar->getId()])) {
                $carStatus[$rentalcar->getId()] = 0;
            }
            if (!isset($insurance[$rentalcar->getId()])) {
                $insurance[$rentalcar->getId()] = 0;
            }
            $insuranceRecord =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:InsuranceRecord')
                    ->findBy(['rentalCar' => $rentalcar, 'insurance' => 1]);
            if (empty($insuranceRecord)) {
                $insurance[$rentalcar->getId()] = '缺失';
            } else {
                $insurance[$rentalcar->getId()] = '齐全';
            }

            $carStatus[$rentalcar->getId()] = $this->get('auto_manager.rental_car_helper')->get_rental_car_status($rentalcar);
            $mileage[$rentalcar->getId()] = $this->get('auto_manager.rental_car_helper')->get_rental_car_range($rentalcar, 5);
        }


        $total = ceil(count($rentalcars) / self::PER_PAGE);
        /*'rentalcars'=>array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
            $rentalcars->getIterator()->getArrayCopy()),*/
        return [
            'companys' => $companys, 'cars' => $cars,
            'mileage' => $mileage, 'insurance' => $insurance, 'rentalcars' => $rentalcars,
            'carStatus' => $carStatus, 'licensePlaces' => $licensePlaces, 'page' => $page, 'total' => $total,
            'rentalStation' => $rentalStation, 'companyId' => $companyId, 'online' => $online, 'rentalCarId' => $plate_number,
            'licensePlace' => $licensePlace, 'province' => $province, 'city' => $city, 'partnerCode' => $partnerCode
        ];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_rentalcar_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new RentalCarType(), null, [
            'action' => $this->generateUrl('auto_admin_rentalcar_create'),
            'method' => 'POST'
        ]);

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/record/{id}", methods="GET", name="auto_admin_rentalcar_record")
     * @Template()
     */
    public function recordAction($id)
    {
        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $boxId = $rental_car->getBoxId();

        if (empty($boxId)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message' => '没有安装设备!']
            );
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LRANGE', array($rental_car->getDeviceCompany()->getEnglishName() . '-mileage-' . $boxId, 0, 100));
        $mileage_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd = $redis->createCommand('LRANGE', array($rental_car->getDeviceCompany()->getEnglishName() . '-power-' . $boxId, 0, 100));
        $power_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd = $redis->createCommand('LRANGE', array($rental_car->getDeviceCompany()->getEnglishName() . '-range-' . $boxId, 0, 100));
        $range_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd = $redis->createCommand('LRANGE', array($rental_car->getDeviceCompany()->getEnglishName() . '-gps-' . $boxId, 0, 100));
        $gps_arr = $redis->executeCommand($redis_cmd);

        $mileage_list = [];
        $power_list = [];
        $range_list = [];
        $gps_list = [];
        foreach ($mileage_arr as $mileage) {
            $mileage_list[] = json_decode($mileage, true);
        }
        foreach ($power_arr as $power) {
            $power_list[] = json_decode($power, true);
        }
        foreach ($range_arr as $range) {
            $range_list[] = json_decode($range, true);
        }
        foreach ($gps_arr as $gps) {
            $gps_list[] = json_decode($gps, true);
        }

        return ['mileage_list' => $mileage_list, 'power_list' => $power_list, 'range_list' => $range_list, 'gps_list' => $gps_list, 'rentalCar' => $rental_car];

    }

    /**
     * @Route("/operateRecord/{id}", methods="GET", name="auto_admin_rentalcar_operateRecord")
     * @Template()
     */
    public function operateRecordAction($id)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                ->createQueryBuilder('o');
        $operateRecords =
            $qb
                ->select('o')
                ->orderBy('o.createTime', 'DESC')
                ->where($qb->expr()->eq('o.rentalCar', ':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(["id" => $id]);

        $records = array();
        foreach ($operateRecords as $value) {
            $reasonsIds = $value->getReason();
            $records[] = [
                "info" => $value,
                "reasonsIds" => $reasonsIds
            ];
        }
        $reasons = array(
            1 => "车辆外观已清洁",
            2 => "车辆轮胎完好",
            3 => "车辆内饰已清洁",
            4 => "保单复印件已有",
            5 => "车辆行驶本已有",
            6 => "车辆交强险标志存在",
            7 => "车辆年检标志存在",
            8 => "车辆备胎已有",
            9 => "车辆换胎工具已有",
            10 => "车辆充电线已有",
            11 => "车辆控制设备可用",
            12 => "设备故障",
            13 => "车辆充电",
            14 => "车辆故障/事故",
            15 => "调配车辆",
            16 => "用户还车",
            17 => "人工还车"
        );
//        $total = ceil(count($operateRecords) / self::PER_PAGE);

        return ['operateRecords' => $records, 'rentalCar' => $rentalCar, 'reasons' => $reasons, 'id' => $id];
    }

    /**
     * @Route("/electricRecord/{id}", methods="GET", name="auto_admin_rentalcar_electricRecord")
     * @Template()
     */
    public function electricRecordAction($id)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                ->createQueryBuilder('o');
        $operateRecords =
            $qb
                ->select('o')
                ->orderBy('o.createTime', 'DESC')
                ->where($qb->expr()->eq('o.rentalCar', ':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(["id" => $id]);

        $records = array();
        foreach ($operateRecords as $value) {
            $reasonsIds = $value->getReason();
            $records[] = [
                "info" => $value,
                "reasonsIds" => $reasonsIds
            ];
        }

        return ['operateRecords' => $records, 'rentalCar' => $rentalCar, 'id' => $id];
    }

    /**
     * @Route("/dispatchRecord/{id}", methods="GET", name="auto_admin_rentalcar_dispatchRecord")
     * @Template()
     */
    public function dispatchRecordAction($id)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DispatchRentalCar')
                ->createQueryBuilder('o');
        $dispatchRecords =
            $qb
                ->select('o')
                ->orderBy('o.createTime', 'DESC')
                ->where($qb->expr()->eq('o.rentalCar', ':id'))
                //->andWhere($qb->expr()->eq('o.kind',':kind'))
                ->setParameter('id', $id)
                //->setParameter('kind', 2)
                ->getQuery()
                ->getResult();
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(["id" => $id]);

        return ['dispatchRecords' => $dispatchRecords, 'rentalCar' => $rentalCar];
    }

    /**
     * @Route("/dispatch/{id}", methods="GET", name="auto_admin_rentalcar_dispatch",requirements={"id"="\d+"})
     * @Template()
     */
    public function dispatchAction(Request $req, $id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $rentalStations = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();
        return ['rentalCar' => $rentalCar, 'rentalStations' => $rentalStations];
    }

    /**
     * @Route("/dispatchCreate/{id}", methods="GET", name="auto_admin_rentalcar_dispatch_create",requirements={"id"="\d+"})
     * @Template()
     */
    public function dispatchCreateAction(Request $req, $id)
    {
        $rentalStationId = $req->query->get('rentalStation');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $rentalStations = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($rentalStationId);


        $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();

        $dispatch->setCreateTime(new \DateTime())
            ->setKind(1)
            ->setRentalCar($rentalCar)
            ->setRentalStation($rentalStation)
            ->setStatus(1)
            ->setOperateMember($this->getUser());
        $man = $this->getDoctrine()->getManager();
        $man->persist($dispatch);
        $man->flush();

        $rentalCar->setRentalStation($rentalStation);

        $man = $this->getDoctrine()->getManager();
        $man->persist($rentalCar);
        $man->flush();

        $carPartnerData = [
            'carId' => $id,
            'operator' => $this->getUser()->getId(),
            'reason' => '',
            'remark' => '',
            'status' => empty($rentalCar->getOnline()) ? 0 : $rentalCar->getOnline()->getStatus(),
            'stationId' => $rentalStationId,
        ];
        $this->get("auto_manager.partner_helper")->carOnOffLine($carPartnerData);


        return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));

        return ['rentalCar' => $rentalCar, 'rentalStations' => $rentalStations];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_rentalcar_create")
     * @Template("AutoAdminBundle:RentalCar:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalCar = new \Auto\Bundle\ManagerBundle\Entity\RentalCar();

        $form = $this->createForm(new RentalCarType(), $rentalCar, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $price = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalPrice')
                ->findOneBy(["car" => $rentalCar->getCar()]);

            if (empty($price)) {

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message' => '车辆没有定价!']
                );

            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalCar);
            $man->flush();

            $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
            $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::OPERATOR_DISPATCH_CAR_KIND);
            $dispatch->setRentalCar($rentalCar);
            $dispatch->setRentalStation($rentalCar->getRentalStation());
            $dispatch->setStatus(1);

            $man->persist($dispatch);
            $man->flush();


            return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));
        }
        return ['form' => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rentalcar_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $form = $this->createForm(new RentalCarType(), $rentalcar, [
            'action' => $this->generateUrl('auto_admin_rentalcar_update', ['id' => $rentalcar->getId()]),
            'method' => 'POST'
        ]);
        $form = $form->createView();
//dump($form);die;
//        dump($form->getImages());die;
        return [
            'form' => $form,
            'rentalcar' => $rentalcar
        ];
    }


    /**
     * @Route("/map", methods="GET", name="auto_admin_rentalcar_map")
     * @Template("AutoAdminBundle:RentalCar:map.html.twig")
     */
    public function mapAction(Request $req)
    {
        $latitude = $req->query->get('latitude');
        $longitude = $req->query->get('longitude');

        return $this->render(
            "AutoAdminBundle:RentalCar:map.html.twig",
            ['latitude' => $latitude, 'longitude' => $longitude]
        );

    }

    /**
     * @Route("/carinfo", methods="GET", name="auto_admin_car_info")
     *
     * @return string
     * @Template()
     */
    public function carInfoAction()
    {

        $license_places = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $companys = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->findBy(['kind' => 1]);

        return [
            'license_places' => $license_places, 'companys' => $companys
        ];
    }

    /**
     * @Route("/carId", methods="POST", name="auto_admin_get_rental_car_id")
     *
     * @param Request $req
     * @return string
     */
    public function getCarIdByPlate(Request $req)
    {
        $plate = $req->request->get('plate');
        $ids = $this->get('auto_manager.cache_helper')->getCarIDsByPlates([$plate]);
        if (!isset($ids[0])) {
            return new JsonResponse(['errorCode' => self::E_NO_RENTAL_CAR, 'errorMessage' => self::M_NO_RENTAL_CAR]);
        } else {
            return new JsonResponse(['errorCode' => self::E_OK, 'id' => $ids[0]]);
        }
    }

    /**
     * @Route("/carBaseInfo", methods="POST", name="auto_admin_rental_car_base_info")
     *
     * @param Request $req
     * @return string
     */
    public function getCarBaseInfoAction(Request $req)
    {
        $plate_id = $req->request->get('plate_id');
        $plate_number = $req->request->get('plate_number');
        $company_id = $req->request->get('company_id');
        $device_number = $req->request->get('device_number');

        $rental_car = null;

        if (isset($plate_id) && isset($plate_number) && $plate_id != null && $plate_number != null) {
            $rental_car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findOneBy(['licensePlace' => $plate_id, 'licensePlate' => $plate_number]);
        }

        if ($rental_car == null && isset($company_id) && isset($device_number) && $company_id != null && $device_number != null) {
            $rental_car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findOneBy(['deviceCompany' => $company_id, 'boxId' => $device_number]);
        }

        if (empty($rental_car)) {
            return new JsonResponse(['errorCode' => self::E_NO_RENTAL_CAR, 'errorMessage' => self::M_NO_RENTAL_CAR]);
        }

        $running_fields = [
            'location' => 1,
            'elevation' => 1,
            'speed' => 1,
            'direction' => 1,
            'distance' => 1,
            'surplusDistance' => 1,
            'surplusPercent' => 1,
            'light' => 1,
            'door' => 1,
            'voltage' => 1,
            'acc' => 1,
            'signal' => 1,
            'status' => 1,
        ];
        $operate_fields = [
            'open' => 1,
            'on' => 1,
            'find' => 1,
            'close' => 1,
            'off' => 1,
            'reset' => 1,
        ];

        switch ($rental_car->getDeviceCompany()->getEnglishName()) {
            case 'carStart':
                break;
            case 'baoJia':
                $operate_fields['reset'] = 0;
                break;
            case 'feeZu':
                $running_fields['elevation'] = 0;
                $running_fields['voltage'] = 0;
                $running_fields['acc'] = 0;
                $running_fields['door'] = 0;
                $running_fields['light'] = 0;
                $running_fields['signal'] = 0;
                $running_fields['status'] = 0;
                break;
            case 'cloudBox':
                break;
            case 'yunshanZhihui':
                $running_fields['voltage'] = 0;
                $running_fields['light'] = 0;
                $running_fields['signal'] = 0;
                $running_fields['status'] = 0;
                $running_fields['door'] = 0;
                break;
            case 'zhiXinTong':
                $running_fields['status'] = 0;
                $running_fields['signal'] = 0;
                break;
        }

        $carImage = '';
        if (isset($rental_car->getImages()[0])) {
            $carImage = $this->get('auto_manager.rental_car_helper')->get_rental_car_image($rental_car->getImages()[0]);
        }

        return new JsonResponse(['errorCode' => self::E_OK,
            'plate_place' => $rental_car->getLicensePlace()->getName(),
            'plate_number' => $rental_car->getLicensePlate(),
            'car_id' => $rental_car->getId(),
            'images' => $this->get('auto_manager.rental_car_helper')->get_rental_car_image($rental_car->getCar()->getImage()),
            'car_image' => $carImage,
            'models' => $rental_car->getCar()->getName(),
            'device_number' => $rental_car->getBoxId(),
            'device' => $rental_car->getDeviceCompany()->getName(),
            'order_status' => $this->get('auto_manager.rental_car_helper')->get_rental_car_status($rental_car),
            'running_fields' => $running_fields,
            'operate_fields' => $operate_fields
        ]);
    }

    /**
     * @Route("/carControl", methods="POST", name="auto_admin_rental_car_control")
     *
     * @param Request $req
     * @return string
     */
    public function carControlAction(Request $req)
    {
        $car_id = $req->request->get('car_id');
        $operate = $req->request->get('operate');

        if (empty($car_id) || empty($operate)) {
            return new JsonResponse(['errorCodes' => self::E_WRONG_PARAMETER, 'message' => self::M_WRONG_PARAMETER]);
        }

        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($car_id);

        if (empty($rental_car)) {
            return new JsonResponse(['errorCode' => self::E_NO_RENTAL_CAR, 'errorMessage' => self::M_NO_RENTAL_CAR]);
        }

        $timeOperateStart = $this->getMicroTime();
        $re = $this->get('auto_manager.rental_car_helper')->operate($rental_car, $operate, $this->getUser());
        $timeOperateEnd = $this->getMicroTime();

        return new JsonResponse(['errorCode' => self::E_OK, 'data' => $re, 'timeStart' => $timeOperateStart, 'timeEnd' => $timeOperateEnd]);
    }

    private function getMicroTime()
    {

        list($usec, $sec) = explode(" ", microtime());

        return (int)(($usec + $sec) * 1000);
    }

    private function cacheCarRunningData($key, $field, $data)
    {
        $max_count = 20;
        $redis = $this->container->get('snc_redis.default');
        $key = $this->getCarRunningDataCacheKey($key);

        $redis_cmd = $redis->createCommand('HGETALL', [$key]);
        $re = $redis->executeCommand($redis_cmd);
        ksort($re);
        $count = count($re);
        if ($count > $max_count) {
            foreach ($re as $k => $v) {
                $redis_cmd = $redis->createCommand('HDEL', array($key, $k));
                $redis->executeCommand($redis_cmd);
                if (--$count <= $max_count) {
                    break;
                }
            }
        }

        $redis_cmd = $redis->createCommand('HSET', array($key, $field, json_encode($data)));
        $redis->executeCommand($redis_cmd);
        $redis_cmd = $redis->createCommand('EXPIRE', [$key, 7200]);
        $redis->executeCommand($redis_cmd);

    }

    private function getCarRunningDataCacheKey($key)
    {
        return 'car_control_page_cache_' . $key;
    }

    private function getCarRunningDataFromCache($key, $field)
    {
        $redis = $this->container->get('snc_redis.default');
        $key = $this->getCarRunningDataCacheKey($key);
        $redis_cmd = $redis->createCommand('HGET', array($key, $field));
        $re = $redis->executeCommand($redis_cmd);
        return $re;
    }

    /**
     * @Route("/carRunningInfo", methods="POST", name="auto_admin_rental_car_running_info")
     *
     * @param Request $req
     * @return string
     */
    public function getCarRunningInfoAction(Request $req)
    {
        $car_id = $req->request->get('car_id');
        $time = $req->request->get('time');
        $cache_id = $req->request->get('cache_id');

        if (empty($car_id) || empty($time)) {
            return new JsonResponse(['errorCodes' => self::E_WRONG_PARAMETER, 'message' => self::M_WRONG_PARAMETER]);
        }

        $cacheData = $this->getCarRunningDataFromCache($cache_id, $time . '_' . $car_id);

        if (!isset($cacheData)) {
            $rental_car = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($car_id);

            if (empty($rental_car)) {
                return new JsonResponse(['errorCode' => self::E_NO_RENTAL_CAR, 'errorMessage' => self::M_NO_RENTAL_CAR]);
            }

            $car_data = null;
            $cacheData = array();
            switch ($rental_car->getDeviceCompany()->getEnglishName()) {
                case 'carStart':
                    break;
                case 'baoJia':
//                    $rental_car->setBoxId('028000003883');
                    $car_data = $this->get('auto_manager.bao_jia_helper')->findCarLocation($rental_car->getBoxId());
                    if ($car_data) {
                        $cacheData['latitude'] = isset($car_data['latitude']) ? substr($car_data['latitude'], 0, strlen($car_data['latitude']) - 6)
                            . '.' . substr($car_data['latitude'], -6) : '未获取到';
                        $cacheData['longitude'] = isset($car_data['latitude']) ? substr($car_data['longitude'], 0, strlen($car_data['longitude']) - 6)
                            . '.' . substr($car_data['longitude'], -6) : '未获取到';
                        $cacheData['elevation'] = isset($car_data['elevation']) ? $car_data['elevation'] . ' m' : '未获取到';
                        $cacheData['signal'] = isset($car_data['signals']) ? $car_data['signals'] . '' : '未获取到';
                        $cacheData['status'] = '通电';
                        $cacheData['distance'] = isset($car_data['distance']) ? $car_data['distance'] . ' km' : '未获取到';
                        $cacheData['direction'] = isset($car_data['direction']) ? $car_data['direction'] . '°' : '未获取到';
                        $cacheData['speed'] = isset($car_data['speed']) ? $car_data['speed'] / 10 . ' km/h' : '未获取到';
                        $cacheData['surplusDistance'] = isset($car_data['surplusDistance']) ? $car_data['surplusDistance'] . ' km' : '未获取到';
                        $cacheData['surplusPercent'] = isset($car_data['surplusPercent']) ? substr($car_data['surplusPercent'], 2) . '%' : '未获取到';
                        $cacheData['voltage'] = isset($car_data['voltage']) ? substr($car_data['voltage'], 0, -1) . '.' . substr($car_data['voltage'], -1) . 'V' : '未获取到';
                        $cacheData['time'] = isset($car_data['time']) ? substr($car_data['time'], 0, -3) : '未获取到';
                        $cacheData['acc'] = isset($car_data['status']) ? $car_data['status'][0] == 1 ? '开启' : '未开启' : '未获取到';
                        $cacheData['door'] = isset($car_data['status']) ? '未开启' : '未获取到';
                        if ($car_data['status'][13]) {
                            $cacheData['door'] = '开启';

                            $open_door = '';
                            if ($car_data['status'][14]) {
                                $open_door .= ':中门';
                            }
                            if ($car_data['status'][15]) {
                                if (empty($open_door)) {
                                    $open_door .= ':后门';
                                } else {
                                    $open_door .= ';后门';
                                }
                            }
                            if ($car_data['status'][16]) {
                                if (empty($open_door)) {
                                    $open_door .= ':驾驶席门';
                                } else {
                                    $open_door .= ';驾驶席门';
                                }
                            }
                            if ($car_data['status'][17]) {
                                if (empty($open_door)) {
                                    $open_door .= ':后备箱';
                                } else {
                                    $open_door .= ';后备箱';
                                }
                            }
                            $cacheData['door'] .= $open_door;
                        }
                        $cacheData['light'] = isset($car_data['status']) ? '未开启' : '未获取到';
                        if ($car_data['status'][26] || $car_data['status'][27] || $car_data['status'][28] || $car_data['status'][29] || $car_data['status'][30]) {
                            $cacheData['light'] .= '开启';

                            $open_door = '';
                            if ($car_data['status'][26]) {
                                $open_door .= ':左转灯';
                            }
                            if ($car_data['status'][27]) {
                                if (empty($open_door)) {
                                    $open_door .= ':右转灯';
                                } else {
                                    $open_door .= ';右转灯';
                                }
                            }
                            if ($car_data['status'][28]) {
                                if (empty($open_door)) {
                                    $open_door .= ':远光灯';
                                } else {
                                    $open_door .= ';远光灯';
                                }
                            }
                            if ($car_data['status'][29]) {
                                if (empty($open_door)) {
                                    $open_door .= ':近光灯';
                                } else {
                                    $open_door .= ';近光灯';
                                }
                            }
                            if ($car_data['status'][30]) {
                                if (empty($open_door)) {
                                    $open_door .= ':位置灯';
                                } else {
                                    $open_door .= ';位置灯';
                                }
                            }
                            $cacheData['light'] .= $open_door;
                        }
                    }
                    break;
                case 'feeZu':
//                    $rental_car->setBoxId('116232100003751');
                    $car_location = $this->get('auto_manager.fee_zu_helper')->findCarLocation($rental_car->getBoxId());
                    $car_status = $this->get('auto_manager.fee_zu_helper')->oneCarStatus($rental_car->getBoxId());
                    if ($car_location) {
                        $gps = $this->get('auto_manager.gps_helper')->gcj_encrypt($car_location['latitude'], $car_location['longitude']);
                        $cacheData['latitude'] = isset($gps['lat']) ? $gps['lat'] : '未获取到';
                        $cacheData['longitude'] = isset($gps['lon']) ? $gps['lon'] : '未获取到';

                        $cacheData['direction'] = isset($car_location['direction']) ? $car_location['direction'] . '°' : '未获取到';
                        $cacheData['speed'] = isset($car_location['speed']) ? $car_location['speed'] . ' km/h' : '未获取到';
                    }
                    if ($car_status) {
                        $cacheData['time'] = isset($car_status['reportTime']) ? strtotime($car_status['reportTime']) : '未获取到';
                        $cacheData['distance'] = isset($car_status['totalMileage']) ? $car_status['totalMileage'] . ' km' : '未获取到';
                        $cacheData['surplusDistance'] = isset($car_status['remainMileage']) ? $car_status['remainMileage'] . ' km' : '未获取到';
                        $cacheData['surplusPercent'] = isset($car_status['power']) ? $car_status['power'] . '%' : '未获取到';
                    }

                    break;
                case 'cloudBox':
                    break;
                case 'yunshanZhihui':
                    $car_location = $this->get('auto_manager.yunshan_zhihui_helper')->findCarLocation($rental_car->getLicensePlace()->getName() . $rental_car->getLicensePlate(), $rental_car->getBoxId());
                    $car_status = $this->get('auto_manager.yunshan_zhihui_helper')->findCarInformation($rental_car->getLicensePlace()->getName() . $rental_car->getLicensePlate(), $rental_car->getBoxId());

                    if ($car_location) {
                        $cacheData['latitude'] = isset($car_location['carPlate']) ? $car_location['lat'] : '未获取到';
                        $cacheData['longitude'] = isset($car_location['carPlate']) ? $car_location['lgt'] : '未获取到';
                        $cacheData['elevation'] = isset($car_location['carPlate']) ? $car_location['hgt'] : '未获取到';
                        $cacheData['direction'] = isset($car_location['carPlate']) ? $car_location['run_dir'] . '°' : '未获取到';
                        $cacheData['speed'] = isset($car_location['carPlate']) ? $car_location['speed'] . ' km/h' : '未获取到';
                        $cacheData['time'] = isset($car_location['carPlate']) ? $car_location['time'] : '未获取到';
                    }
                    if ($car_status) {
                        $cacheData['distance'] = isset($car_status['carPlate']) ? $car_status['total_mil'] . ' km' : '未获取到';
                        $cacheData['surplusDistance'] = isset($car_status['carPlate']) ? $car_status['rmn_mil'] . ' km' : '未获取到';
                        $cacheData['acc'] = isset($car_status['carPlate']) ? $car_status['acc'] == 2 ? '开启' : '未开启' : '未获取到';
                        $cacheData['surplusPercent'] = isset($car_status['carPlate']) ? $car_status['soc'] . '%' : '未获取到';
                    }
                    break;
                case 'zhiXinTong':
                    $car_location = $this->get('auto_manager.zhixin_tong_helper')->findCarLocation($rental_car->getBoxId());
                    $car_location = json_decode($car_location, true);
                    if (isset($car_location['cars'][0])) {
                        $car_location = $car_location['cars'][0];
                        $gps = $this->get('auto_manager.gps_helper')->gcj_encrypt($car_location['latitude'], $car_location['longitude']);
                    }
                    $carAltitude = $this->get('auto_manager.zhixin_tong_helper')->getCarAltitude($rental_car->getBoxId());
                    $carAltitude = json_decode($carAltitude, true);
                    if (isset($carAltitude['cars'][0])) {
                        $carAltitude = $carAltitude['cars'][0];
                    }
                    if ($car_location) {
                        $cacheData['latitude'] = isset($gps['lat']) ? $gps['lat'] : '未获取到';
                        $cacheData['longitude'] = isset($gps['lon']) ? $gps['lon'] : '未获取到';
                        $cacheData['acc'] = isset($car_location['on']) && $car_location['on'] == 1 ? '开启' : '未开启';
                        $cacheData['distance'] = isset($car_location['totalMileage']) ? $car_location['totalMileage'] . ' km' : '未获取到';
                        $cacheData['speed'] = isset($car_location['speed']) ? $car_location['speed'] / 10 . ' km/h' : '未获取到';
                        $cacheData['surplusDistance'] = isset($car_location['mileage']) ? $car_location['mileage'] . ' km' : '未获取到';
                        $cacheData['surplusPercent'] = isset($car_location['electricity']) ? explode('/', $car_location['electricity'])[0] . '%' : '未获取到';
                        $cacheData['voltage'] = isset($car_location['voltage']) ? $car_location['voltage'] . 'V' : '未获取到';
                        $cacheData['time'] = isset($car_location['receivedTime']) ? substr($car_location['receivedTime'], 0, -3) : '未获取到';
                        $cacheData['door'] = (isset($car_location['leftFrontDoor']) && $car_location['leftFrontDoor'] == '1') ||
                        isset($car_location['rightFrontDoor']) && $car_location['rightFrontDoor'] == '1' ||
                        isset($car_location['leftRearDoor']) && $car_location['leftRearDoor'] == '1' ||
                        isset($car_location['rightRearDoor']) && $car_location['rightRearDoor'] == '1' ? '开启' : '未开启';
                        $cacheData['light'] = isset($car_location['lightsStatus']) ? $car_location['lightsStatus'] == '1' ? '开启' : '未开启' : '未获取到';
                    }
                    if ($carAltitude) {
                        $cacheData['elevation'] = isset($carAltitude['altitude']) ? $carAltitude['altitude'] . ' m' : '未获取到';
                        $cacheData['direction'] = isset($carAltitude['direction']) ? ($carAltitude['direction'] * 2) . '°' : '未获取到';
                    }
                    break;

            }

            $this->cacheCarRunningData($cache_id, $time . '_' . $car_id, $cacheData);
        } else {
            $cacheData = json_decode($cacheData, true);
        }

        if (!empty($cacheData)) {
            return new JsonResponse(['errorCode' => self::E_OK, 'data' => $cacheData]);
        } else {
            return new JsonResponse(['errorCode' => -1, 'errorMessage' => '获取不到车辆数据']);
        }
    }

    /**
     * @Route("/info/{id}", methods="GET", name="auto_admin_rentalcar_info",requirements={"id"="\d+"})
     * @Template()
     */
    public function infoAction($id)
    {
        $rentalOrders = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findBy(['rentalCar' => $id], ['createTime' => 'desc']);

        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $status = $this->get('auto_manager.rental_car_helper')->get_rental_car_status($rentalcar);

        return [
            'rentalcar' => $rentalcar, 'rentalOrders' => $rentalOrders, 'userid' => $id, 'car_status' => $status
        ];
    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_rentalcar_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
//        $status = $this->get('auto_manager.rental_car_helper')->get_rental_car_status($rentalcar);
//        dump($rentalcar);die;
        return [
            'rentalcar' => $rentalcar
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_rentalcar_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RentalCar:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $form = $this->createForm(new RentalCarType(), $rentalCar, [
            'action' => $this->generateUrl('auto_admin_rentalcar_update', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
//编辑不需要移库记录。  mod by liyandong 20160705
//            $dispatch = new \Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar();
//            $dispatch->setKind(\Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar::OPERATOR_DISPATCH_CAR_KIND);
//            $dispatch->setRentalCar($rentalCar);
//            $dispatch->setRentalStation($rentalCar->getRentalStation());
//            $dispatch->setStatus(1);
//            $man->persist($dispatch);
//            $man->flush();

            $man->persist($rentalCar);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));
        }
        return ['form' => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_rentalcar_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $man->remove($rentalCar);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));
    }


    /**
     * @Route("/locate/{id}", methods="GET", name="auto_admin_rental_car_locate",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function locateAction($id)
    {

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        if (empty($rentalCar->getBoxId())) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message' => '没有安装设备!']
            );
        }
        $this->get("auto_manager.rental_car_helper")->operate($rentalCar, 'gps', $this->getUser());

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd = $redis->createCommand('LINDEX', array($rentalCar->getDeviceCompany()->getEnglishName() . '-gps-' . $rentalCar->getBoxId(), 0));
        $box_json = $redis->executeCommand($redis_cmd);

        $redis_cmd = $redis->createCommand('LRANGE', array($rentalCar->getDeviceCompany()->getEnglishName() . '-gps-' . $rentalCar->getBoxId(), 0, 20));
        $locations = $redis->executeCommand($redis_cmd);

        $gps_list = [];
        foreach ($locations as $gps) {
            $arr = [];
            $gps = $this->get('auto_manager.curl_helper')->object_array(json_decode($gps));
            $arr['coordinate'] = $gps['coordinate'];
            $arr['time'] = date('Y-m-d H:i:s', $gps['time']);
            $gps_list[] = $arr;
        }


        if (empty($box_json)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message' => '没有坐标数据!']
            );
        }
        $gps_arr = $this->get('auto_manager.curl_helper')->object_array(json_decode($box_json));

        return ['coordinate' => $gps_arr['coordinate'], 'time' => date('Y-m-d H:i:s', $gps_arr['time']), 'unusual' => (new \DateTime())->getTimestamp() - $gps_arr['time'] > 60 * 5 ? 1 : 0, 'locations' => $gps_list];


    }

    /**
     * @Route("/inspection/list/{id}/{page}", methods="GET", name="auto_admin_rentalcar_inspection_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function inspectionListAction($id, $page = 1)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Inspection')
                ->createQueryBuilder('i');
        $inspections =
            new Paginator(
                $qb
                    ->select('i')
                    ->where($qb->expr()->eq('i.rentalCar', ':rentalCar'))
                    ->setParameter(':rentalCar', $rentalCar)
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($inspections) / self::PER_PAGE);
        $arrResult = array();
        foreach ($inspections as $value) {
            $arrTemp = array();
            $arrTemp['id'] = $value->getId();
            $arrTemp['rentalCar'] = $value->getRentalCar();
            $arrTemp['inspectionTime'] = $value->getInspectionTime();
            $arrTemp['createTime'] = $value->getCreateTime();
            $arrTemp['nextInspectionTime'] = $value->getNextInspectionTime();
            $arrTemp['remark'] = $value->getRemark();
            $arrTemp['inspectionYear'] = $value->getInspectionYear();
            if ($value->getNextInspectionTime()->getTimestamp() >= strtotime(date('Y-m-d'))) {
                $arrTemp['overdueStatus'] = '否';
                $arrTemp['overdueDay'] = '-';
            } else {
                $arrTemp['overdueStatus'] = '是';
                $arrTemp['overdueDay'] = floor((time() - $value->getNextInspectionTime()->getTimestamp()) / (3600 * 24));
            }

            $arrResult[] = $arrTemp;
        }

        return ['inspections' => $arrResult, 'page' => $page, 'total' => $total, 'rentalCar' => $rentalCar];
    }


    /**
     * @Route("/inspection/new/{id}", methods="GET", name="auto_admin_rentalcar_inspection_new")
     * @Template()
     */
    public function inspectionNewAction($id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $form = $this->createForm(new InspectionOneType(), null, [
            'action' => $this->generateUrl('auto_admin_rentalcar_inspection_create', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        return ['form' => $form->createView(), 'rentalCar' => $rentalCar];
    }

    /**
     * @Route("/inspection/new/{id}", methods="POST", name="auto_admin_rentalcar_inspection_create")
     * @Template()
     */
    public function inspectionCreateAction(Request $req, $id)
    {
        $inspection = new \Auto\Bundle\ManagerBundle\Entity\Inspection();

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $form = $this->createForm(new InspectionOneType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_rentalcar_inspection_create', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $inspection->setRentalCar($rentalCar);
            $man = $this->getDoctrine()->getManager();
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_inspection_list', ['id' => $rentalCar->getId()]));
        }
        return ['form' => $form->createView()];
    }

    /**
     * @Route("/inspection/del/{id}", methods="GET", name="auto_admin_rentalcar_inspection_delete",requirements={"id"="\d+"})
     */
    public function inspectionDeleteAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $man = $this->getDoctrine()->getManager();
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $man->remove($inspection);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rentalcar_inspection_list', ['id' => $rentalCar->getId()]));
    }

    /**
     * @Route("/inspection/edit/{id}", methods="GET", name="auto_admin_rentalcar_inspection_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function inspectionEditAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $form = $this->createForm(new InspectionOneType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_rentalcar_inspection_update', ['id' => $inspection->getId(), 'rentalcarid' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form' => $form->createView(), 'rentalCar' => $rentalCar
        ];
    }


    /**
     * @Route("/inspection/edit/{id}", methods="POST", name="auto_admin_rentalcar_inspection_update",requirements={"id"="\d+"})
     * @Template("")
     */
    public function inspectionUpdateAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $form = $this->createForm(new InspectionOneType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_rentalcar_inspection_update', ['id' => $inspection->getId(), 'rentalcarid' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_inspection_list', ['id' => $rentalCar->getId()]));
        }
        return ['form' => $form->createView()];
    }

//-------------------------------单车  保养-----------------------------------------

    /**
     * @Route("/upkeep/list/{id}/{page}", methods="GET", name="auto_admin_rentalcar_upkeep_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function upkeepListAction($id, $page = 1)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
//        $mileageCurrent = $this->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($rentalCar);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Upkeep')
                ->createQueryBuilder('i');
        $upkeeps =
            new Paginator(
                $qb
                    ->select('i')
                    ->where($qb->expr()->eq('i.rentalCar', ':rentalCar'))
                    ->setParameter(':rentalCar', $rentalCar)
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($upkeeps) / self::PER_PAGE);
        $arrResult = array();
        foreach ($upkeeps as $value) {
            $arrTemp = array();
            $arrTemp['id'] = $value->getId();
            $arrTemp['rentalCar'] = $value->getRentalCar();
            $arrTemp['nextMileage'] = $value->getNextMileage();
            $arrTemp['upkeepTime'] = $value->getUpkeepTime();
            $arrTemp['nextUpkeepTime'] = $value->getNextUpkeepTime();
            $arrTemp['remark'] = $value->getRemark();
//            $arrTemp['upkeepDF'] = floor(($value->getNextUpkeepTime()->getTimestamp()-time())/(3600*24));
//            $arrTemp['mileageDF'] = $value->getNextMileage()-$mileageCurrent;
            $arrTemp['createTime'] = $value->getCreateTime();
            $arrResult[] = $arrTemp;
        }

        return ['upkeeps' => $arrResult, 'page' => $page, 'total' => $total, 'rentalCar' => $rentalCar];
    }


    /**
     * @Route("/upkeep/new/{id}", methods="GET", name="auto_admin_rentalcar_upkeep_new")
     * @Template()
     */
    public function upkeepNewAction($id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $mileageCurrent = $this->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($rentalCar);
        $form = $this->createForm(new UpkeepOneType(), null, [
            'action' => $this->generateUrl('auto_admin_rentalcar_upkeep_create', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        return ['form' => $form->createView(), 'rentalCar' => $rentalCar, 'mileageCurrent' => $mileageCurrent];
    }

    /**
     * @Route("/upkeep/new/{id}", methods="POST", name="auto_admin_rentalcar_upkeep_create")
     * @Template()
     */
    public function upkeepCreateAction(Request $req, $id)
    {
        $upkeep = new \Auto\Bundle\ManagerBundle\Entity\Upkeep();

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $form = $this->createForm(new UpkeepOneType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_rentalcar_upkeep_create', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $upkeep->setRentalCar($rentalCar);
            $man = $this->getDoctrine()->getManager();
            $man->persist($upkeep);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_upkeep_list', ['id' => $rentalCar->getId()]));
        }
        return ['form' => $form->createView()];
    }

    /**
     * @Route("/upkeep/del/{id}", methods="GET", name="auto_admin_rentalcar_upkeep_delete",requirements={"id"="\d+"})
     */
    public function upkeepDeleteAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $man = $this->getDoctrine()->getManager();
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $man->remove($upkeep);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rentalcar_upkeep_list', ['id' => $rentalCar->getId()]));
    }

    /**
     * @Route("/upkeep/edit/{id}", methods="GET", name="auto_admin_rentalcar_upkeep_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function upkeepEditAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $mileageCurrent = $this->get('auto_manager.rental_car_helper')->get_rental_car_current_mileage($rentalCar);
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $form = $this->createForm(new UpkeepOneType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_rentalcar_upkeep_update', ['id' => $upkeep->getId(), 'rentalcarid' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form' => $form->createView(), 'rentalCar' => $rentalCar, 'mileageCurrent' => $mileageCurrent
        ];
    }


    /**
     * @Route("/upkeep/edit/{id}", methods="POST", name="auto_admin_rentalcar_upkeep_update",requirements={"id"="\d+"})
     * @Template("")
     */
    public function upkeepUpdateAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        $upkeep = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Upkeep')
            ->find($id);
        $form = $this->createForm(new UpkeepOneType(), $upkeep, [
            'action' => $this->generateUrl('auto_admin_rentalcar_upkeep_update', ['id' => $upkeep->getId(), 'rentalcarid' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($upkeep);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_upkeep_list', ['id' => $rentalCar->getId()]));
        }
        return ['form' => $form->createView()];
    }

    /**
     * @Route("/orientation", methods="GET", name="auto_admin_add_station_rentalcar_orientation")
     * @Template()
     */
    public function orientationAction(Request $req)
    {
        $place = $req->query->get('place');
        $license = $req->query->get('license');
        $LicensePlace = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findOneBy(['id' => $place]);

        if (empty($LicensePlace)) {
            $data = null;
        } else {

            $lecensestr = $LicensePlace->getName() . $license;
            $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
                ('auto_api_rental_car_detail'),
                ['licenseplate' => $lecensestr]);
            $data = json_decode($post_json, true);
            if ($data['errorCode'] != 0) {
                return $this->render(
                    "AutoMobileBundle:Default:message.html.twig",
                    ['message' => $data['errorMessage']]);
            }
        }


        $license_place = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        return ["licensePlaces" => $license_place, "rentalCar" => $data, "license" => $lecensestr];
    }


}
