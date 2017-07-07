<?php

namespace Auto\Bundle\Api2Bundle\Controller;

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

    /**
     * @Route("/reward/list", methods="POST")
     */
    public function rewardListAction(Request $req)
    {

        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        // 身份验证
        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();


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
        
        $count = empty($invitee_count) ? 0 : count($invitee_count);

        $totalAmount= 0;

        if (!empty($invitee_count)) {

            foreach ($invitee_count as $k => $v) {

                $totalAmount += $v->getRechargeRecord()->getAmount();

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

        // invite => inviterId、 inviteeId => member mobile name


        // recharge_record => member(inviterId) => reward time 




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

        $member = $this->getUser();

        $check_member = $this->checkMember($member);

        if(!empty($check_member)){

            return new JsonResponse($check_member);

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


}
