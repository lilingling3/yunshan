<?php

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayConfig;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayUnifiedOrder;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/vote")
 */
class VoteController extends Controller
{

    /**
     * @Route("/index", methods="POST")
     */
    public function indexAction(Request $req)
    {

        $vote_id = $req->request->getInt('vote');
        $option_id = $req->request->get('option');
        $user = $req->request->get('user');
        /**
         * @var $vote \Auto\Bundle\ManagerBundle\Entity\Vote
         */
        /**
         * @var $vote_option \Auto\Bundle\ManagerBundle\Entity\VoteOptions
         */

        $vote = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Vote')
            ->find($vote_id);

        $vote_option = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:VoteOptions')
            ->find($option_id);

        if(empty($vote)){

            return new JsonResponse([
                'errorCode' =>  -1,
                'errorMessage' =>  $user,
            ]);

        }


        if(!$user){

            return new JsonResponse([
                'errorCode' =>  -1,
                'errorMessage' =>  '无效用户',
            ]);

        }

        if(empty($vote_option)){

            return new JsonResponse([
                'errorCode' =>  -1,
                'errorMessage' =>  '无效投票选项',
            ]);

        }


        if($vote->getCountPrePerson()>0){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:VoteRecords')
                    ->createQueryBuilder('r')
            ;

            $vote_count =
                $qb->select($qb->expr()->count('r'))
                    ->join('r.option', 'o')
                    ->where($qb->expr()->eq('r.wechatId', ':user'))
                    ->andWhere($qb->expr()->eq('o.vote', ':vote'))
                    ->setParameter('user', $user)
                    ->setParameter('vote', $vote)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            if($vote_count>=$vote->getCountPrePerson()){

                return new JsonResponse([
                    'errorCode' =>  -1,
                    'errorMessage' =>  '超出最多投票数量',
                ]);

            }




        }

        if($vote->getCountPreDay()>0){

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:VoteRecords')
                    ->createQueryBuilder('r')
            ;

            $vote_count =
                $qb->select($qb->expr()->count('r'))
                    ->join('r.option', 'o')
                    ->where($qb->expr()->eq('r.wechatId', ':user'))
                    ->andWhere($qb->expr()->eq('o.vote', ':vote'))
                    ->andWhere($qb->expr()->lte('r.createTime', ':ttime'))
                    ->andWhere($qb->expr()->gte('r.createTime',':gtime'))

                    ->setParameter('ttime', (new \DateTime("+1 days"))->format('Y-m-d'))
                    ->setParameter('gtime', date('Y-m-d'))
                    ->setParameter('vote', $vote)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            if($vote_count>=$vote->getCountPreDay()){

                return new JsonResponse([
                    'errorCode' =>  -1,
                    'errorMessage' =>  '超出每天最多投票数量',
                ]);

            }


        }


        if($vote->getCountPreOptionPerson()>0){


            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:VoteRecords')
                    ->createQueryBuilder('r')
            ;

            $vote_count =
                $qb->select($qb->expr()->count('r'))
                    ->join('r.option', 'o')
                    ->where($qb->expr()->eq('r.wechatId', ':user'))
                    ->andWhere($qb->expr()->eq('r.option', ':option'))
                    ->setParameter('option', $vote_option)
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;


            if($vote_count>=$vote->getCountPreOptionPerson()){

                return new JsonResponse([
                    'errorCode' =>  -1,
                    'errorMessage' =>  '您已经投过改选项',
                ]);

            }

        }

        if($vote->getCountPreOptionDay()>0){

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:VoteRecords')
                    ->createQueryBuilder('r')
            ;

            $vote_count =
                $qb->select($qb->expr()->count('r'))
                    ->join('r.option', 'o')
                    ->where($qb->expr()->eq('r.wechatId', ':user'))
                    ->andWhere($qb->expr()->lte('r.createTime', ':ttime'))
                    ->andWhere($qb->expr()->gte('r.createTime',':gtime'))
                    ->andWhere($qb->expr()->eq('r.option', ':option'))
                    ->setParameter('option', $vote_option)
                    ->setParameter('ttime', (new \DateTime("+1 days"))->format('Y-m-d'))
                    ->setParameter('gtime', date('Y-m-d'))
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            if($vote_count>=$vote->getCountPreOptionDay()){

                return new JsonResponse([
                    'errorCode' =>  -1,
                    'errorMessage' =>  '该选项每天最多只能投'.$vote->getCountPreOptionDay().'票',
                ]);

            }

        }

        $vote_record = new \Auto\Bundle\ManagerBundle\Entity\VoteRecords();

        $vote_record->setCreateTime(new \DateTime());
        $vote_record->setOption($vote_option);
        $vote_record->setWechatId($user);

        $man = $this->getDoctrine()->getManager();
        $man->persist($vote_record);
        $man->flush();


        return new JsonResponse([
            'errorCode'    =>  0,
        ]);


    }


    /**
     * @Route("/logo", methods="GET")
     * @Template()
     */
    public function logoAction()
    {
        $tools = new JsApiPay();
        $user = $tools->GetOpenid();

        $vote = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Vote')
            ->find(1);

        $options = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:VoteOptions')
            ->findBy(['vote'=>$vote],['id'=>'asc']);
        return ['options'=>$options,'vote'=>$vote,'user'=>$user];
    }


}
