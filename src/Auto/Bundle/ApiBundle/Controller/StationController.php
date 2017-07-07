<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/station")
 */
class StationController extends BaseController
{
    const PER_PAGE = 10;
    /**
     * @Route("/cluster/list", methods="POST" ,name="auto_api_station_cluster_list")
     */
    public function clusterListAction(Request $req)
    {
        $max_lat = $req->request->get('maxlat');
        $min_lat = $req->request->get('minlat');
        $max_lng = $req->request->get('maxlng');
        $min_lng = $req->request->get('minlng');
        $zoom = $req->request->get('zoom');
        $back_type = $req->request->get('backType');
        $pick_up_id = $req->request->get('pickUpRentalStationID');
        $city = $req->request->get('city');
        $stations = $this->get('auto_manager.station_helper')->getStationByLatlng($max_lat,$min_lat,$max_lng,
            $min_lng,$back_type);

        if(empty($stations)){
            if(empty($city)){
                return new JsonResponse([
                    'errorCode' =>  self::E_NO_STATION,
                    'errorMessage' =>  self::M_NO_STATION
                ]);
            }

            $city = mb_substr($city,mb_strlen($city,'utf-8')-1,1,'utf-8')=="市"? $city : $city."市";

            $area = $this->get('auto_manager.area_helper')->getArea($city,null);

            if(empty($area)){
                return new JsonResponse([
                    'errorCode' =>  self::E_NO_STATION,
                    "errorMessage" =>"您当前的城市暂无租赁车点，是否切换城市享受服务"
                ]);
            }

            $araStations = $this->get('auto_manager.station_helper')->getUseableStationByArea($area);

            if(empty($araStations)){
                return new JsonResponse([
                    'errorCode' =>  self::E_NO_STATION,
                    'errorMessage' => "您当前的城市暂无租赁车点，是否切换城市享受服务"
                ]);
            }

        }
        $rental_stations=[];
        $districtIds=[];
        $district=null;
        $district2=null;

        if($zoom==3 || $zoom==2 || $zoom==1){
            foreach($stations as $value){
                if($zoom==1){
                    $districtEntity=$value->getBusinessDistrict();
                }
                else if($zoom==2){
                    $districtEntity=$value->getArea();
                }
                else {
                    $districtEntity=$value->getArea()->getParent();
                }

                if(empty($districtEntity)){
                    continue;
                }

                if(! in_array($districtEntity->getId(),$districtIds)){
                    $districtIds[]=$districtEntity->getId();
                    $rental_stations[$districtEntity->getId()]=[
                        "rentalStationID"=>$districtEntity->getId(),
                        "name"=>$districtEntity->getName(),
                        "latitude"=>$districtEntity->getLatitude(),
                        "longitude"=>$districtEntity->getLongitude(),
                        "amount"=>0
                    ];
                }


                $data=$this->get('auto_manager.station_helper')->get_rental_car_status_count($value);

                $rental_stations[$districtEntity->getId()]["amount"]+=$data["usable"];

            }
            $rental_stations=array_values($rental_stations);

        }
        else{
            $rental_stations = array_map($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $stations);
            if($pick_up_id){

                foreach($rental_stations as $key=>$station){

                    if($station['rentalStationID']==$pick_up_id){
                        $rental_stations[$key]['parkingSpaceCount']+=1;
                        break;
                    }

                }
            }
        }

        if(empty($rental_stations)){
            $s = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findOneBy(['id'=>1]);
            $rental_stations[]= call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $s);

        }

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>$rental_stations,
        ]);


    }
    /**
     * @Route("/list", methods="POST")
     */
    public function indexAction(Request $req)
    {

        $max_lat = $req->request->get('maxlat');
        $min_lat = $req->request->get('minlat');
        $max_lng = $req->request->get('maxlng');
        $min_lng = $req->request->get('minlng');
        $back_type = $req->request->get('backType');
        $pick_up_id = $req->request->get('pickUpRentalStationID');



        $stations = $this->get('auto_manager.station_helper')->getStationByLatlng($max_lat,$min_lat,$max_lng,
            $min_lng,$back_type);



        if(empty($stations)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_STATION,
            ]);

        }

        $rental_stations = array_map($this->get('auto_manager.station_helper')->get_station_normalizer(), $stations)
        ;

        if($pick_up_id){

            foreach($rental_stations as $key=>$station){

                if($station['rentalStationID']==$pick_up_id){
                    $rental_stations[$key]['parkingSpaceCount']+=1;
                    break;
                }

            }
        }

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => $rental_stations]);
    }



    /**
     * @Route("/back/list", methods="POST",name="auto_api_station_back_list")
     */
    public function backListAction(Request $req)
    {
        $station_id = $req->request->get('rentalStationID');
        $pick_up_station_id = $req->request->get('pickUpRentalStationID');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;

        $backType=\Auto\Bundle\ManagerBundle\Entity\RentalStation::DIFFERENT_PLACE_BACK;
        $stations =

            $qb
                ->select('s')
                ->where($qb->expr()->eq('s.backType', ':backType'))
                ->andWhere($qb->expr()->eq('s.online', ':online'))
                ->andWhere($qb->expr()->gte('s.usableParkingSpace', ':usableParkingSpace'))
                ->orderBy('s.id', 'DESC')
                ->setParameter('backType', $backType)
                ->setParameter('online', 1)
                ->setParameter('usableParkingSpace', 1)
                ->getQuery()
                ->getResult();

        if(empty($stations)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_STATION,
            ]);

        }

        $rental_stations = array_map($this->get('auto_manager.station_helper')->get_station_normalizer(), $stations);


        if($pick_up_station_id){

            foreach($rental_stations as $key=>$station){

                if($station['rentalStationID']==$pick_up_station_id){
                    $rental_stations[$key]['parkingSpaceCount']+=1;
                    break;
                }

            }
        }

        return new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'      => $rental_stations]);
    }

    /**
     * @Route("/getLatlngImage", methods="POST")
     */

    public function getLatlngImageAction(Request $req)
    {
        $sid = $req->request->get('stationID');
        $rental_station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($sid);

        if(empty($rental_station)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_STATION,
                'errorMessage' =>  self::M_NO_STATION,
            ]);

        }
        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'rentalStation' => call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer(), $rental_station)
        ]);
    }

    /**
     * @Route("/searchByName", methods="POST", name="auto_api_station_searchByName")
     */
    public function searchByNameAction(Request $req)
    {
        $page = $req->request->get('page')?$req->request->get('page'):0;
        $latitude = $req->request->get('latitude');
        $longitude = $req->request->get('longitude');
        $name = $req->request->get('name');
        $back_type = $req->request->get('backType');

        /**
         * @var $repos \Doctrine\ORM\EntityManager
         */

        $repos = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Station');
        $qb = $repos->createQueryBuilder('s')
            ->select('s')
            ->join('s.area','a');

        $qb->where($qb->expr()->orX(
            $qb->expr()->like('s.name', ':name'),
            $qb->expr()->like('a.name', ':name')
        ))
            ->andWhere($qb->expr()->eq('s.online', ':online'))
            ->setParameter('online',1)
            ->setParameter('name', '%'.$name.'%');
        if($back_type){
            $qb->andWhere($qb->expr()->eq('s.backType',':backType'))
                ->setParameter('backType',$back_type);
        }

        $stations = $qb->getQuery()
            ->execute();


        $station_array = array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$stations);

        $station_list =
            array_map(function($station)use($longitude,$latitude){
                $station['walkDistance'] = $this->get('auto_manager.amap_helper')->walking_distance([$longitude,
                    $latitude],[$station['longitude'],$station['latitude']]);
                return $station;
            },$station_array);


        uasort($station_list,function($a,$b){

            if ($a['walkDistance'] == $b['walkDistance']) return 0;
            return ($a['walkDistance'] > $b['walkDistance']) ? 1 : -1;
        });


        $station_list = array_slice($station_list,($page-1)*self::PER_PAGE,self::PER_PAGE);


        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>$station_list,
            'page'      =>$page,
            'pageCount' =>ceil(count($stations) / self::PER_PAGE),

        ]);
    }


    /**
     * @Route("/searchByCoordinate", methods="POST",name="auto_api_search_by_coordinate")
     */
    public function searchByCoordinateAction(Request $req)
    {
        $latitude = $req->request->get('latitude');
        $longitude = $req->request->get('longitude');
        $back_type = $req->request->get('backType');

        list($maxlng,$maxlat,$minlng,$minlat) = $this->get('auto_manager.amap_helper')->square_point($longitude,
            $latitude);

        /**
         * @var $repos \Doctrine\ORM\EntityManager
         */
        $repos = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation');

        $qb = $repos->createQueryBuilder('s')
            ->select('s');


            $qb
                ->andWhere($qb->expr()->gt('s.latitude',':minlat'))
                ->andWhere($qb->expr()->lt('s.latitude',':maxlat'))
                ->andWhere($qb->expr()->gt('s.longitude',':minlng'))
                ->andWhere($qb->expr()->lt('s.longitude',':maxlng'))
                ->andWhere($qb->expr()->eq('s.online', ':online'))
                ->setParameter('online',1)
                ->setParameter('minlat', $minlat)
                ->setParameter('maxlat', $maxlat)
                ->setParameter('maxlng', $maxlng)
                ->setParameter('minlng', $minlng);

            if($back_type){
                $qb->andWhere($qb->expr()->eq('s.backType',':backType'))
                    ->setParameter('backType',$back_type);
            }
        $stations =
            $qb->getQuery()
                ->execute();


        $station_array = array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$stations);

        $station_list =
            array_map(function($station)use($longitude,$latitude){
                $station['walkDistance'] = $this->get('auto_manager.amap_helper')->walking_distance([$longitude,
                    $latitude],[$station['longitude'],$station['latitude']]);
                return $station;
            },$station_array);


        uasort($station_list,function($a,$b){

            if ($a['walkDistance'] == $b['walkDistance']) return 0;
            return ($a['walkDistance'] > $b['walkDistance']) ? 1 : -1;
        });

        $station_list = array_slice($station_list,0,10);

        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>$station_list,
        ]);
    }



    /**
     * @Route("/hot", methods="POST")
     */
    public function hotAction(Request $req)
    {
        /**
         * @var $repos \Doctrine\ORM\EntityManager
         */
        $repos = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Station');


        $cityName = $req->request->get('cityName');

        $qb = $repos->createQueryBuilder('s')
            ->select('s')
            ->join('s.area','a')
            ->join('a.parent','p')
        ;

        $stations = $qb->where($qb->expr()->orX(
            $qb->expr()->like('p.name', ':name'),
            $qb->expr()->like('a.name', ':name')
        ))
            ->andWhere($qb->expr()->eq('s.online', ':online'))
            ->setParameter('online',1)
            ->setParameter('name', '%'.$cityName.'%')
            ->getQuery()
            ->execute();

        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>array_map($this->get('auto_manager.station_helper')->get_station_normalizer(),$stations),
        ]);
    }



    /**
     * @Route("/charging/list", methods="GET")
     */
    public function chargingListAction(Request $req)
    {
        $stations = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:ChargingStation')
            ->findAll();
        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'stations'=>array_map($this->get('auto_manager.station_helper')->get_charging_station_normalizer(),$stations),
        ]);

    }




    /**
     * @Route("/recommend", methods="POST")
     */
    public function recommendAction(Request $req)
    {
        $name = $req->request->get('name');
        $address = $req->request->get('address');
        $latitude = $req->request->get('latitude');
        $longitude = $req->request->get('longitude');
        $uid = $req->request->get('userID');


        $station = new \Auto\Bundle\ManagerBundle\Entity\RecommendStation();
        $station
                ->setAddress($address)
                ->setLatitude($latitude)
                ->setLongitude($longitude)
                ->setName($name)
            ;

        if($uid){
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['token'=>$uid]);

            $station ->setMember($member);


        }

        $man = $this->getDoctrine()->getManager();
        $man->persist($station);
        $man->flush();

        return new JsonResponse([
            'errorCode'     =>  self::E_OK
        ]);


    }

    /**
     * @Route("/see", methods="POST")
     */

    public function seeAction(Request $req)
    {

        $rental_station = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findAll();

        $station_array_data = [];

        $i = 0;

        foreach($rental_station as $r){


            $station_array_data[$i]['name'] = $r->getName();

            $station_array_data[$i]['latitude'] = $r->getLatitude();

            $station_array_data[$i]['longitude'] = $r->getLongitude();

            $station_array_data[$i]['images'] = $r->getImages();

            $i++;


        }

        foreach($station_array_data as $k=>$st){

            if(empty($st['images'])){
                $station_array_data[$k]['images'][0] = '无图片链接地址';
                $station_array_data[$k]['images'][1] = '无图片链接地址';
                $station_array_data[$k]['images'][2] = '无图片链接地址';
                $station_array_data[$k]['images'][3] = '无图片链接地址';
                $station_array_data[$k]['images'][4] = '无图片链接地址';

            }else{

                $station_array_data[$k]['images'][0] = 'http://www.bkcar.cn/photograph/'.substr( $station_array_data[$k]['images'][0],0,2).'/'.substr( $station_array_data[$k]['images'][0],2,2).'/'.$station_array_data[$k]['images'][0];
                $station_array_data[$k]['images'][1] = 'http://www.bkcar.cn/photograph/'.substr( $station_array_data[$k]['images'][1],0,2).'/'.substr( $station_array_data[$k]['images'][1],2,2).'/'.$station_array_data[$k]['images'][1];
                $station_array_data[$k]['images'][2] = 'http://www.bkcar.cn/photograph/'.substr( $station_array_data[$k]['images'][2],0,2).'/'.substr( $station_array_data[$k]['images'][2],2,2).'/'.$station_array_data[$k]['images'][2];
                $station_array_data[$k]['images'][3] = 'http://www.bkcar.cn/photograph/'.substr( $station_array_data[$k]['images'][3],0,2).'/'.substr( $station_array_data[$k]['images'][3],2,2).'/'.$station_array_data[$k]['images'][3];
                $station_array_data[$k]['images'][4] = 'http://www.bkcar.cn/photograph/'.substr( $station_array_data[$k]['images'][4],0,2).'/'.substr( $station_array_data[$k]['images'][4],2,2).'/'.$station_array_data[$k]['images'][4];


            }



        }



//        var_dump($station_array_data);exit;

        echo "<table border='1px';><tr>
        <td>租赁点名称</td>
        <td>经度</td>
        <td>纬度</td>
        <td>租赁点图片1</td>
        <td>租赁点图片2</td>
        <td>租赁点图片3</td>
        <td>租赁点图片4</td>
        <td>租赁点图片5</td>
    </tr>";


        foreach( $station_array_data as $st ){


            echo "
        <tr>
        <td>".$st['name']."</td>
       <td>".$st['latitude']."</td>
       <td>".$st['longitude']."</td>
       <td>".$st['images'][0]."</td>
        <td>".$st['images'][1]."</td>
         <td>".$st['images'][2]."</td>
         <td>".$st['images'][3]."</td>
         <td>".$st['images'][4]."</td>
    </tr>";
                }

    "</table>".'<br/>'.'<br/>';




        exit;





    }

}
