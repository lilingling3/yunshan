<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/illegalRecord")
 */
class IllegalRecordController extends BaseController
{
    const PER_PAGE = 20;

    /**
     * @Route("/list", methods="POST", name="auto_api_illegal_record_list")
     */

    public function listAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

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
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('r')
        ;
        $illegalRecords =
            new Paginator(
                $qb
                    ->select('r')
                    ->join('r.order','o')
                    ->orderBy('r.id', 'DESC')
                    ->where($qb->expr()->eq('o.member', ':member'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

        );

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'pageCount'     =>ceil($illegalRecords->count() / self::PER_PAGE),
            'page'          =>$page,
            'illegalRecords'=>array_map($this->get('auto_manager.illegal_record_helper')
                ->get_illegal_record_normalizer(),
                $illegalRecords->getIterator()->getArrayCopy()),
        ]);

    }

    /**
     * @Route("/show", methods="POST",name="auto_api_illegal_record_show")
     */

    public function showAction(Request $req){

        $uid = $req->request->get('userID');
        $illegalId = $req->request->getInt('illegalRecordID');

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
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('r');

        $illegal = $qb
            ->select('r')
            ->join('r.order','o')
            ->where($qb->expr()->eq('o.member', ':member'))
            ->andWhere($qb->expr()->eq('r.id', ':illegalId'))
            ->setParameter('member', $member)
            ->setParameter('illegalId', $illegalId)
            ->getQuery()
            ->getSingleResult()
        ;

        if(empty($illegal)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_ILLEGAL_RECORD,
                'errorMessage' =>  self::M_NO_ILLEGAL_RECORD
            ]);

        }

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'illegalRecord' =>call_user_func($this->get('auto_manager.illegal_record_helper')
                ->get_illegal_record_normalizer(),
                $illegal)

        ]);
    }


}
