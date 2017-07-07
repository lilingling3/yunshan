<?php

namespace Auto\Bundle\Api2Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/car")
 */
class CarController extends BaseController
{
    const PER_PAGE = 20;

    /**
     * @Route("/introduction/list", methods="POST")
     */
    public function introductionListAction(Request $req)
    {

        $base_url = $this->get("auto_manager.curl_helper")->base_url();

        $cars =[[
            'carID' => 2,
            'name'  => "北汽EV系列",
            'url'   =>$base_url.$this->generateUrl('auto_wap_car_introduce_show2',['id'=>2])
        ],
            [
                'carID' => 6,
                'name'  => "奇瑞eQ",
                'url'   =>$base_url.$this->generateUrl('auto_wap_car_introduce_show2',['id'=>6])
            ],
            [
                'carID' => 4,
                'name'  => "江淮iEV5",
                'url'   =>$base_url.$this->generateUrl('auto_wap_car_introduce_show2',['id'=>4])
            ]
            ,
            [
                'carID' => 8,
                'name'  => "吉利帝豪EV",
                'url'   =>$base_url.$this->generateUrl('auto_wap_car_introduce_show2',['id'=>8])
            ]

        ];


        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'introductions'=>$cars
        ]);

    }




}
