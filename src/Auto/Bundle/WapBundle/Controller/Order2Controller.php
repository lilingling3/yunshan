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
 * @Route("/order2")
 */
class Order2Controller extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/rental/show/{id}", methods="GET", name="auto_wap_rental_order_show2",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function rentalShowAction( $id )
    {

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
                "AutoWapBundle:Order2:cancelRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }


        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER || $rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER){

            return $this->render(
                "AutoWapBundle:Order2:beginRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }

        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER){
            return $this->render(
                "AutoWapBundle:Order2:successRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_order_settle_rental_order'),
            ['userID'=>$this->getUser()->getToken(),"orderID"=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return $this->render(
            "AutoWapBundle:Order2:payRental.html.twig",
            ['rentalOrder'=>$rentalOrder,'maxcoupon'=>$data["coupon"],"maxWalletAmount"=>$data["maxWalletAmount"]]);

    }


    /**
     * @Route("/change/coupon/{id}", methods="GET", name="auto_wap_rental_order_change_coupon2",
     * requirements={"id"="\d+"})
     * @Template()
     */

    public function changeCouponAction(Request $req,$id){
        $couponId = $req->query->get('couponId');
    
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

        return ['coupons'=>$coupons,'order'=>$order,"couponId"=>$couponId];

    }

    /**
     * @Route("/rental/list/{page}", methods="GET", name="auto_wap_rental_order_list2",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */


    public function listAction($page)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_order_list'),
            ['userID'=>$this->getUser()->getToken(),'page'=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rentalOrders' => $data["orders"],
            "pageCount"=>$data["orderCount"],
            "page"=>$page,
        ];

    }


    /**
     * @Route("/rental/back/{id}", methods="GET", name="auto_wap_rental_car_back2",
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
     * @Route("/share/{id}", methods="GET", name="auto_wap_rental_order_share2",
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



    /**
     * @Route("/detail/{id}", methods="GET", name="auto_wap_rental_order_detail2",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function detailAction($id)
    {

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);
        return ['rentalOrder' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder)];
    }





}