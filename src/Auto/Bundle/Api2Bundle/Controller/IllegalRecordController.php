<?php

namespace Auto\Bundle\Api2Bundle\Controller;

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
     * @Route("/list", methods="POST", name="auto_api_2_illegal_record_list")
     */

    public function listAction(Request $req)
    {
        $page = $req->request->getInt('page')?$req->request->getInt('page'):1;

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();

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
     * @Route("/show", methods="POST",name="auto_api_2_illegal_record_show")
     */

    public function showAction(Request $req){

        $illegalId = $req->request->getInt('illegalRecordID');

        $check_member = $this->checkMember($this->getUser());

        if(!empty($check_member)){

            return new JsonResponse($check_member);

        }

        $member = $this->getUser();


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
