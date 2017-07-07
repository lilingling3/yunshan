<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\OperateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Auto\Bundle\ManagerBundle\Form\RentalCarOperateType;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/rentalCar")
 */
class RentalCarController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/list/{sid}", methods="GET", name="auto_operate_rental_car_list",requirements={"sid"="\d+"})
     * @Template()
     */
    public function listAction($sid)
    {

        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($sid);

        $rentalCars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->findBy(['rentalStation'=>$rentalStation]);



        return [
            'rentalCars' => array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),
                $rentalCars),
            'rentalStation'=>call_user_func($this->get('auto_manager.station_helper')
                ->get_station_normalizer(),
                $rentalStation),
        ];

    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_operate_rental_car_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $orderqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');
        $nopayorders= $orderqb
            ->select('o')
            ->where($orderqb->expr()->eq('o.rentalCar',$id))
            ->andWhere($orderqb->expr()->isNotNull('o.endTime'))
            ->andWhere($orderqb->expr()->isNull('o.cancelTime'))
            ->andWhere($orderqb->expr()->isNull('o.payTime'))
            ->getQuery()
            ->getResult() ;

        $illegalqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('i');
        $illegal=
            $illegalqb->select('i')
                ->where($illegalqb->expr()->eq('i.rentalCar',$id))
                ->andWhere($illegalqb->expr()->isNull('i.handleTime'))
                ->getQuery()
                ->getResult() ;

        return ['rentalCar'=>call_user_func($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer()
            ,$rentalCar),'nopayorders'=>count($nopayorders),'illegal'=>count($illegal)
        ];
    }

    /**
     * @Route("/change/station/{id}/{sid}", methods="GET", name="auto_operate_rental_car_change_station",
     * requirements={"id"="\d+","sid"="\d+"})
     * @Template()
     */
    public function changeStationAction($id,$sid)
    {


        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_dispatch'),
            ['userID'=>$this->getUser()->getToken(),'rentalCarID'=>$id,'dispatchStationID'=>$sid]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return $this->redirect($this->generateUrl('auto_operate_rental_car_detail',['id'=>$id]));

    }


    /**
     * @Route("/detail/{id}", methods="GET", name="auto_operate_rental_car_detail",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function detailAction($id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $tBox= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$rentalCar]);
        $status=$this->get('auto_manager.RentalCar_helper')->get_rental_car_status($rentalCar);
        $carStyle=[
            "id"=>$id,
            "status"=>$status,
            "name"=>$rentalCar->getCar()->getName(),
            "color"=>$rentalCar->getColor()->getName(),
            "licensePlace"=>$rentalCar->getLicensePlace()->getName(),
            "licensePlate"=>$rentalCar->getLicensePlate(),
            "chassisNumber"=>$rentalCar->getChassisNumber(),
            "engineNumber"=>$rentalCar->getEngineNumber(),
            "rentalStation"=>$rentalCar->getRentalStation()->getName(),
            "company"=>$rentalCar->getCompany()->getName(),
            "carStart"=>"CarStart",
            "tBox"=>$rentalCar->getBoxId(),
            "online"=>$rentalCar->getOnline()
        ];

        return ['rentalCar'=>$carStyle];
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="auto_operate_rental_car_edit",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $tBox= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$rentalCar]);
        $form = $this->createFormBuilder()
            ->add('car', new RentalCarOperateType(),['data'=>$rentalCar])
            ->add('tBox', 'text', ['data' =>$rentalCar->getBoxId(),'required' => false])
            ->setAction($this->generateUrl('auto_operate_rentalcar_update', ['id' => $rentalCar->getId()]))
            ->getForm();

        return [
            'form'      => $form->createView(),
            'rentalCar' => $rentalCar
        ];

    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_operate_rentalcar_update",requirements={"id"="\d+"})
     * @Template("AutoOperateBundle:RentalCar:show.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $tBox= $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$rentalCar]);

        $form = $this->createFormBuilder()
            ->add('car', new RentalCarOperateType(),['data'=>$rentalCar])
            ->add('tBox', 'text', ['data' =>$rentalCar->getBoxId(),'required' => false])
            ->setAction($this->generateUrl('auto_operate_rentalcar_update', ['id' => $rentalCar->getId()]))
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {
            $data = $form->getData();
            $car = $data['car'];
            if($data['tBox'] != $rentalCar->getBoxId()){
                $tBox->setCarStartId($data['tBox']);
            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalCar);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_operate_rental_car_detail', ['id' => $rentalCar->getId()]));
        }

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/locate/{id}", methods="GET", name="auto_operate_rental_car_locate",
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



        $process =
            $this->get("auto_manager.rental_car_helper")->operate($rentalCar,"gps",$this->getUser(),'');



        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('LINDEX',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0));
        $box_json = $redis->executeCommand($redis_cmd);

        $redis_cmd= $redis->createCommand('LRANGE',array($rentalCar->getDeviceCompany()->getEnglishName().'-gps-'.$rentalCar->getBoxId(),0,20));
        $locations = $redis->executeCommand($redis_cmd);

        // 获取设备离线状态
        $redis_cmd= $redis->createCommand('HGET',array("feeZu-car-online-status",$rentalCar->getBoxId()));
        $onlinestatus = $redis->executeCommand($redis_cmd);

        // 获取设备离线状态
        $redis_cmd= $redis->createCommand('HGET',array("feeZu-car-speed-status",$rentalCar->getBoxId()));
        $speed = $redis->executeCommand($redis_cmd);

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

        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);
        $name = $rentalCar->getLicense();

        return [
                'name' =>$name,
                'coordinate' => $gps_arr['coordinate'],
                'time' => date('Y-m-d H:i:s',$gps_arr['time']),
                'unusual' => ($speed != 0) ? 3: ($onlinestatus == 1 ? 1 : 2 ),
                'locations' => $gps_list
        ];


    }

    /**
     * @Route("/carOnline/{id}", methods="GET", name="auto_operate_car_online",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function carOnlineAction($id){
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_onlineReasons'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"onlineStatus"=>1]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ["id"=>$id,"onlineReasons"=>$data["onlineReasons"]];
    }


    /**
     * @Route("/carOnline", methods="POST", name="auto_operate_rental_car_online_update")
     * @Template("AutoOperateBundle:RentalCar:carOnline.html.twig")
     */
    public function onlineShowAction(Request $req)
    {
        $id=$req->request->get('id');
        $text=json_decode($req->request->get('text'),true);

        return $this->redirect($this->generateUrl('auto_operate_rental_car_online',['id'=>$id]));
    }

    /**
     * @Route("/carOffline/{id}", methods="GET", name="auto_operate_car_offline",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function carOfflineAction($id){
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_onlineReasons'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"onlineStatus"=>0]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ["id"=>$id,"orderId"=>"","onlineReasons"=>$data["onlineReasons"],"rentalCar"=>$data["rentalCar"]];
    }

    /**
     * @Route("/carOffline", methods="POST", name="auto_operate_car_offline_back")
     * @Template("AutoOperateBundle:RentalCar:carOffline.html.twig")
     */
    public function carOfflineBackAction(Request $req){
        $id=$req->request->get('carID');
        $orderId=$req->request->get('orderID');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_onlineReasons'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"onlineStatus"=>0]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ["id"=>$id,"orderId"=>$orderId,"onlineReasons"=>$data["onlineReasons"],"rentalCar"=>$data["rentalCar"]];
    }
    /**
     * @Route("/carOffline", methods="POST", name="auto_operate_rental_car_offline_update")
     * @Template("AutoOperateBundle:RentalCar:carOffline.html.twig")
     */
    public function offlineShowAction(Request $req)
    {
        $id=$req->request->get('id');
        $text=json_decode($req->request->get('text'),true);


        return $this->redirect($this->generateUrl('auto_operate_rental_car_offline',['id'=>$id]));
    }

    /**
     * @Route("/carOfflineList/{id}", methods="GET", name="auto_operate_car_offline_list",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function carOfflineListAction($id){
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
                ->createQueryBuilder('o');
        $carofflins= $qb
            ->select('o')
            ->orderBy('o.createTime', 'DESC')
            ->where($qb->expr()->eq('o.rentalCar',':id'))
            ->andWhere($qb->expr()->eq('o.status',':status'))
            ->setParameter('id', $id)
            ->setParameter('status', 0)
            ->getQuery()
            ->getResult() ;
        $offlins=array();
        foreach($carofflins as $value){
            $reasonsIds=$value->getReason();
            $offlins[]=[
                "info"=>$value,
                "reasonsIds"=>$reasonsIds
            ];
        }
        $reasons=$this->get('auto_manager.rental_car_helper')->get_offline_options();
        return ["offlins"=>$offlins,"reasons"=>$reasons,"id"=>$id];
    }

    /**
     * @Route("/carOnlineList/{id}/{page}", methods="GET", name="auto_operate_car_online_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function carOnlineListAction($id,$page){

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_onlineReasons_list'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"onlineStatus"=>1,"page"=>$page]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        
        return ["onlins"=>$data["onlins"],"id"=>$id,"pageCount"=>$data["pageCount"],
            "page"=>$page,];
    }

    /**
     * @Route("/carOnlineShow/{id}", methods="GET", name="auto_operate_car_online_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function carOnlineShowAction($id){
        $onlin = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCarOnlineRecord')
            ->findOneBy(['id'=>$id]);
        $reasonsIds=$onlin->getReason();
        $reasons=$this->get('auto_manager.rental_car_helper')->get_online_options();
        return ["onlin"=>$onlin,"reasons"=>$reasons,"reasonsIds"=>$reasonsIds];
    }


    //车辆搜索

    /**
     * @Route("/search/{page}", methods="GET", name="auto_operate_car_search",
     * requirements={"page"="\d+"},
     * defaults={"page"=1}))
     * @Template()
     */
    public function searchAction($page = 1,Request $req ){

        $status=$req->query->get('car-staus');
           if(empty($status) && $status!==0){
               $status=-1;
           }
        $type=$req->query->get('car-type');
        if(empty($type) && $type !==0){
            $type=-1;
        }
        $page=1;
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operate_getRentalCars'),
            ['userID'=>$this->getUser()->getToken(),'page'=>$page,"carStaus"=>$status,"carType"=>$type]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        $carqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Car')
                ->createQueryBuilder('car')
        ;
        $cars =
            $carqb
                ->select('car')
                ->orderBy('car.id', 'DESC')
                ->getQuery()
                ->getResult() ;
        $flag=['status'=>$status,'type'=>$type];

        return ["rentalCars"=>$data["rentalCars"],'page'=>$data["page"],'total'=>$data["pageCount"],"cars"=>$cars,'flag'=>$flag];
    }

    //车辆搜索

    /**
     * @Route("/conditsearch", methods="POST", name="auto_operate_car_search_condit")
     * @Template("AutoOperateBundle:RentalCar:search.html.twig")
     */
    public function conditSearchAction(Request $req){
        $status=$req->request->get('car-staus');
        $type=$req->request->get('car-type');
        $operate =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$this->getUser()]);
        $stations=$operate->getStations();
        $stationsId=array();
        foreach($stations as $value){
            $stationsId[]=$value->getId();
        }
        $carsAll =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');
        if($type==-1 && $status==-1){
            $rentalCars =
                $qb
                    ->select('c')
                    ->where($qb->expr()->in('c.rentalStation',$stationsId))
                    ->getQuery()
                    ->getResult() ;
        }
        elseif($type==-1 && $status !=-1){
            if ($status ==0){
                $rentalCars =
                    $qb
                        ->select('c')
                        ->leftJoin('c.online','o')
                        ->where($qb->expr()->in('c.rentalStation',$stationsId))
                        ->andWhere($qb->expr()->orX($qb->expr()->eq('o.status', ':status'),$qb->expr()->isNull('c.online')))
                        ->setParameter('status',$status)
                        ->getQuery()
                        ->getResult() ;
            }
            else{
                $rentalCars =
                    $qb
                        ->select('c')
                        ->join('c.online','o')
                        ->where($qb->expr()->in('c.rentalStation',$stationsId))
                        ->andWhere($qb->expr()->eq('o.status', ':status'))
                        ->setParameter('status',$status)
                        ->getQuery()
                        ->getResult() ;
            }
        }
        elseif($type!=-1 && $status ==-1){
            $rentalCars =
                $qb
                    ->select('c')
                    ->where($qb->expr()->in('c.rentalStation',$stationsId))
                    ->andWhere($qb->expr()->eq('c.car', ':car'))
                    ->setParameter('car',$type)
                    ->getQuery()
                    ->getResult() ;
        }
        else{
            if ($status ==0){
                $rentalCars =
                    $qb
                        ->select('c')
                        ->leftJoin('c.online','o')
                        ->where($qb->expr()->in('c.rentalStation',$stationsId))
                        ->andWhere($qb->expr()->orX($qb->expr()->eq('o.status', ':status'),$qb->expr()->isNull('c.online')))
                        ->andWhere($qb->expr()->eq('c.car', ':car'))
                        ->setParameter('car',$type)
                        ->setParameter('status',$status)
                        ->getQuery()
                        ->getResult() ;
            }
            else{
                $rentalCars =
                    $qb
                        ->select('c')
                        ->join('c.online','o')
                        ->where($qb->expr()->in('c.rentalStation',$stationsId))
                        ->andWhere($qb->expr()->eq('c.car', ':car'))
                        ->andWhere($qb->expr()->eq('o.status', ':status'))
                        ->setParameter('status',$status)
                        ->setParameter('car',$type)
                        ->getQuery()
                        ->getResult() ;
            }

        }

        $flag=['status'=>$status,'type'=>$type];
        $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCars);
        return ['rentalCars'=>$rental_cars,'cars'=>$carsAll,'flag'=>$flag];
    }
    /**
     * @Route("/searchlicense", methods="GET", name="auto_operate_search_license")
     * @Template()
     */

    public function searchLicenseAction(){
        $licensePlaces =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        return ["licensePlaces"=>$licensePlaces,'rentalCars'=>'','place'=>-1];
    }

    /**
     * @Route("/searchlicenseCar", methods="POST", name="auto_operate_search_license_car")
     * @Template("AutoOperateBundle:RentalCar:searchLicense.html.twig")
     */

    public function searchLicenseCarAction(Request $req){
        $licensePlate=$req->request->get('plate');
        $licensePlace=$req->request->get('place');
        $licensePlaces =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $operate =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Operator')
            ->findOneBy(['member'=>$this->getUser()]);
        $stations=$operate->getStations();
        $stationsId=array();
        foreach($stations as $value){
            $stationsId[]=$value->getId();
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c');
        $rentalCar =
            $qb
                ->select('c')
                ->join('c.licensePlace','p')
                ->where($qb->expr()->in('c.rentalStation',$stationsId))
                ->andWhere($qb->expr()->eq('c.licensePlate', ':licensePlate'))
                ->andWhere($qb->expr()->eq('p.id',':id'))
                ->setParameter('licensePlate',$licensePlate)
                ->setParameter('id',$licensePlace)
                ->getQuery()
                ->getResult() ;

        $car=array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_normalizer(),$rentalCar);
        return ["licensePlaces"=>$licensePlaces,"rentalCars"=>$car,'place'=>$licensePlace];
    }


    /**
     * @Route("/add", methods="GET", name="auto_operate_rental_car_add")
     * @Template()
     */
    public function addAction(){

        $form = $this->createFormBuilder()
            ->add('car', new RentalCarOperateType(),['data'=>null])
            ->add('tBox', 'text', ['data' =>null,'required' => false])
            ->setAction($this->generateUrl('auto_operate_rental_car_create'))
            ->getForm();
        return [
            'form'      => $form->createView()
        ];
    }
    /**
     * @Route("/create", methods="POST", name="auto_operate_rental_car_create")
     * @Template("AutoOperateBundle:Car:add.html.twig")
     */
    public function createAction(Request $req){
        $rentalCar = new \Auto\Bundle\ManagerBundle\Entity\RentalCar();
        $tBox = new \Auto\Bundle\ManagerBundle\Entity\CarStartTbox();
        $form = $this->createFormBuilder()
            ->add('car', new RentalCarOperateType(),['data'=>$rentalCar])
            ->add('tBox', 'text', ['data' =>null,'required' => false])
            ->setAction($this->generateUrl('auto_operate_rental_car_create'))
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $car = $data['car'];
            if(!empty($data['tBox'])){
                $tBox->setRentalCar($rentalCar);
                $tBox->setCarStartId($data['tBox']);
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalCar);
            $man->persist($tBox);
            $man->flush();


            return $this->redirect($this->generateUrl('auto_operate_car_search'));
        }

        return ['form'  => $form->createView()];
    }

}