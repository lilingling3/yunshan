<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Member;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\JsApiPay;
use Auto\Bundle\ManagerBundle\Payment\WeChatJsPay\WxPayUnifiedOrder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/2")
 */
class Default2Controller extends Controller
{

    /**
     * @Route("/login", methods="GET", name="auto_wap_login2")
     * @Template()
     */
    public function loginAction(Request $req)
    {
        return $this->redirect($this->generateUrl('auto_mobile_login'));

        $session = $req->getSession();

        if ($req->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $req->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return [
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error
        ];

    }



    /**
     * @Route("/codelogin", methods="GET", name="auto_wap_codelogin2")
     * @Template()
     */
    public function codeloginAction(Request $req)
    {
        return $this->redirect($this->generateUrl('auto_mobile_login'));
        return [];
    }


    /**
     * @Route("/codelogin/verify", methods="POST", name="auto_wap_codelogin2_verify")
     * @Template("AutoWapBundle:Default2:codelogin.html.twig")
     */
    public function codeloginVerifyAction(Request $req)
    {
       $mobile = $req->request->getInt('mobile');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile' => $mobile]);

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_wap_index3'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile'; 
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

    /**
     * @Route("/index", methods="GET", name="auto_wap_index2")
     * @Template()
     */
    public function indexAction(Request $req)
    {

        return $this->redirect($this->generateUrl('auto_mobile_index'));

        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        $progress = '';
        $user = $this->getUser();
        if(!empty($user))
            $progress = $this->get('auto_manager.order_helper')->get_progress_rental_order($this->getUser());
        $illegal_count = $this->get('auto_manager.member_helper')->get_illegal_count($this->getUser());


        return ['auth'=>$auth,'progress'=>$progress,'illegal_count'=>$illegal_count];
    }


    /**
     * @Route("/index3", methods="GET", name="auto_wap_index3")
     * @Template()
     */
    public function index3Action(Request $req)
    {
        return $this->redirect($this->generateUrl('auto_mobile_index'));

        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        $progress = '';
        $user = $this->getUser();
        if(!empty($user))
            $progress = $this->get('auto_manager.order_helper')->get_progress_rental_order($this->getUser());
        $illegal_count = $this->get('auto_manager.member_helper')->get_illegal_count($this->getUser());


        return ['auth'=>$auth,'progress'=>$progress,'illegal_count'=>$illegal_count];
    }


    /**
     * @Route("/gaode", methods="GET", name="auto_wap_gaode")
     * @Template()
     */
    public function gaodeAction(Request $req)
    {

        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        $progress = '';
        $user = $this->getUser();
        if(!empty($user))
            $progress = $this->get('auto_manager.order_helper')->get_progress_rental_order($this->getUser());
        $illegal_count = $this->get('auto_manager.member_helper')->get_illegal_count($this->getUser());


        return ['auth'=>$auth,'progress'=>$progress,'illegal_count'=>$illegal_count];
    }

    /**
     * @Route("/ge", methods="GET", name="auto_wap_ge")
     * @Template()
     */
    public function geAction(Request $req)
    {

        return [];
    }

    /**
     * @Route("/forget", methods="GET", name="auto_wap_forget2")
     * @Template()
     */
    public function forgetAction()
    {
        return [];
    }

    /**
     * @Route("/forget", methods="POST", name="auto_wap_reset2")
     * @Template()
     */
    public function resetAction(Request $req)
    {
        $mobile = $req->request->getInt('mobile');
        $password = $req->request->get('password');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile'=>$mobile]);

        $encoded = $this->container->get('security.password_encoder')
            ->encodePassword($member, $password);

        $man = $this->getDoctrine()->getManager();
        $member->setPassword($encoded);

        $man->persist($member);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_wap_codelogin2'));

    }

    /**
     * @Route("/rent", methods="GET", name="auto_wap_more_rent2")
     * @Template()
     */
    public function rentAction(){

        return [];
    }
    /**
     * @Route("/more", methods="GET", name="auto_wap_more2")
     * @Template()
     */
    public function moreAction(){

        return [];
    }


    /**
     * @Route("/about", methods="GET", name="auto_wap_more_about2")
     * @Template()
     */
    public function aboutAction(){

        return [];
    }



}