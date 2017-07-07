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
 * @Route("/illegalRecord2")
 */
class IllegalRecord2Controller extends Controller{
    const PER_PAGE = 20;
    /**
     * @Route("/{page}", methods="GET", name="auto_wap_illegalRecord2",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page){
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_illegal_record_list'),
            ['userID'=>$this->getUser()->getToken(),"page"=>$page]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return [
            "pageCount"=>$data["pageCount"],
            "page"=>$data["page"],
            'illegals' => $data["illegalRecords"]
        ];


    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_wap_illegalRecord_show2",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id){

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_illegal_record_show'),
            ['userID'=>$this->getUser()->getToken(),"illegalRecordID"=>$id]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        return ['illegal' => $data["illegalRecord"]];
    }
}