<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/3/7
 * Time: 下午2:01
 */

namespace Auto\Bundle\ApiBundle\Controller;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/remind")
 */

class RemindController extends BaseController {


    /**
     * @Route("/rentalStation/list", methods="POST")
     */
    public function rentalStationListAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $station_id = $req->request->getInt('rentalStationID');

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
                ->getRepository('AutoManagerBundle:Remind')
                ->createQueryBuilder('r');

        $remind = $qb
            ->select('r')
            ->where($qb->expr()->eq('r.member', ':member'))
            ->andWhere($qb->expr()->isNull('r.remindTime'))
            ->andWhere($qb->expr()->gt('r.endTime',':time'))
            ->andWhere($qb->expr()->lt('r.createTime',':time'))
            ->setParameter('member', $member)
            ->setParameter('time',new \DateTime())
            ->orderBy('r.id','DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        ;
        if(empty($remind)){
            return  new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'remind'=>null,
            ]);


        }


        if($station_id&&$remind->getRentalStation()->getId()==$station_id){

            $remind->setEndTime(new DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($remind);
            $man->flush();

            return  new JsonResponse([
                'errorCode'    =>  self::E_OK,
                'remind'=>null,
            ]);

        }



        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'remind'=>[
                'rentalStationID'=>$remind->getRentalStation()->getId(),
                'endTime'=>$remind->getEndTime()->format('H:i'),
                'remindID'=>$remind->getId()
            ],

        ]);
        
    }

    /**
     * @Route("/rentalStation/add", methods="POST")
     */
    public function rentalStationAddAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $rental_station_id = $req->request->getInt('rentalStationID');
        $minute = $req->request->getInt('minute');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }

        $rentalStation = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalStation')
            ->find($rental_station_id);

        if(empty($rentalStation)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_STATION,
                'errorMessage' =>self::M_NO_STATION,
            ]);
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Remind')
                ->createQueryBuilder('r')
        ;

        $reminds =

            $qb
                ->select('r')
                ->set('r.endTime', (new \DateTime())->getTimezone())
                ->where($qb->expr()->eq('r.member', ':member'))
                ->andWhere($qb->expr()->gt('r.endTime',':time'))
                ->andWhere($qb->expr()->lt('r.createTime',':time'))
                ->setParameter('member', $member)
                ->setParameter('time',new \DateTime())
                ->getQuery()
                ->execute()
            ;

        $man = $this->getDoctrine()->getManager();

        foreach($reminds as $r){

            $r->setEndTime(new \DateTime());
            $man->persist($r);
            $man->flush();

        }


        $remind = new \Auto\Bundle\ManagerBundle\Entity\Remind();
        $remind->setRentalStation($rentalStation);
        $remind->setMember($member);
        $remind->setCreateTime(new \DateTime());
        $remind->setEndTime((new DateTime())->modify("+".$minute." minutes"));

        $man->persist($remind);
        $man->flush();

        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
            'remind'=>[
                'rentalStationID'=>$remind->getRentalStation()->getId(),
                'endTime'=>$remind->getEndTime()->format('H:i'),
                'remindID'=>$remind->getId()
            ],

        ]);

    }

    /**
     * @Route("/rentalStation/cancel", methods="POST")
     */
    public function rentalStationCancelAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $remind_id = $req->request->getInt('remindID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }


        $remind = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Remind')
            ->findOneBy(['member'=>$member,'id'=>$remind_id]);

        if(empty($remind)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_REMIND,
                'errorMessage' =>self::M_NO_REMIND,
            ]);
        }

        if($remind->getEndTime()>(new DateTime())){

            $remind->setEndTime(new DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($remind);
            $man->flush();

        }

        return  new JsonResponse([
            'errorCode'    =>  self::E_OK,
        ]);

    }
}