<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/21
 * Time: 上午11:45
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalCar;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Process\Process;
use Auto\Bundle\ManagerBundle\Form\RegionCarType;
use Auto\Bundle\ManagerBundle\Form\InsuranceRecordType;


/**
 * @Route("/regioncar")
 */
class RegionCarController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_regioncar_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $rentalStation = $req->query->get('rentalStation');
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');
        $online = $req->query->get('online');
        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c')
                ->select('c')
        ;
        $qb
            ->join('c.rentalStation','s')
            ->where($qb->expr()->in('s.area',$areaIds));
        if($licensePlace && $plate_number){
                    $qb
                        ->andWhere( $qb->expr()->eq('c.licensePlate',':licensePlate') )
                        ->andWhere( $qb->expr()->eq('c.licensePlace',':licensePlace') )
                        ->setParameter('licensePlate', $plate_number)
                        ->setParameter('licensePlace', $licensePlace);

        }else{
            if($rentalStation){
                $qb
                    ->andWhere($qb->expr()->eq('s.name',':rentalStation'))
                    ->setParameter('rentalStation', $rentalStation);
            }
            if($online){
                $qb
                    ->join('c.online','ol')
                    ->andWhere($qb->expr()->eq('ol.status',':online'))
                    ->setParameter('online', $online);
            }
        }
        $rentalcars =
            new Paginator(
                $qb

                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $mileage=[];$carStatus=[];$insurance=[];
        foreach($rentalcars as $rentalcar ){
            if(!isset($mileage[$rentalcar->getId()])){
                $mileage[$rentalcar->getId()]=0;
            }
            if(!isset($carStatus[$rentalcar->getId()])){
                $carStatus[$rentalcar->getId()]=0;
            }
            if(!isset($insurance[$rentalcar->getId()])){
                $insurance[$rentalcar->getId()]=0;
            }
            $insuranceRecord =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:InsuranceRecord')
                    ->findBy(['rentalCar'=>$rentalcar,'insurance'=>1]);
            if(empty($insuranceRecord)){
                $insurance[$rentalcar->getId()]='缺失';
            }else{
                $insurance[$rentalcar->getId()]='齐全';
            }

            $carStatus[$rentalcar->getId()]=$this->get('auto_manager.rental_car_helper')->get_rental_car_status($rentalcar);
            $mileage[$rentalcar->getId()]=$this->get('auto_manager.rental_car_helper')->get_rental_car_range($rentalcar,5);
        }



        $total = ceil(count($rentalcars) / self::PER_PAGE);
        /*'rentalcars'=>array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
            $rentalcars->getIterator()->getArrayCopy()),*/
        return ['mileage'=>$mileage,'insurance'=>$insurance,'rentalcars'=>$rentalcars,'carStatus'=>$carStatus,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_regioncar_new")
     * @Template()
     */
    public function newAction()
    {
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;
        $rentalStations =
                $qb
                    ->select('s')
                    ->where($qb->expr()->in('s.area',$areaIds))
                    ->orderBy('s.id', 'ASC')
                    ->getQuery()
                    ->getResult();
        $form = $this->createForm(new RegionCarType(), null, [
            'action' => $this->generateUrl('auto_admin_regioncar_create'),
            'method' => 'POST',
        ]);

        return ['form'  => $form->createView(),'rentalStations'=>$rentalStations];
    }

    /**
     * @Route("/record/{id}", methods="GET", name="auto_admin_regioncar_record")
     * @Template()
     */
    public function recordAction($id)
    {
        $rental_car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $box = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$rental_car]);



        if(empty($box)){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message'=>'没有安装设备!']
            );
        }

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('LRANGE',array($rental_car->getDeviceCompany()->getEnglishName().'-mileage-'.$box->getCarStartId(),0,100));
        $mileage_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('LRANGE',array($rental_car->getDeviceCompany()->getEnglishName().'-power-'.$box->getCarStartId(),0,100));
        $power_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('LRANGE',array($rental_car->getDeviceCompany()->getEnglishName().'-range-'.$box->getCarStartId(),0,100));
        $range_arr = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('LRANGE',array($rental_car->getDeviceCompany()->getEnglishName().'-gps-'.$box->getCarStartId(),0,100));
        $gps_arr = $redis->executeCommand($redis_cmd);

        $mileage_list = [];
        $power_list = [];
        $range_list = [];
        $gps_list = [];
        foreach($mileage_arr as $mileage){
            $mileage_list[] = json_decode($mileage,true);
        }
        foreach($power_arr as $power){
            $power_list[] = json_decode($power,true);
        }
        foreach($range_arr as $range){
            $range_list[] = json_decode($range,true);
        }
        foreach($gps_arr as $gps){
            $gps_list[] = json_decode($gps,true);
        }

        return ['mileage_list'=>$mileage_list,'power_list'=>$power_list,'range_list'=>$range_list,'gps_list'=>$gps_list,'rentalCar'=>$rental_car];

    }

    /**
     * @Route("/operateRecord/{id}", methods="GET", name="auto_admin_regioncar_operateRecord")
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
                    ->where($qb->expr()->eq('o.rentalCar',':id'))
                    ->setParameter('id', $id)
                   ->getQuery()
                   ->getResult();
        $rentalCar=$this->getDoctrine()
        ->getRepository('AutoManagerBundle:RentalCar')
        ->findOneBy(["id"=>$id]);

        $records=array();
        foreach($operateRecords as $value){
            $reasonsIds=$value->getReason();
            $records[]=[
                "info"=>$value,
                "reasonsIds"=>$reasonsIds
            ];
        }
        $reasons=array(
            1=> "车辆外观已清洁",
            2=> "车辆轮胎完好",
            3=> "车辆内饰已清洁",
            4=>"保单复印件已有",
            5=>"车辆行驶本已有",
            6=>"车辆交强险标志存在",
            7=>"车辆年检标志存在",
            8=>"车辆备胎已有",
            9=>"车辆换胎工具已有",
            10=>"车辆充电线已有",
            11=>"车辆控制设备可用",
            12=>"设备故障",
            13=>"车辆充电",
            14=>"车辆故障/事故",
            15=>"调配车辆",
            16=>"用户还车",
            17=>"人工还车"
        );
//        $total = ceil(count($operateRecords) / self::PER_PAGE);

        return ['operateRecords'=>$records,'rentalCar'=>$rentalCar,'reasons'=>$reasons,'id'=>$id];
    }
    /**
     * @Route("/electricRecord/{id}", methods="GET", name="auto_admin_regioncar_electricRecord")
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
                ->where($qb->expr()->eq('o.rentalCar',':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        $rentalCar=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(["id"=>$id]);

        $records=array();
        foreach($operateRecords as $value){
            $reasonsIds=$value->getReason();
            $records[]=[
                "info"=>$value,
                "reasonsIds"=>$reasonsIds
            ];
        }

        return ['operateRecords'=>$records,'rentalCar'=>$rentalCar,'id'=>$id];
    }

    /**
     * @Route("/dispatchRecord/{id}", methods="GET", name="auto_admin_regioncar_dispatchRecord")
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
                ->where($qb->expr()->eq('o.rentalCar',':id'))
                //->andWhere($qb->expr()->eq('o.kind',':kind'))
                ->setParameter('id', $id)
                //->setParameter('kind', 2)
                ->getQuery()
                ->getResult();
        $rentalCar=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findOneBy(["id"=>$id]);

        return ['dispatchRecords'=>$dispatchRecords,'rentalCar'=>$rentalCar];
    }

    /**
     * @Route("/dispatch/{id}", methods="GET", name="auto_admin_regioncar_dispatch",requirements={"id"="\d+"})
     * @Template()
     */
    public function dispatchAction(Request $req,$id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;
        $rentalStations =
            $qb
                ->select('s')
                ->where($qb->expr()->in('s.area',$areaIds))
                ->orderBy('s.id', 'ASC')
                ->getQuery()
                ->getResult();
        return ['rentalCar'=>$rentalCar,'rentalStations'=>$rentalStations];
    }

    /**
     * @Route("/dispatchCreate/{id}", methods="GET", name="auto_admin_regioncar_dispatch_create",requirements={"id"="\d+"})
     * @Template()
     */
    public function dispatchCreateAction(Request $req,$id)
    {
        $rentalStationId = $req->query->get('rentalStation');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $rentalStations=$this->getDoctrine()
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
            ->setOperateMember($this->getUser())
        ;
        $man = $this->getDoctrine()->getManager();
        $man->persist($dispatch);
        $man->flush();

        $rentalCar->setRentalStation($rentalStation);

        $man = $this->getDoctrine()->getManager();
        $man->persist($rentalCar);
        $man->flush();


        return $this->redirect($this->generateUrl('auto_admin_regioncar_list'));

        return ['rentalCar'=>$rentalCar,'rentalStations'=>$rentalStations];
    }






    /**
     * @Route("/new", methods="POST", name="auto_admin_regioncar_create")
     * @Template("AutoAdminBundle:RentalCar:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalCar = new \Auto\Bundle\ManagerBundle\Entity\RentalCar();

        $form = $this->createForm(new RegionCarType(), $rentalCar, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        $retalStationId = $req->request->getInt('rentalStation');
        $retalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($retalStationId);

        if ($form->isValid() && !empty($retalStation)) {

            $price=$this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalPrice')
                ->findOneBy(["car"=>$rentalCar->getCar()]);

            if(empty($price)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'车辆没有定价!']
                );

            }
            $rentalCar->setRentalStation($retalStation);
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



            return $this->redirect($this->generateUrl('auto_admin_regioncar_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_regioncar_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $form = $this->createForm(new RegionCarType(), $rentalcar, [
            'action' => $this->generateUrl('auto_admin_regioncar_update', ['id' => $rentalcar->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'      => $form->createView(),
            'rentalcar' => $rentalcar
        ];
    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_regioncar_info",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {


        $rentalOrders = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findBy(['rentalCar'=>$id],['createTime'=>'desc']);

        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $status = $this->get('auto_manager.rental_car_helper')->get_rental_car_status($rentalcar);

        return [
            'rentalcar' => $rentalcar,'rentalOrders' => $rentalOrders,'userid'=>$id,'car_status'=>$status
        ];
    }



    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_regioncar_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RentalCar:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $form = $this->createForm(new RegionCarType(), $rentalCar, [
            'action' => $this->generateUrl('auto_admin_regioncar_update', ['id' => $rentalCar->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalCar);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_regioncar_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_regioncar_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $man->remove($rentalCar);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_regioncar_list'));
    }


    /**
     * @Route("/locate/{id}", methods="GET", name="auto_admin_region_car_locate",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function locateAction($id){

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);



        if(empty($rentalCar->getBoxId())){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message'=>'没有安装设备!']
            );
        }
        $this->get("auto_manager.rental_car_helper")->operate($rentalCar,'gps',$this->getUser());

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('LINDEX',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0));
        $box_json = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('LRANGE',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0,20));
        $locations = $redis->executeCommand($redis_cmd);

        $gps_list = [];
        foreach($locations as $gps){
            $arr = [];
            $gps = $this->get('auto_manager.curl_helper')->object_array(json_decode($gps));
            $arr['coordinate'] = $gps['coordinate'];
            $arr['time'] = date('Y-m-d H:i:s',$gps['time']);
            $gps_list[] = $arr;
        }


        if(empty($box_json)){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message'=>'没有坐标数据!']
            );
        }
        $gps_arr = $this->get('auto_manager.curl_helper')->object_array(json_decode($box_json));

        return ['coordinate'=>$gps_arr['coordinate'],'time'=>date('Y-m-d H:i:s',$gps_arr['time']),'unusual'=>(new \DateTime())->getTimestamp()-$gps_arr['time']>60*5?1:0,'locations'=>$gps_list];


    }

    /**
     * @Route("/insurancelist/{page}", methods="GET", name="auto_admin_regioncar_insurance_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function insuranceListAction(Request $req,$page = 1)
    {
        $rentalCarId = $req->query->get('rentalcarid');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InsuranceRecord')
                ->createQueryBuilder('i')
        ;
        if($rentalCarId){
            $rentalCar = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($rentalCarId);

            $qb
                ->andWhere($qb->expr()->eq('i.rentalCar', ':rentalCar'))
                ->setParameter('rentalCar', $rentalCar);
        }

        $insurances =
            new Paginator(
                $qb
                    ->select('i')
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($insurances) / self::PER_PAGE);
        return ['rentalcarid'=>$rentalCarId,'insurances'=>$insurances,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/insurancenew", methods="GET", name="auto_admin_regioncar_insurance_new")
     * @Template()
     */
    public function insuranceNewAction(Request $req)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        if(empty($rentalCar)){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message'=>'无此车辆!']
            );
        }
        $url = $this->generateUrl('auto_admin_regioncar_insurance_create', ['rentalcarid' => $rentalCarId]);
        $form = $this->createForm(new InsuranceRecordType(), null, [
            'action' => $url,
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalCar];
    }

    /**
     * @Route("/insurancenew", methods="POST", name="auto_admin_regioncar_insurance_create")
     * @Template("AutoAdminBundle:RegionCar:insuranceNew.html.twig")
     */
    public function insuranceCreateAction(Request $req)
    {
        $rentalCarId = $req->query->get('rentalcarid');

        $insurance = new \Auto\Bundle\ManagerBundle\Entity\InsuranceRecord();

        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $this->generateUrl('auto_admin_regioncar_insurance_create', ['rentalcarid' => $rentalCarId]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($insurance);
            $man->flush();
            $url = $this->generateUrl('auto_admin_regioncar_insurance_list', ['rentalcarid' => $rentalCarId]);
            return $this->redirect($url);
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/insuranceedit/{id}", methods="GET", name="auto_admin_regioncar_insurance_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function insuranceEditAction(Request $req,$id)
    {
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);
        if(empty($rentalCar)){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message'=>'无此车辆!']
            );
        }
        $url = $this->generateUrl('auto_admin_regioncar_insurance_update', ['id' => $insurance->getId(),'rentalcarid' => $rentalCarId]);
        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $url,
            'method' => 'POST'
        ]);
        return ['form'  => $form->createView(),'rentalcar' => $rentalCar];
    }


    /**
     * @Route("/insuranceedit{id}", methods="POST", name="auto_admin_regioncar_insurance_update",requirements={"id"="\d+"})
     * @Template()
     */
    public function insuranceUpdateAction(Request $req, $id)
    {
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $this->generateUrl('auto_admin_regioncar_insurance_update', ['id' => $insurance->getId()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($insurance);
            $man->flush();
            $rentalCarId = $req->query->get('rentalcarid');
            $url = $this->generateUrl('auto_admin_regioncar_insurance_list', ['rentalcarid' => $rentalCarId]);
            return $this->redirect($url);
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/insurancedel/{id}", methods="GET", name="auto_admin_regioncar_insurance_delete",requirements={"id"="\d+"})
     */
    public function insuranceDeleteAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $url = $this->generateUrl('auto_admin_regioncar_insurance_list', ['rentalcarid' => $rentalCarId]);
        $man = $this->getDoctrine()->getManager();
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $man->remove($insurance);
        $man->flush();

        return $this->redirect($url);
    }



}