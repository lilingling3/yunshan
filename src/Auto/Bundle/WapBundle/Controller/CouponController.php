<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/coupon")
 */
class CouponController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/{page}", methods="GET", name="auto_wap_coupon",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function couponAction($page)
    {

        $this_user = $this->getUser();
        $offsetHour = floor(strtotime(date('Y-m-d H:i:s')));
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c');

        $coupons =
            new Paginator(
                $qb
                    ->select('c')
                    ->select('c')
                    ->join('c.kind','k')
                    ->orderBy('c.useTime', 'DESC')
                    ->addOrderBy('c.endTime', 'DESC')
                    ->addOrderBy('k.amount', 'DESC')
                    ->where($qb->expr()->eq('c.member', ':member'))
                    ->setParameter('member', $this->getUser())
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))

            );


        return [

            'pageCount' => ceil($coupons->count() / self::PER_PAGE),
            'page' => $page,
            'coupons' => array_map($this->get('auto_manager.coupon_helper')->get_coupon_normalizer(),
                $coupons->getIterator()->getArrayCopy())
        ];


    }

    /**
     * @Route("/draw", methods="post", name="auto_wap_coupon_draw")
     * @Template()
     */
    public function drawAction(Request $req){
        $code = $req->request->get('code');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl('auto_api_coupon_draw'),
            ['userID'=>$this->getUser()->getToken(),'code'=>$code]);
        $post_result = $this->get('auto_manager.curl_helper')->object_array(json_decode($post_json));
        if(isset($post_result['errorCode'])&&$post_result['errorCode'] == 0){
            return $this->redirect($this->generateUrl('auto_wap_coupon'));
        }
        else{
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message'=>$post_result['errorMessage']]);
        }

     }

    /**
     * @Route("/share/app/{aid}.html", methods="get",name="auto_wap_coupon_share_app")
     * @Template()
     */

    public function shareAppAction($aid,Request $req){

        $uid = $req->query->get('uid');
        return ["aid"=>$aid,"uid"=>$uid];
    }

    /**
     * @Route("/share/order/{aid}_{oid}.html", methods="get", name="auto_wap_coupon_share_order")
     * @Template()
     */

    public function shareOrderAction($aid,$oid){
        $activety = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->find($aid);
        if(empty($activety)){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['没有该活动！']);
        }
        $rentalOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalOrder')
            ->find($oid);
        $offset = $rentalOrder->getCreateTime()?($rentalOrder->getEndTime()?$rentalOrder->getEndTime()->getTimestamp():(new \DateTime())
                ->getTimestamp())
            -$rentalOrder->getCreateTime()->getTimestamp():0;
        $pay_amount=$this->get('auto_manager.order_helper')->get_charge_details($rentalOrder);
       $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();
        $mileage=$this->get('auto_manager.order_helper')->get_rental_order_mileage($rentalOrder);
        return [
            'rentalOrder' =>$rentalOrder,
            "mileage"=>$mileage,
            "offset"=>$offset,
            'aid' =>$aid,
            "payAmount"=>$pay_amount,
            'signPackage'=>$signPackage,
            'url'=>$url
        ];
    }

}