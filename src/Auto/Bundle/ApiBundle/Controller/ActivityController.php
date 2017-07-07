<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/3/25
 * Time: 上午10:30
 */

namespace Auto\Bundle\ApiBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;

/**
 * @Route("/activity")
 */
class ActivityController extends BaseController {

    /**
     * @Route("/list", methods="POST")
     */

    public function listAction(Request $req){

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MarketActivity')
                ->createQueryBuilder('a');

        $activities = $qb
            ->select('a')
            ->where($qb->expr()->gt('a.endTime',':time'))
            ->andWhere($qb->expr()->lte('a.startTime',':time'))
            ->setParameter('time',new \DateTime())
            ->orderBy('a.startTime','DESC')
            ->getQuery()
            ->getResult();

        if(empty($activities)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_AD,
                'errorMessage' =>  self::M_NO_AD,

            ]);
        }

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'activities' => array_map($this->get('auto_manager.market_activity_helper')
                ->get_market_activity_normalizer()
                , $activities),
        ]);

    }


    /**
     * @Route("/feng", methods="POST")
     */


    public function fengAction(Request $req){

        $name = iconv( "UTF-8", "gb2312" , $req->request->get('name'));;
        $mobile = $req->request->get('mobile');
        $car = iconv( "UTF-8", "gb2312" , $req->request->get('car'));
        $province = iconv( "UTF-8", "gb2312" , $req->request->get('province'));
        $city = iconv( "UTF-8", "gb2312" , $req->request->get('city'));
        $time = iconv( "UTF-8", "gb2312" , $req->request->get('time'));
        $dealer = iconv( "UTF-8", "gb2312" , $req->request->get('dealer'));
        $clock = iconv( "UTF-8", "gb2312" , $req->request->get('clock'));




        $data = ["IND_NAME"=>$name,"IND_CELLPHONE_1"=>$mobile,"IND_FIELDS_20"=>$car,"IND_DEALER_PROVINCE"=>$province,"IND_DEALER_CITY"=>$city,"IND_PURPOSE_DEALER"=>$dealer,"IRF_PLAN_PUR_DATE"=>$time,"IRF_FIELDS_30"=>$clock,"cac_uid"=>2188,"Key"=>"audilead"];

        $this->get("auto_manager.curl_helper")->do_post("http://leads.audi.cn/exdata/accept.aspx",$data);

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
        ]);
    }

    /**
     * @Route("/validate", methods="POST")
     */

    public function validateAction(Request $req){
        $startime = $req->request->get('startime');
        $endtime = $req->request->get('endtime');
        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'startime' =>  $startime,
            'endtime'=>$endtime
        ]);
    }
}