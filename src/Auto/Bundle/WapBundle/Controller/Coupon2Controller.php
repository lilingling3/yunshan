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
 * @Route("/coupon2")
 */
class Coupon2Controller extends Controller
{


    /**
     * @Route("/useable/{page}", methods="GET", name="auto_wap_coupon_useablelist",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function useablelistAction($page)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_coupon_usable_list'),
            ['userID'=>$this->getUser()->getToken(),'page'=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return [

            'pageCount' => $data["pageCount"],
            'page' => $data["page"],
            'coupons' =>$data["coupons"]
        ];


    }

    /**
     * @Route("/unuseable/{page}", methods="GET", name="auto_wap_coupon_unuseablelist",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function unuseablelistAction($page)
    {

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_coupon_unusable_list'),
            ['userID'=>$this->getUser()->getToken(),'page'=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return [

            'pageCount' => $data["pageCount"],
            'page' => $data["page"],
            'coupons' =>$data["coupons"]
        ];


    }


    /**
     * @Route("/draw", methods="post", name="auto_wap_coupon_draw2")
     * @Template()
     */
    public function drawAction(Request $req){
        $code = $req->request->get('code');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl('auto_api_coupon_draw'),
            ['userID'=>$this->getUser()->getToken(),'code'=>$code]);
        $post_result = $this->get('auto_manager.curl_helper')->object_array(json_decode($post_json));
        if(isset($post_result['errorCode'])&&$post_result['errorCode'] == 0){
            return $this->redirect($this->generateUrl('auto_wap_coupon_useablelist'));
        }
        else{
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message'=>$post_result['errorMessage']]);
        }

    }
}