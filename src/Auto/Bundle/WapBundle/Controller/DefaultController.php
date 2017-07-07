<?php

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
use Symfony\Component\Security\Core\Util\SecureRandom;



class DefaultController extends Controller
{
    /**
     * @Route("/protocol", methods="GET", name="auto_wap_protocol")
     * @Template()
     */
    public function protocolAction(Request $req)
    {
        return [];

    }

    /**
     * @Route("/app/down", methods="GET", name="auto_wap_app_down")
     * @Template()
     */
    public function appDownAction(Request $req)
    {
        return [];

    }

    function get_url_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1")
            return file_get_contents($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result =  curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function do_post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }

    /**
     * @Route("/index", methods="GET", name="auto_wap_index")
     * @Template()
     */
    public function indexAction(Request $req)
    {
        return $this->redirect($this->generateUrl("auto_wap_index3"));


    }


    /**
     * @Route("/login", methods="GET", name="auto_wap_login")
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
     * @Route("/register", methods="GET", name="auto_wap_register")
     * @Template()
     */
    public function registerAction(Request $req)
    {
        return [];
    }

    /**
     * @Route("/register", methods="POST", name="auto_wap_create")
     * @Template()
     */
    public function createAction(Request $req)
    {
        $password = $req->request->get('password');
        $mobile = $req->request->getInt('mobile');


        $member = new \Auto\Bundle\ManagerBundle\Entity\Member();
        $member
            ->setMobile($mobile)
        ;
        $encoded = $this->container->get('security.password_encoder')
            ->encodePassword($member, $password);

        $man = $this->getDoctrine()->getManager();
        $member->setPassword($encoded);
        $member->setRoles(['ROLE_USER'])
               ->setToken(md5((new SecureRandom())->nextBytes(18)));

        $man->persist($member);
        $man->flush();

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_wap_index'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile'; //
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }


    /**
     * @Route("/forget/{id}", methods="GET", name="auto_wap_forget",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function forgetAction($id)
    {
        return ["id"=>$id];
    }

    /**
     * @Route("/forget", methods="POST", name="auto_wap_reset")
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

        return $this->redirect($this->generateUrl('auto_wap_login'));

    }

    /**
     * @Route("/info", methods="GET", name="auto_wap_member_info")
     * @Template()
     */
    public function infoAction(Request $req)
    {
        return [];
    }


    /**
     * @Route("/info", methods="POST", name="auto_wap_member_info_add")
     * @Template()
     */
    public function infoAddAction(Request $req)
    {
        $name = $req->request->get('name');
        $letv_id = $req->request->get('letvId');

        /**
         * @var $member \Auto\Bundle\ManagerBundle\Entity\Member
         */

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->find($this->getUser()->getToken());

        $man = $this->getDoctrine()->getManager();
        $member->setName($name);
        $member->setLetvId($letv_id);

        $man->persist($member);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_wap_index'));
    }

    /**
     * @Route("/feedback", methods="GET", name="auto_wap_feedback")
     * @Template()
     */
    public function feedbackAction(Request $req)
    {
        return [];
    }

    /**
     * @Route("/more", methods="GET", name="auto_wap_more")
     * @Template()
     */
    public function moreAction(){

        return [];
    }

    /**
     * @Route("/apiAbout", methods="GET", name="auto_wap_api_about")
     * @Template()
     */
    public function apiAboutAction(){

        return [];
    }


    /**
     * @Route("/about", methods="GET", name="auto_wap_more_about")
     * @Template()
     */
    public function aboutAction(){

        return [];
    }


    /**
     * @Route("/rent", methods="GET", name="auto_wap_more_rent")
     * @Template()
     */
    public function rentAction(){

        return [];
    }
    /**
     * @Route("/geolocation", methods="GET", name="auto_wap_geolocation")
     * @Template()
     */
    public function geolocationAction(){

        return [];
    }
    /**
     * @Route("/fuli", methods="GET", name="auto_wap_fuli")
     * @Template()
     */
    public function fuliAction(){

        return [];
    }
    

}