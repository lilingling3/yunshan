<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/invite")
 */
class InviteController extends BaseController
{
    const PER_PAGE = 20;

    const INVITE_REWARD_CASH = 5;

    /**
     * @Route("/addInvite", methods="POST", name="auto_api_invite_add_relationship")
     */
    public function addInviteAction(Request $req)
    {

        $userid = $req->request->get('userID');
        $uid    = $req->request->get('token');
        $channelType = $req->request->get('channel');
        $mobile = $req->request->getInt('mobile');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['id'=>$userid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    => self::E_NO_MEMBER,
                'errorMessage' => self::M_NO_MEMBER
            ]);
        }

        if (md5($member->getToken()) != $uid) {

            // 请检查分享链接
            return new JsonResponse([
                'errorCode'    => self::E_UNSAFE_LINK,
                'errorMessage' => self::M_UNSAFE_LINK,
                // 'uid'          => $uid,
                // 'token'        => md5($member->getToken())
            ]);
        }

        // 电话号码验证
        if(!preg_match('/^1[34578]{1}\d{9}$/',$mobile)){

            return new JsonResponse([
                'errorCode'    =>  self::E_WRONG_MOBILE,
                'errorMessage' =>  self::M_WRONG_MOBILE
            ]);
        }

        // 自邀
        if ($mobile == $member->getMobile()) {

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_INVITE_YOURSELF,
                'errorMessage' =>  self::M_NO_INVITE_YOURSELF
            ]);            
        }

        // 互邀
        
        $invitee = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        if ($invitee) {
            
            
            $inviteRelation = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Invite')
                ->findOneBy(['inviteeMobile'=>$member->getMobile(),'inviter'=>$invitee]);

            if ($inviteRelation) {

                return new JsonResponse([
                    'errorCode'    =>  self::E_NO_VALID_INVITION,
                    'errorMessage' =>  self::M_NO_VALID_INVITION
                ]);
            }
        }


        // 被邀请号码是否被邀请过

        $relation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invite')
            ->findOneBy(['inviteeMobile'=>$mobile]);

        if (empty($relation)) {

            $invition = new \Auto\Bundle\ManagerBundle\Entity\Invite();
            $invition->setInviter($member);
            $invition->setInviteeMobile($mobile);

            $channel = 0;
            if ($channelType == "qq") {

                $channel = \Auto\Bundle\ManagerBundle\Entity\Invite::WE_CHAT;
            } elseif ($channelType == "wechat") {
                
                $channel = \Auto\Bundle\ManagerBundle\Entity\Invite::TENCENT;
            } elseif ($channelType == "weibo") {
                
                $channel = \Auto\Bundle\ManagerBundle\Entity\Invite::WEIBO;
            } else {
                $channel = \Auto\Bundle\ManagerBundle\Entity\Invite::DEFAULT_CH;
            }

            $invition->setChannel($channel);

            // 发优惠券
            // 第一次邀请给券
            $relationShip = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Invite')
                ->findOneBy(['inviter'=>$member]);

            if (empty($relationShip)) {
                
                $relationShip = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:InviteActivity')
                    ->findAll();

                if (!empty($relationShip)) {
                    
                    $activity = array_shift($relationShip);

                    // 发券了  
                    $this->get('auto_manager.coupon_helper')->send_coupon($member,$activity->getKind());

                }
            }

            $man = $this->getDoctrine()->getManager();
            $man->persist($invition);
            $man->flush();
        } else {

            // 已经被邀请过一次，关系已经建立了
            return new JsonResponse([
                'errorCode'    =>  self::E_MOBILE_HAS_INVITED,
                'errorMessage' =>  self::M_MOBILE_HAS_INVITED
            ]);
        }


        return new JsonResponse([
            'errorCode' => self::E_OK,

        ]);

    }

    /**
     * @Route("/reward/list", methods="POST")
     */
    public function rewardListAction(Request $req)
    {

        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page');
        // $uid    = $req->request->get('token');
        // $channelType = $req->request->get('channel');
        // $mobile = $req->request->getInt('mobile');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InviteReward')
                ->createQueryBuilder('i')
        ;

        $invitee_count =
            $qb->select('i')
                ->join('i.relative','r')
                ->andWhere($qb->expr()->eq('r.inviter', ':member'))
                ->setParameter('member', $member)
                // ->where($qb->expr()->eq('i.id', ':member'))
                // ->setParameter('member', 7)
                ->getQuery()
                ->getResult()
                // ->getSingleScalarResult()
        ;
        

// var_dump($invitee_count);exit;


        $count = empty($invitee_count) ? 0 : count($invitee_count);

        $totalAmount= 0;

        if (!empty($invitee_count)) {

            foreach ($invitee_count as $k => $v) {

                // var_dump($v);exit;
                $totalAmount += $v->getRechargeRecord()->getAmount();//
                // var_dump($totalAmount);exit;
            }
        }
        




        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InviteReward')
                ->createQueryBuilder('i')
                ->select('i')
                ->join('i.relative','r')
                ->andWhere($qb->expr()->eq('r.inviter', ':member'))
                ->setParameter('member', $member)
        ;


        $rewardList =
            new Paginator(

                $qb
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        $list = [];

        if ($rewardList) {
            
            $list = array_map($this->get("auto_manager.invite_helper")->get_invite_normalizer(),
                $rewardList->getIterator()->getArrayCopy());

        }


        return new JsonResponse([
            'errorCode' => self::E_OK,
            'pageCount' => ceil($rewardList->count() / self::PER_PAGE),
            'page'      => $page,
            'list'      => $list,
            'inviteNum' => $count,
            'totalAmount' => $totalAmount
        ]);

    }


    /**
     * @Route("/share/content", methods="POST")
     */
    public function getShareContentAction(Request $req)
    {

        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

        $shareTitle = "驾呗，让出行更自由！";

        $shareContent = "注册即获218元优惠大礼包，邀您一起来感受最实惠的出行之旅。";

        $host = "https://go.win-sky.com.cn";

        $link = $host . "/mobile/activity/invite.html?id=" . (string)$member->getId() . "&token=". (string)md5($member->getToken()) . "&channel=";



        return new JsonResponse([
            'errorCode' => self::E_OK,
            'title' => $shareTitle,
            'content' => $shareContent,
            'link' => $link

        ]);

    }


    /**
     * @Route("/share/detail", methods="POST",name="auto_api_invite_share_detail")
     */
    public function getShareDetailAction(Request $req)
    {

        $uid = $req->request->get('userID');


        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InviteReward')
                ->createQueryBuilder('i')
        ;

        $invitee_count =
            $qb->select('i')
                ->join('i.relative','r')
                ->andWhere($qb->expr()->eq('r.inviter', ':member'))
                ->setParameter('member', $member)
                ->getQuery()
                ->getResult()
        ;

        $count = empty($invitee_count) ? 0 : count($invitee_count);

        $totalAmount= 0;

        if (!empty($invitee_count)) {

            foreach ($invitee_count as $k => $v) {

                // var_dump($v);exit;
                $totalAmount += $v->getRechargeRecord()->getAmount();//
                // var_dump($totalAmount);exit;
            }
        }

        $total = 0;

        $amount = 
            $this->getDoctrine()
                ->getRepository('AutoManagerBundle:InviteActivity')
                ->findOneBy(['id'=>1]);

        if ($amount) {
            
            $total = $amount->getCashBack() ?  $amount->getCashBack(): 0;
        }

        return new JsonResponse([
            'errorCode' => self::E_OK,
            'amount' => $total,
            'inviteNum' => $count,
            'totalAmount' => $totalAmount

        ]);

    }

}
