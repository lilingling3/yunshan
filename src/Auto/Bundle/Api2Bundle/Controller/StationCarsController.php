<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/13
 * Time: 上午10:30
 */

namespace Auto\Bundle\Api2Bundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/stationCars")
 */
class StationCarsController extends BaseController{

    /**
     * @Route("/v1/get", methods="POST",name="auto_api2_v1_get_cars_by_station")
     */

    public function getAction(Request $req){

        $sid = $req->request->getInt('stationID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        /*
         * var $rentalStation = \Auto\Bundle\ManagerBundle\Entity\RentalStation
         */
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findOneBy(['id'=>$sid,'online'=>1]);

        if(empty($rentalStation)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_STATION,
                'errorMessage' => self::M_NO_STATION,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c')
        ;

        $rentalCars =

            $qb
                ->select('c')
                ->join('c.online','o')
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->andWhere($qb->expr()->eq('o.status', ':status'))
                ->orderBy('c.id', 'DESC')
                ->setParameter('station', $rentalStation)
                ->setParameter('status', 1)
                ->getQuery()
                ->getResult();

        $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_data_normalizer(),
            $rentalCars);

        $rental_able_cars = [];


        foreach($rental_cars as $car){

            if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE){

                $rental_able_cars[] = $car;

            }

        }

        uasort($rental_able_cars,function($a,$b){
            if ($a['mileage'] == $b['mileage']) return 0;
            return ($a['mileage'] > $b['mileage']) ? 1 : -1;
        });


        uasort($rental_able_cars,function($a,$b){
            if ($a['rentalCarDiscount'] == $b['rentalCarDiscount']) return 0;
            return ($a['rentalCarDiscount'] > $b['rentalCarDiscount']) ? 1 : -1;
        });

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'rentalCars'=> array_values($rental_able_cars),
            'page'      => $page,
            'rentalStation' => call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $rentalStation),
            'pageCount' => 1
        ]);

    }

    /**
     * 获取车辆详情
     *
     * @Route("/get", methods="POST",name="auto_api2_get_cars_by_station")
     * @param int stationID Require
     * @param int page
     *
     * @return json
     */

    public function getCarAction(Request $req){

        $sid = $req->request->getInt('stationID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        /*
         * var $rentalStation = \Auto\Bundle\ManagerBundle\Entity\RentalStation
         */
        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->findOneBy(['id'=>$sid,'online'=>1]);

        if(empty($rentalStation)) {
            return new JsonResponse([
                'errorCode' => self::E_NO_STATION,
                'errorMessage' => self::M_NO_STATION,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c')
        ;

        $rentalCars =

            $qb
                ->select('c')
                ->join('c.online','o')
                ->where($qb->expr()->eq('c.rentalStation', ':station'))
                ->andWhere($qb->expr()->eq('o.status', ':status'))
                ->orderBy('c.id', 'DESC')
                ->setParameter('station', $rentalStation)
                ->setParameter('status', 1)
                ->getQuery()
                ->getResult();

        $rental_cars = array_map($this->get('auto_manager.rental_car_helper')->get_rental_car_data_normalizer2(),
            $rentalCars);

        $rental_able_cars = [];


        foreach($rental_cars as $car){

            if($car['status']==\Auto\Bundle\ManagerBundle\Entity\RentalCar::RENTAL_ABLE){

                $rental_able_cars[] = $car;

            }

        }

        uasort($rental_able_cars,function($a,$b){
            if ($a['mileage'] == $b['mileage']) return 0;
            return ($a['mileage'] > $b['mileage']) ? 1 : -1;
        });


        uasort($rental_able_cars,function($a,$b){
            if ($a['rentalCarDiscount'] == $b['rentalCarDiscount']) return 0;
            return ($a['rentalCarDiscount'] > $b['rentalCarDiscount']) ? 1 : -1;
        });

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'rentalCars'=> array_values($rental_able_cars),
            'page'      => $page,
            'rentalStation' => call_user_func($this->get('auto_manager.station_helper')->get_station_data_normalizer(), $rentalStation),
            'pageCount' => 1
        ]);

    }
} 
