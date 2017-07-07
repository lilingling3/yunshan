<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @Route("/activity")
 */
class ActivityController extends Controller
{
    /**
     * @Route("/api", methods="GET", name="auto_mobile_activity_api")
     * @Template()
     */
    public function apiAction(){

        return [];
    }


    /**
     * @Route("/database", methods="GET", name="auto_mobile_activity_database")
     * @Template()
     */
    public function databaseAction(){

        return [];
    }

    /**
     * @Route("/invite.html", methods="GET", name="auto_mobile_activity_invite")
     * @Template()
     */
    public function inviteAction(Request $req){

        $uid = $req->query->get('id');
        $utoken = $req->query->get('token');
        $channel = $req->query->get('channel');
        return ["uid"=>$uid,"utoken"=>$utoken,"channel"=>$channel];
    }

    /**
     * @Route("/invitationAward", methods="GET", name="auto_mobile_activity_invitationAward")
     * @Template()
     */
    public function invitationAwardAction( Request $req ){
        $uid = $req->query->get('uid');
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl . $this->generateUrl
            ('auto_api_invite_share_detail'),
            ['userID' =>$uid]);
        $data = json_decode($post_json, true);
        return ['amountnum' => $data,"uid"=>$uid];
    }

    /**
     * @Route("/carInfo", methods="GET", name="auto_mobile_activity_car_control_page")
     * @Template()
     */
    public function carInfoAction(){


        $license_places = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->findBy(['kind' => 1]);

        return ['license_places' => $license_places, 'companys' => $company];
    }
    /**
     * @Route("/error", methods="GET", name="auto_mobile_activity_car_error")
     * @Template()
     */
    public function errorAction(){
        return [];
    }

    /**
     * @Route("/testFastDFS", methods="GET", name="auto_mobile_activity_test")
     * @Template()
     */
    public function testAction( Request $req ){

        return [];
    }
}
