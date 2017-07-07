<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Auto\Bundle\ManagerBundle\Entity\Invoice;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\InvoiceType;

/**
 * @Route("/invoice")
 */
class InvoiceController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_invoice_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Invoice')
                    ->createQueryBuilder('c')

            ;
        $invoices =
                new Paginator(
                    $qb
                        ->select('c')
                        ->where($qb->expr()->isNull('c.authTime') )
                        ->orderBy('c.createTime', 'ASC')
                        ->setMaxResults(self::PER_PAGE)
                        ->setFirstResult(self::PER_PAGE * ($page - 1))
                );

        $total = ceil(count($invoices) / self::PER_PAGE);

        return ['invoices'=>$invoices,'page'=>$page,'total'=>$total];
    }


    /**
     * @Route("/invoicedList/{page}", methods="GET", name="auto_admin_invoice_invoicedList",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function invoicedListAction(Request $req,$page = 1)
    {
        $status = $req->query->getInt('status');
        $mobile = $req->query->getInt('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Invoice')
                ->createQueryBuilder('c')
        ;

        if($status){
            if($status==1){
                $qb
                    ->andWhere( $qb->expr()->isNotNull('c.deliveryTime') );
                echo $status;
            }
            if($status==2){
                $qb
                    ->andWhere( $qb->expr()->isNull('c.deliveryTime'));
            }
        }
        if($mobile){
            $qb->join('c.applyMember','m')
                ->andWhere( $qb->expr()->eq('m.mobile',':mobile') )
                ->setParameter('mobile', $mobile);
        }

        $invoices =
            new Paginator(
                $qb
                    ->select('c')
                    ->andWhere($qb->expr()->isNotNull('c.authTime') )
                    ->orderBy('c.authTime', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );


        $total = ceil(count($invoices) / self::PER_PAGE);

        return ['invoices'=>$invoices,'page'=>$page,'total'=>$total];
    }


    /**
     * @Route("/verify/{id}", methods="GET", name="auto_admin_invoice_verify",requirements={"id"="\d+"})
     * @Template()
     */
    public function verifyAction($id)
    {

        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->findOneBy(['id'=>$id]);
        if($invoice){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Invoice')
                    ->createQueryBuilder('i');

            //已开票金额（元）
            $invoice_amount =
                $qb
                    ->select('sum(i.amount)')
                    ->where($qb->expr()->eq('i.applyMember', ':member'))
                    ->andWhere($qb->expr()->isNotNull('i.authTime'))
                    ->setParameter('member', $invoice->getApplyMember()->getId())
                    ->getQuery()
                    ->getSingleScalarResult();

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Invoice')
                    ->createQueryBuilder('i');
            //已开票张数（张）
            $invoiced_count =
                $qb
                    ->select('count(i)')
                    ->where($qb->expr()->eq('i.applyMember', ':member'))
                    ->andWhere($qb->expr()->isNotNull('i.authTime'))
                    ->setParameter('member', $invoice->getApplyMember()->getId())
                    ->getQuery()
                    ->getSingleScalarResult()
            ;
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Invoice')
                    ->createQueryBuilder('i');
            //待开票张数（张）
            $invoice_waiting =
                $qb
                    ->select('count(i)')
                    ->where($qb->expr()->eq('i.applyMember', ':member'))
                    ->andWhere($qb->expr()->isNull('i.authTime'))
                    ->setParameter('member', $invoice->getApplyMember()->getId())
                    ->getQuery()
                    ->getSingleScalarResult()
            ;

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:BaseOrder')
                    ->createQueryBuilder('o');
            //订单消费（元）
            $order_amount = $qb
                ->select('sum(o.amount)')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->setParameter('member', $invoice->getApplyMember()->getId())
                ->getQuery()
                ->getSingleScalarResult();
            //充值金额（元）
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RechargeOrder')
                    ->createQueryBuilder('o');

            $recharge_amount = $qb
                ->select('sum(o.actualAmount)')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->andWhere($qb->expr()->isNotNull('o.payTime'))
                ->setParameter('member', $invoice->getApplyMember()->getId())
                ->getQuery()
                ->getSingleScalarResult();

        }else{
            $invoice_amount=null;
            $order_amount=null;
            $invoiced_count=null;
            $invoice_waiting=null;
        }

        return ['invoice'=>$invoice,'invoice_amount'=>$invoice_amount,'order_amount'=>$order_amount+$recharge_amount-$invoice_amount,'invoiced_count'=>$invoiced_count,'invoice_waiting'=> $invoice_waiting];
    }

    /**
     * @Route("/update", methods="GET", name="auto_admin_invoice_update")
     * @Template()
     */
    public function updateAction(Request $req)
    {

        $id = $req->query->getInt('id');
        $memberId=$req->query->getInt('member');

        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->findOneBy(['id'=>$id]);
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['id'=>$memberId]);

        if(empty($invoice)){

            return new JsonResponse([
                'errorCode'    =>  0,
                'errorMessage' =>  '没有发票'
            ]);

        }



        $invoice->setAuthTime(new \DateTime())
                ->setAuthMember($member);


        $man = $this->getDoctrine()->getManager();
        $man->persist($invoice);
        $man->flush();

        return new JsonResponse([
            'errorCode'     => 1
        ]);
    }


    /**
     * @Route("/show{id}", methods="GET", name="auto_admin_invoice_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction( $id)
    {
        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->find($id);

        return ['invoice'  => $invoice];
    }

    /**
     * @Route("/accountShow{id}/{page}", methods="Get", name="auto_admin_invoice_account_show",requirements={"id"="\d+"})
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function accountShowAction(Request $req,$id,$page = 1)
    {
        $invoiceid = $req->query->getInt('invoiceid');
        $member= $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['id'=>$id]);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('c');
        $order =
            new Paginator(
                $qb
                    ->select('c')
                    ->andWhere( $qb->expr()->isNotNull('c.payTime') )
                    ->andWhere( $qb->expr()->eq('c.member',':member') )
                    ->setParameter('member', $id)
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($order) / self::PER_PAGE);

        return ['orders'=>$order,'page'=>$page,'total'=>$total,'member'=>$member,'invoiceid'=>$invoiceid];
    }

    /**
     * @Route("/accountRechargeShow{id}/{page}", methods="Get", name="auto_admin_invoice_account_recharge_show",requirements={"id"="\d+"})
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function accountRechargeShowAction(Request $req,$id,$page = 1)
    {
        $invoiceid = $req->query->getInt('invoiceid');
        $member= $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['id'=>$id]);
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->createQueryBuilder('c');
        $order =
            new Paginator(
                $qb
                    ->select('c')
                    ->andWhere( $qb->expr()->isNotNull('c.payTime') )
                    ->andWhere( $qb->expr()->eq('c.member',':member') )
                    ->setParameter('member', $id)
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($order) / self::PER_PAGE);

        return ['orders'=>$order,'page'=>$page,'total'=>$total,'member'=>$member,'invoiceid'=>$invoiceid];
    }

    /**
     * @Route("/delivery{id}", methods="GET", name="auto_admin_invoice_delivery",requirements={"id"="\d+"})
     * @Template()
     */
    public function deliveryAction( $id)
    {
        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->find($id);
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->findBy(['kind'=>4]);

        return ['invoice'  => $invoice, 'company' => $company];
    }
    /**
     * @Route("/deliveryUpdate", methods="GET", name="auto_admin_invoice_delivery_update")
     * @Template()
     */
    public function deliveryUpdateAction(Request $req)
    {
        $id = $req->query->getInt('id');
        $memberId=$req->query->getInt('member');
        $deliveryCompanyId=$req->query->getInt('deliveryCompany');
        $deliveryNumber = $req->query->get('deliveryNumber');
        $invoice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Invoice')
            ->findOneBy(['id'=>$id]);
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['id'=>$memberId]);
        $deliveryCompany = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->findOneBy(['id'=>$deliveryCompanyId]);


        if(empty($invoice)){
            return new JsonResponse([
                'errorCode'    =>  0,
                'errorMessage' =>  'error'
            ]);
        }

        $invoice->setDeliveryTime(new \DateTime())
                ->setDeliveryMember($member)
                ->setDeliveryCompany($deliveryCompany)
                ->setDeliveryNumber($deliveryNumber);


        $man = $this->getDoctrine()->getManager();
        $man->persist($invoice);
        $man->flush();

        return new JsonResponse([
            'errorCode'     => 1
        ]);

    }
}