<?php
/**
 * Created by PhpStorm.
 * User: fl
 * Date: 4/1/17
 * Time: 下午13:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/recharge")
 */
class RechargeController extends Controller
{

    /**
     * @Route("/show", methods="GET", name="auto_mobile_recharge_show")
     * @Template()
     */
    public function showAction()
    {

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_recharge_step_list'),
            ['userID' => $this->getUser()->getToken()]);
        $data = json_decode($post_json, true);
        $steplists = [];
        foreach ($data['steplist'] as $key => $value) {

            if ($key % 3 == 0) {
                $steplists[count($steplists)] = [];
            }
            $steplists[count($steplists) - 1][] = $value;
        }
        return ['steplists' => $steplists, 'amount' => $data];
    }


    /**
     * @Route("/list/{page}", methods="GET", name="auto_mobile_recharge_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */


    public function listAction($page)
    {

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_recharge_record'),
            ['userID' => $this->getUser()->getToken(), 'page' => $page]);
        $data = json_decode($post_json, true);
        if ($data['errorCode'] != 0) {

            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);

        }

        return ['operates' => $data];
    }
}