<?php
/**
 * Created by sublime.
 * User: luyao
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/deposit")
 */
class DepositController extends Controller {


	const PER_PAGE = 20;

	/**
     * @Route("/list/{page}", methods="GET", name="auto_admin_deposit_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {


        $payTime = $req->query->get('payTime');
        $payMethod = $req->query->getInt('payMethod');
        $mobile  = $req->query->getInt('mobile');

        $qb = 
        	$this
        		->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
                ->select('d')
                ->join('d.member', 'm')
        ;
        	$qb
                ->where($qb->expr()->isNotNull('d.payTime'))
                ->andWhere($qb->expr()->isNull('d.refundTime'))
        ;



        if ($mobile) {
            $qb 
                ->andWhere($qb->expr()->eq('m.mobile',':mobile'))
                ->setParameter('mobile', $mobile)
            ;
        }

        if ($payTime) {
            $qb
                ->andWhere($qb->expr()->eq('d.payTime',':paytime'))
                ->setParameter('paytime', $payTime)
            ;
        }

        
        if ($payMethod == 100) {
            
            // 支付宝
            $qb
                ->andWhere($qb->expr()->isNotNull('d.alipayTradeNo'))
            ;
        }else if($payMethod == 101) {

            // 微信
            $qb
                ->andWhere($qb->expr()->isNotNull('d.wechatTransactionId'))
            ;
        }


        $depositOrders =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $deposit_list = array_map($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
            ,$depositOrders->getIterator()->getArrayCopy());


        $total = ceil(count($depositOrders) / self::PER_PAGE);

        return ['depositOrders'=>$deposit_list,'page'=>$page,'total'=>$total];
    }



    /**
     * @Route("/refundList/{page}", methods="GET", name="auto_admin_deposit_refund_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function refundListAction(Request $req,$page = 1)
    {
        $payTime = $req->query->get('payTime');
        $payMethod = $req->query->getInt('payMethod');
        $mobile  = $req->query->getInt('mobile');

        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
                ->select('d')
                ->join('d.member', 'm')
        ;
            $qb
                ->where($qb->expr()->isNotNull('d.payTime'))
                ->andWhere($qb->expr()->isNotNull('d.refundTime'))
                ->andWhere($qb->expr()->isNull('d.endTime'))
        ;



        if ($mobile) {
            $qb 
                ->andWhere($qb->expr()->eq('m.mobile',':mobile'))
                ->setParameter('mobile', $mobile)
            ;
        }

        if ($payTime) {
            $qb
                ->andWhere($qb->expr()->eq('d.payTime',':paytime'))
                ->setParameter('paytime', $payTime)
            ;
        }

        
        if ($payMethod == 100) {
            
            // 支付宝
            $qb
                ->andWhere($qb->expr()->isNotNull('d.alipayTradeNo'))
            ;
        }else if($payMethod == 101) {

            // 微信
            $qb
                ->andWhere($qb->expr()->isNotNull('d.wechatTransactionId'))
            ;
        }


        $depositOrders =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.createTime', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $deposit_list = array_map($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
            ,$depositOrders->getIterator()->getArrayCopy());


        $total = ceil(count($depositOrders) / self::PER_PAGE);


        return ['depositOrders'=>$deposit_list,'page'=>$page,'total'=>$total];

    }

    /**
     * @Route("/hasrefundList/{page}", methods="GET", name="auto_admin_deposit_has_refund_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function hasRefundListAction(Request $req,$page = 1)
    {
         $payTime = $req->query->get('payTime');
        $payMethod = $req->query->getInt('payMethod');
        $mobile  = $req->query->getInt('mobile');

        $qb = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->createQueryBuilder('d')
                ->select('d')
                ->join('d.member', 'm')
        ;
            $qb
                ->where($qb->expr()->isNotNull('d.payTime'))
                ->andWhere($qb->expr()->isNotNull('d.refundTime'))
                ->andWhere($qb->expr()->isNotNull('d.endTime'))
        ;



        if ($mobile) {
            $qb 
                ->andWhere($qb->expr()->eq('m.mobile',':mobile'))
                ->setParameter('mobile', $mobile)
            ;
        }

        if ($payTime) {
            $qb
                ->andWhere($qb->expr()->eq('d.payTime',':paytime'))
                ->setParameter('paytime', $payTime)
            ;
        }

        
        if ($payMethod == 100) {
            
            // 支付宝
            $qb
                ->andWhere($qb->expr()->isNotNull('d.alipayTradeNo'))
            ;
        }else if($payMethod == 101) {

            // 微信
            $qb
                ->andWhere($qb->expr()->isNotNull('d.wechatTransactionId'))
            ;
        }


        $depositOrders =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $deposit_list = array_map($this->get('auto_manager.deposit_helper')->get_deposit_normalizer()
            ,$depositOrders->getIterator()->getArrayCopy());


        $total = ceil(count($depositOrders) / self::PER_PAGE);
        return ['depositOrders'=>$deposit_list,'page'=>$page,'total'=>$total];

    }


    /**
     * @Route("/refund", methods="POST")
     */
    public function refundDepositAction(Request $req)
    {
        $orderId = $req->request->getInt('OrderId');
        $refundAmount = $req->request->get('refundAmount');

        $order = 
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DepositOrder')
                ->findOneBy(["id" => $orderId]);
        ;

        $order->setEndTime(new \DateTime());
        $order->setActualRefundAmount($refundAmount);

        $man = $this->getDoctrine()->getManager();
        $man->persist($order);
        $man->flush();

        return new JsonResponse([
                'errorCode'    => 0,
                'order'        => $orderId
            ]);

    }


}