<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

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

    /**
     * @Route("/rental/show/{id}", methods="GET", name="auto_mobile_rental_order_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function rentalShowAction($id)
    {
        $order = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->findOneBy(['member' => $this->getUser(), 'id' => $id]);
        if (empty($order)) {
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => '无效订单!']);
        }

        $rentalOrder = call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $order);
        if($rentalOrder['status']== \Auto\Bundle\ManagerBundle\Entity\RentalOrder::CANCEL_ORDER){

            return $this->render(
                "AutoMobileBundle:Order:cancelRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }


        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER || $rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER){

            return $this->render(
                "AutoMobileBundle:Order:beginRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }

        if($rentalOrder['status']==\Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER){
            return $this->render(
                "AutoMobileBundle:Order:successRental.html.twig",
                ['rentalOrder'=>$rentalOrder]  );
        }

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_order_settle_rental_order'),
            ['userID'=>$this->getUser()->getToken(),"orderID"=>$id]);

        $data = json_decode($post_json,true);

        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return $this->render(
            "AutoMobileBundle:Order:payRental.html.twig",
            ['rentalOrder'=>$data["order"],'maxcoupon'=>$data["coupon"],"maxWalletAmount"=>$data["maxWalletAmount"]]);


    }

    /**
     * @Route("/rental/list/{page}", methods="GET", name="auto_mobile_rental_order_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */


    public function rentalListAction($page)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
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
            "pageCount"=>$data["pageCount"],
            "page"=>$page,
        ];

    }


    /**
     * @Route("/rental/back/{id}", methods="GET", name="auto_mobile_rental_car_back",
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
     * @Route("/detail/{id}", methods="GET", name="auto_mobile_rental_order_detail",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function detailAction($id)
    {

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);

        return ['rentalOrder' => call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder)];
    }

}