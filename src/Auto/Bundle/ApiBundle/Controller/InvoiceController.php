<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/4/27
 * Time: 下午3:57
 */

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/invoice")
 */
class InvoiceController extends BaseController
{
    const PER_PAGE = 20;
    /**
     * @Route("/list", methods="POST")
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
                ->getRepository('AutoManagerBundle:Invoice')
                ->createQueryBuilder('i')
        ;
        $invoices =
            new Paginator(
                $qb
                    ->select('i')
                    ->orderBy('i.id', 'DESC')
                    ->where($qb->expr()->eq('i.applyMember', ':member'))
                    ->setParameter('member', $member)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'pageCount'     =>ceil($invoices->count() / self::PER_PAGE),
            'page'          =>$page,
            'invoices'=>array_map($this->get('auto_manager.invoice_helper')->get_invoice_normalizer(),$invoices->getIterator()->getArrayCopy()),
        ]);

    }

    /**
     * @Route("/show", methods="POST")
     */

    public function showAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $invoice_id = $req->request->getInt('invoiceID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);


        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->findOneBy(['applyMember'=>$member,'id'=>$invoice_id]);

        if(empty($invoice)){
            return new JsonResponse([
                'errorCode'     =>  self::E_NO_INVOICE,
                'errorMessage'  =>  self::M_NO_INVOICE,
            ]);
        }

        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'invoice'=>call_user_func($this->get('auto_manager.invoice_helper')->get_invoice_normalizer(),$invoice),
        ]);

    }


    /**
     * @Route("/quota", methods="POST")
     */


    public function quotaAction(Request $req){

        $uid = $req->request->get('userID');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Invoice')
                ->createQueryBuilder('i');


        $invoice_amount =

            $qb
            ->select('sum(i.amount)')
            ->where($qb->expr()->eq('i.applyMember', ':member'))
            ->setParameter('member', $member)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BaseOrder')
                ->createQueryBuilder('o');

        $order_amount = $qb
            ->select('sum(o.amount)')
            ->where($qb->expr()->eq('o.member', ':member'))
            ->setParameter('member', $member)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->createQueryBuilder('o');

        $recharge_amount = $qb
            ->select('sum(o.actualAmount)')
            ->where($qb->expr()->eq('o.member', ':member'))
            ->andWhere($qb->expr()->isNotNull('o.payTime'))
            ->setParameter('member', $member)
            ->getQuery()
            ->getSingleScalarResult()
        ;


        return new JsonResponse([
            'errorCode'     =>  self::E_OK,
            'maxAmount'=>$order_amount + $recharge_amount - $invoice_amount,
            'serviceName'=>'车辆租赁服务',
            'orderAmount'=>$order_amount,
            'rechargeAmount'=>$recharge_amount,
            'invoiceAmount'=>$invoice_amount


        ]);

    }

    /**
     * @Route("/add", methods="POST")
     */

    public function addAction(Request $req)
    {
        $uid = $req->request->get('userID');
        $amount = $req->request->get('amount');
        $name = $req->request->get('name');
        $address = $req->request->get('address');
        $mobile = $req->request->get('mobile');
        $title = $req->request->get('title');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['token'=>$uid]);

        if(empty($member)){

            return new JsonResponse([
                'errorCode'    =>  self::E_NO_MEMBER,
                'errorMessage' =>  self::M_NO_MEMBER
            ]);

        }


        $invoice = new \Auto\Bundle\ManagerBundle\Entity\Invoice();
        $invoice->setApplyMember($member)
                ->setAmount($amount)
                ->setDeliveryName($name)
                ->setDeliveryAddress($address)
                ->setDeliveryMobile($mobile)
                ->setTitle($title)
            ;

        $man = $this->getDoctrine()->getManager();
        $man->persist($invoice);
        $man->flush();

        return new JsonResponse([
            'errorCode'     =>  self::E_OK
        ]);

    }



}