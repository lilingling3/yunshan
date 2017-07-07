<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/12/9
 * Time: 下午6:26
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
 * @Route("/message")
 */

class MessageController extends BaseController {

    const PER_PAGE = 10;

    /**
     * @Route("/list", methods="POST")
     */

    public function stationRentalCarsAction(Request $req){

        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){
            return new JsonResponse([
                'errorCode' =>  self::E_NO_MEMBER,
                'errorMessage' =>self::M_NO_MEMBER,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Message')
                ->createQueryBuilder('m')
        ;
        $messages =
            new Paginator(
                $qb
                    ->select('m')
                    ->where($qb->expr()->eq('m.member', ':member'))
                    ->orderBy('m.id', 'DESC')
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($messages) / self::PER_PAGE);

        $message_list = array_map($this->get('auto_manager.message_helper')->get_message_normalizer(),
            $messages->getIterator()->getArrayCopy());

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Message')
                ->createQueryBuilder('m')
        ;

        $qb
            ->update('Auto\Bundle\ManagerBundle\Entity\Message','m')
            ->set('m.status', 1001)
            ->where($qb->expr()->eq('m.member', ':member'))
            ->andWhere($qb->expr()->eq('m.status', ':status'))
            ->setParameter('status', 1000)
            ->setParameter('member', $member)
            ->getQuery()
            ->execute()
        ;

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'messages'=> $message_list,
            'page'      =>$page,
            'pageCount' =>$total
        ]);
    }

    /**
     * @Route("/ad", methods="POST")
     */

    public function adAction(Request $req){

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MarketActivity')
                ->createQueryBuilder('a');

        $activity = $qb
            ->select('a')
            ->where($qb->expr()->gt('a.endTime',':time'))
            ->andWhere($qb->expr()->lte('a.startTime',':time'))
            ->setParameter('time',new \DateTime())
            ->orderBy('a.id','DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();


        if(!empty($activity)&&$activity->getImage()){
            $base_url = $this->get("auto_manager.curl_helper")->base_url();

            return new JsonResponse([
                'errorCode' =>  self::E_OK,
                'adImage'=> $base_url.$this->renderView('{{ localname|photograph }}',['localname' =>
                        $activity->getImage()]),
                'link'  =>$activity->getLink(),
                'title'  =>$activity->getTitle()
            ]);

        }else{

            return new JsonResponse([
                'errorCode' =>  self::E_NO_AD,
                'errorMessage' =>  self::M_NO_AD,
            ]);

        }

    }

    /**
     * @Route("/version", methods="POST")
     */

    public function versionAction(Request $req){

        $platform = $req->request->getInt('platform');

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'version'=> '2.0',
            'message'  =>'',
            'force'  =>1
        ]);


    }


    /**
     * @Route("/options", methods="POST",name="auto_api_message_options")
     */

    public function optionsAction(Request $req){

        $cancel_order_options = [
            ['id'=>"1",'name'=>"车辆破损"],
            ['id'=>"2",'name'=>"外观过脏"],
            ['id'=>"3",'name'=>"行程有变"],
            ['id'=>"4",'name'=>"车门打不开"],
            ['id'=>"5",'name'=>"其他"],
        ];

//        $qb =
//            $this
//                ->getDoctrine()
//                ->getRepository('AutoManagerBundle:RechargeActivity')
//                ->createQueryBuilder('a');
//
//        $activity =
//            $qb
//                ->select('a')
//                ->andWhere($qb->expr()->lte('a.startTime',':time'))
//                ->andWhere($qb->expr()->gte('a.endTime',':time'))
//                ->setParameter('time', (new \DateTime()))
//                ->setMaxResults(1)
//                ->getQuery()
//                ->getOneOrNullResult();
//        ;


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeActivity')
                ->createQueryBuilder('a');

        $activity =
            $qb
                ->select('a')
                ->andWhere($qb->expr()->lte('a.startTime',':time'))
                ->andWhere($qb->expr()->gte('a.endTime',':time'))
                ->setParameter('time', (new \DateTime()))
                ->getQuery()
                ->getResult();
        ;


        $activity_first_recharge = 'start';

        if(!empty($activity) && $activity_first_recharge == 'start'){

            $activity_name = '多充多得';

        }else if(!empty($activity) && $activity_first_recharge == 'end'){

            $activity_name = '多充多得';

        }else if(empty($activity) && $activity_first_recharge == 'start'){

            $activity_name = '首充送现';

        }else{

            $activity_name = '';

        }

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'cancelOrderOptions'=> $cancel_order_options,
//            'rechargeActivity'=>empty($activity)?'':$activity->getName()
            'rechargeActivity'=> $activity_name ,
//            'rechargeActivity'=> '' ,
        ]);


    }

}