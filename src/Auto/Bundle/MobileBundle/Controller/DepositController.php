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
 * @Route("/deposit")
 */
class DepositController extends Controller
{

    /**
     * @Route("/list/{id}/{page}", methods="GET", name="auto_mobile_deposit_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($id, $page)
    {
        //id:1 从个人中心进入缴纳押金页面，id：2 ：缴纳押金成功返回列表页
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_deposit_list'),
            ['userID' => $this->getUser()->getToken(), 'page' => $page]);
        $data = json_decode($post_json, true);

        if ($data['errorCode'] != 0) {

            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);

        }
//      dump($data);exit;
//        var-dump($data);
        return ['auth' => $auth, 'deposits' => $data['deposit'], 'total' => $data, 'id' => $id];
    }

}