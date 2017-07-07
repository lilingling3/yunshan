<?php
namespace Auto\Bundle\Api2Bundle\Controller;
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

    const REDIRECT_TYPE_INNER = 1;  // 内部跳转
    const REDIRECT_TYPE_OUTER = 2;  // 外部跳转

    const REDIRECT_POSITON_DEFAULT  = 100;  // 默认跳转
    const REDIRECT_POSITON_RECHARGE = 101;  // 余额充值
    const REDIRECT_POSITON_DEPOSIT  = 201;  // 押金充值
    const REDIRECT_POSITON_INVITE   = 301;  // 邀请好友
    const REDIRECT_POSITON_COUPON   = 401;  // 优惠券
    const REDIRECT_POSITON_ACCOUNT_AUTH = 501;  // 用户认证
    const REDIRECT_POSITON_ACCOUNT_SESAME_CREDIT = 502;  // 芝麻信用

    /**
     * @Route("/list", methods="POST")
     */

    public function stationRentalCarsAction(Request $req){

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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
     * 活动广告
     *
     * @Route("/ad", methods="POST")
     *
     * @param Request $req
     * @return JsonResponse
     */
    public function adAction(Request $req){

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MarketActivity')
                ->createQueryBuilder('a');

        // 根据startTime 排序
        $activity = $qb
            ->select('a')
            ->where($qb->expr()->gt('a.endTime',':time'))
            ->andWhere($qb->expr()->lte('a.startTime',':time'))
            ->andWhere($qb->expr()->isNotNull('a.image'))
            ->setParameter('time',new \DateTime())
            ->orderBy('a.startTime','DESC')
            ->getQuery()
            ->getResult();

//        'adImage'=> $base_url.$this->renderView('{{ localname|photograph }}',['localname' =>
//            $activity->getImage()]),
//            'link'  =>$activity->getLink(),
//            'title'  =>$activity->getTitle()
         //      'redirecttype' '1 2' 1 inner 2 outer

           //    redirectpos 1 2 3 4 5 6677

        // 存在活动
        if(!empty($activity)){

            $base_url = $this->get("auto_manager.curl_helper")->base_url();

            return new JsonResponse([
                'errorCode' => self::E_OK,
                'list' => 1
            ]);
        }else{

            return new JsonResponse([
                'errorCode'    => self::E_NO_AD,
                'errorMessage' => self::M_NO_AD,
            ]);
        }

    }



    /**
     * @Route("/options", methods="POST",name="auto_api_2_message_options")
     */

    public function optionsAction(Request $req){

        $cancel_order_options = [
            ['id'=>"1",'name'=>"车辆破损"],
            ['id'=>"2",'name'=>"外观过脏"],
            ['id'=>"3",'name'=>"行程有变"],
            ['id'=>"4",'name'=>"车门打不开"],
            ['id'=>"5",'name'=>"其他"],
        ];

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
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
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
            'rechargeActivity'=> $activity_name ,
        ]);


    }

}