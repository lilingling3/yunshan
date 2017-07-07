<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/order")
 */
class OrderController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/rental/show/{id}", methods="GET", name="auto_wap_rental_order_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function rentalShowAction(Request $req,$id)
    {
        $coupon_id = $req->query->get('coupon');

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);
        if (empty($order)) {
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => '无效订单!']);
        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);
        if($rentalOrder['status']== \Auto\Bundle\ManagerBundle\Entity\RentalOrder::CANCEL_ORDER){

            return $this->render(
                "AutoWapBundle:Order:cancelRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }


        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER || $rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER){

            $endDate = $order->getCreateTime()->modify('+15 minutes')->format('H:i');
            return $this->render(
                "AutoWapBundle:Order:beginRental.html.twig",
                ['rentalOrder'=>$rentalOrder,'endDate'=>$endDate]  );
        }

        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER){
            return $this->render(
                "AutoWapBundle:Order:successRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }



        $offsetHour = floor((strtotime(date('Y-m-d H:i:s'))-strtotime($order->getCreateTime()->format('Y-m-d
       H:i:s')))
            /3600);

        $orderCost = $this->get('auto_manager.order_helper')->get_rental_order_cost($order);


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');
        $qb =
            $qb->select('c')
            ->join('c.kind','k')
            ->orderBy('k.amount','DESC')
            ->where($qb->expr()->eq('c.member', ':member'))
            ->andWhere($qb->expr()->lte('k.needHour', ':hour'))
            ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
            ->andWhere($qb->expr()->isNull('c.useTime'))
            ->setParameter('member', $this->getUser())
            ->setParameter('hour', $offsetHour)
            ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
            ->setMaxResults(1)

        ;


        if(isset($coupon_id)&&$coupon_id!=0){

            $coupon_status=1;

            $qb
                ->andWhere($qb->expr()->eq('c.id', ':id'))
                ->setParameter('id', $coupon_id);

            $coupon =  $qb
                ->getQuery()
                ->getOneOrNullResult();
            ;

        }elseif(!isset($coupon_id)){
            //默认使用最高金额优惠券
            $coupon_status=0;
            $coupon =  $qb
                ->getQuery()
                ->getOneOrNullResult();
            ;

        }else{
            $coupon_status=-1;
            $coupon = null;
        }

        $endDate = $order->getCreateTime()->modify('+15 minutes')->format('H:i');
        return $this->render(
        "AutoWapBundle:Order:payRental.html.twig",['rentalOrder'=>$rentalOrder,'coupon'=>$coupon,
            'coupon_status'=>$coupon_status,
            'endDate'=>$endDate,'orderCost'=>$orderCost]);

    }


    /**
     * @Route("/change/coupon/{id}", methods="GET", name="auto_wap_rental_order_change_coupon",
     * requirements={"id"="\d+"})
     * @Template()
     */

    public function changeCouponAction($id){

        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);
        if (empty($order)) {
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => '无效订单!']);
        }

        $offsetHour = floor((strtotime($order->getEndTime()->format('Y-m-d
       H:i:s'))-strtotime($order->getCreateTime()->format('Y-m-d
       H:i:s')))
            /3600);


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');
        $coupons =
            $qb->select('c')
                ->join('c.kind','k')
                ->orderBy('k.amount','DESC')
                ->where($qb->expr()->eq('c.member', ':member'))
                ->andWhere($qb->expr()->lte('k.needHour', ':hour'))
                ->andWhere($qb->expr()->gte('c.endTime',':endTime'))
                ->andWhere($qb->expr()->isNull('c.useTime'))
                ->setParameter('member', $this->getUser())
                ->setParameter('hour', $offsetHour)
                ->setParameter('endTime', (new \DateTime())->format('Y-m-d'))
                ->getQuery()
                ->getResult()
        ;

        $coupons = array_map($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
            $coupons);

        return ['coupons'=>$coupons,'order'=>$order];

    }

    /**
     * @Route("/rental/list/{page}", methods="GET", name="auto_wap_rental_order_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */


    public function rentalListAction($page)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');
        $orders =
            new Paginator(
                $qb
                    ->select('o')
                    ->orderBy('o.id', 'DESC')
                    ->where($qb->expr()->eq('o.member', ':member'))
                    ->setParameter('member', $this->getUser())
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );

        return ['rentalOrders' => array_map($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $orders->getIterator()->getArrayCopy()),];

    }


    /**
     * @Route("/rental/back/{id}", methods="GET", name="auto_wap_rental_car_back",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function rentalBackAction($id)
    {

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);

        if (empty($rentalOrder)) {

            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => '无效订单!']);

        }


        return ['rentalOrder' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder)];
    }
    /**
     * @Route("/share/{id}", methods="GET", name="auto_wap_rental_order_share",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function shareOrderAction($id)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();

        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_coupon_order_share'),
            ['userID'=>$this->getUser()->getToken(),'orderID'=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();



        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);

        return ['rentalOrder' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder),'signPackage'=>$signPackage,'data'=>$data,'url'=>$url];
    }

}