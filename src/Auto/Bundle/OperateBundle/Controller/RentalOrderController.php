<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/18
 * Time: 下午5:58
 */

namespace Auto\Bundle\OperateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Auto\Bundle\ManagerBundle\Entity\RentalCarOnlineRecord;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * @Route("/order/rental")
 */

class RentalOrderController extends Controller{


    /**
     * @Route("/show/{id}", methods="GET", name="auto_operate_rental_order_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rental_order_show'),
            ['userID'=>$this->getUser()->getToken(),"rentalOrderID"=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rentalOrder'=>$data['rentalOrder']];
    }



    /**
     * @Route("/list/{id}/{page}", methods="GET", name="auto_operate_rental_order_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($id,$page)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rental_car_orders'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"page"=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rentalOrders'=>$data["rentalOrders"],"pageCount"=>$data["pageCount"],
            'rentalCarId'=>$id,"page"=>$data["page"]];
    }



    /**
     * @Route("/paylist/{page}", methods="GET", name="auto_operate_rental_payorder_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function payListAction($page)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_unpaid_rental_order_list'),
            ['userID'=>$this->getUser()->getToken(),"page"=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['rentalOrders'=>$data["rentalOrders"],"pageCount"=>$data["pageCount"],"page"=>$data["page"]];
    }


    /**
     * @Route("/backlist/{page}", methods="GET", name="auto_operate_rental_backorder_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function backListAction($page)
    {
         $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_overtime_rental_order_list'),
            ['userID'=>$this->getUser()->getToken(),"page"=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ['rentalOrders'=>$data["rentalOrders"],"pageCount"=>$data["pageCount"],"page"=>$data["page"]];
    }

    /**
     * @Route("/check/{id}", methods="GET", name="auto_operate_rental_order_check",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function checkAction($id)
    {
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);
        return ['rentalOrder'=>call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder),'member'=>$rentalOrder->getMember()];
    }


    /**
     * @Route("/check/submit", methods="POST", name="auto_operate_rental_order_check_submit")
     */
    public function checkSubmitAction(Request $req)
    {
        $id = $req->request->getInt('orderID');
        $mileage = $req->request->get('mileage');

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);

        $rentalOrder->setUseTime(new \DateTime());
        $rentalOrder->setCancelTime(null);

        $man = $this->getDoctrine()->getManager();

        $man->persist($rentalOrder);
        $man->flush();

        $mileageRecord = new \Auto\Bundle\ManagerBundle\Entity\MileageRecords();
        $mileageRecord->setRentalCar($rentalOrder->getRentalCar());
        $mileageRecord->getOperator($this->getUser());
        $mileageRecord->setMileage($mileage);
        $mileageRecord->setRentalOrder($rentalOrder);
        $mileageRecord->setKind($mileageRecord::USE_CAR_KIND);

        $man->persist($mileageRecord);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_operate_rental_car_list'));
    }



    /**
     * @Route("/end/{id}", methods="GET", name="auto_operate_rental_order_end",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function endAction($id)
    {
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);
        return ['rentalOrder'=>call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder),'member'=>$rentalOrder->getMember()];
    }


    /**
     * @Route("/end/submit", methods="POST", name="auto_operate_rental_order_end_submit")
     */
    public function endSubmitAction(Request $req)
    {
        $id = $req->request->getInt('orderID');
        $mileage = $req->request->get('mileage');

        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($id);

        $rentalOrder->setEndTime(new \DateTime());
        $rentalOrder->setPayTime(new \DateTime());
        $rentalOrder->setAmount(0);

        $man = $this->getDoctrine()->getManager();

        $man->persist($rentalOrder);
        $man->flush();

        $mileageRecord = new \Auto\Bundle\ManagerBundle\Entity\MileageRecords();
        $mileageRecord->setRentalCar($rentalOrder->getRentalCar());
        $mileageRecord->getOperator($this->getUser());
        $mileageRecord->setMileage($mileage);
        $mileageRecord->setRentalOrder($rentalOrder);
        $mileageRecord->setKind($mileageRecord::BACK_CAR_KIND);

        $man->persist($mileageRecord);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_operate_rental_car_list'));
    }
    /**
     * @Route("/rentalend/{id}", methods="GET", name="auto_operate_rental_order_end_rental",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function endOrderAction($id)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rental_order_show'),
            ['userID'=>$this->getUser()->getToken(),"rentalOrderID"=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return ['rentalOrder'=>$data['rentalOrder']];
        

    }

    /**
     * @Route("/rentalend", methods="POST", name="auto_operate_end_order_data")
     * @Template("AutoOperateBundle:RentalOrder:endOrder.html.twig")
     */

    public function endOrderDateAction(Request $req)
    {
        $orderId=$req->request->getInt('orderID');
        /* echo $orderId;*/
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($orderId);
        /* echo $rentalOrder->getEndTime();exit;*/
        if(!empty($rentalOrder->getEndTime())){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => '已还车!']);
        }
        $rentalOrder->setEndTime(new \DateTime());
        $man = $this->getDoctrine()->getManager();
        $onlineRecord =new RentalCarOnlineRecord();
        $onlineRecord->setStatus(0);
        $onlineRecord->setReason(json_encode([17]));
        $onlineRecord->setRentalCar($rentalOrder->getRentalCar());
        $onlineRecord->setMember($this->getUser());
        $man->persist($onlineRecord);
        $man->flush();
        //断电
        $box =  $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$rentalOrder->getRentalCar()]);
   
        $process =
            $this->get("auto_manager.rental_car_helper")->operate($rentalOrder->getRentalCar(),"off",$this->getUser(),'');


        $this->get('monolog.logger.operate')->info($this->getUser()->getToken().' off '.$rentalOrder->getRentalCar()->getLicense());

        $rentalOrderDetail=call_user_func($this->get('auto_manager.order_helper')->get_rental_order_normalizer(),
            $rentalOrder);

        return $this->redirect($this->generateUrl('auto_operate_end_order_succeed',['id'=>$orderId]));
    }

    /**
     * @Route("/rentalend/succeed/{id}", methods="GET", name="auto_operate_end_order_succeed",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function endDataAction($id)
    {
        return ['id'=>$id];
    }
}