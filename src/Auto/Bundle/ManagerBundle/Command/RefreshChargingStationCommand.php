<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/14
 * Time: 上午10:43
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshChargingStationCommand extends ContainerAwareCommand{


    public function configure()
    {
        $this
            ->setName('auto:charging:station')
            ->setDescription('auto charging station')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stations_json = file_get_contents("http://id.dz.tt/index.php?m=state_server&c=api&a=dianzhuang_list_v2");
        $stations = json_decode($stations_json);

        $man = $this->getContainer()->get('doctrine')->getManager();

        array_map(function ($s)use ($man,$output) {

            $station =
                $man
                    ->getRepository('AutoManagerBundle:ChargingStation')
                    ->findOneBy(['outId' => $s->id])
            ;

            if(empty($station) && !strstr($s->name,'test')){
                $station = new \Auto\Bundle\ManagerBundle\Entity\ChargingStation();
                if($s->id!=null) $station->setOutId($s->id);
                if($s->name!=null) $station->setName($s->name);
                if($s->current_state!=null) $station->setCurrentStatus($s->current_state);
                if($s->is_active!=null) $station->setIsActive($s->is_active);
                if($s->charge_port_type!=null) $station->setPortType($s->charge_port_type);
                if($s->address!=null) $station->setAddress($s->address);
                if($s->charge_fast_num!=null) $station->setFastCount($s->charge_fast_num);
                if($s->charge_slow_num!=null) $station->setSlowCount($s->charge_slow_num);
                if($s->nature!=null) $station->setNature($s->nature);
                if($s->type!=null) $station->setType($s->type);
                if($s->coordinate!=null&&strstr($s->coordinate,',')){
                    $arr = explode(',',$s->coordinate);
                    $station->setLatitude($arr[1]);
                    $station->setLongitude($arr[0]);
                };
                if($s->ev_nums!=null) $station->setEvCount($s->ev_nums);

                $man->persist($station);
                $man->flush();

                $output->writeln(sprintf(
                    'add charging station <%d:%s>.',
                    $station->getId(),
                    $station->getName()
                ));
            }

        },$stations);









    }


}