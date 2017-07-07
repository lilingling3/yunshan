<?php

namespace Auto\Bundle\ApiBundle\Controller;

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

        $cars =[[
            'carID' => 2,
            'name'  => "北汽EV系列",
            'url'   =>$this->generateUrl('auto_wap_car_introduce_show',['id'=>2])
        ],
        [
            'carID' => 6,
            'name'  => "奇瑞eQ",
            'url'   =>$this->generateUrl('auto_wap_car_introduce_show',['id'=>6])
        ],
        [
            'carID' => 4,
            'name'  => "江淮iEV5",
            'url'   =>$this->generateUrl('auto_wap_car_introduce_show',['id'=>4])
        ]
        ,
        [
            'carID' => 8,
            'name'  => "吉利帝豪EV",
            'url'   =>$this->generateUrl('auto_wap_car_introduce_show',['id'=>8])
        ]

        ];


        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
            'introductions'=>$cars
        ]);

    }

    /**
     * @Route("/ecolink", methods="POST")
     */
    public function ecoLinkAction(Request $req)
    {

        $name = $req->request->get('name');
        $mobile = $req->request->get('mobile');
        $email = $req->request->get('email');
        $job = $req->request->get('job');
        $company = $req->request->get('company');

        $arr = ['name'=>$name,'mobile'=>$mobile,'email'=>$email,'job'=>$job,'company'=>$company];

        $redis = $this->container->get('snc_redis.default');
        $redis_cmd= $redis->createCommand('LPUSH',array('echo-link-register',json_encode($arr)));
        $box_json = $redis->executeCommand($redis_cmd);

        return new JsonResponse([
            'errorCode'  =>  self::E_OK,
        ]);



    }



}
