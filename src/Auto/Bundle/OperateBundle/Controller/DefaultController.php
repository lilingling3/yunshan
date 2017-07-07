<?php

namespace Auto\Bundle\OperateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


class DefaultController extends Controller
{




    /**
     * @Route("/login", methods="GET", name="auto_operate_login")
     * @Template()
     */
    public function loginAction(Request $req)
    {
        return [];
    }


    /**
     * @Route("/login/verify", methods="POST", name="auto_operate_login_verify")
     * @Template("AutoOperateBundle:Default:login.html.twig")
     */
    public function loginVerifyAction(Request $req)
    {
       $mobile = $req->request->get('mobile');
        $code = $req->request->get('code');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        //$baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_verify_login'),
            ['mobile'=>$mobile,"code"=>$code]);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }



        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile' => $mobile]);

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_operate_index'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile';
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }



    /**
     * @Route("/index", methods="GET", name="auto_operate_index")
     * @Template()
     */
    public function indexAction()
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
       // $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_index'),
            ['userID'=>$this->getUser()->getToken()]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        return [
            "rentalCar"=>$data
            ];
    }


    /**
     * @Route("/forget/{id}", methods="GET", name="auto_operate_forget",
     *  requirements={"id"="\d+"})
     * @Template()
     */
    public function forgetAction($id)
    {
        return ["id"=>$id];
    }


    /**
     * @Route("/forget", methods="POST", name="auto_operate_reset")
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

        return $this->redirect($this->generateUrl('auto_operate_login'));

    }

    /**
     * @Route("/apitest/{id}", methods="GET", name="auto_operate_apitest",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function apitestAction($id)
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_operator_rentalCar_onlineReasons'),
            ['userID'=>$this->getUser()->getToken(),"rentalCarID"=>$id,"onlineStatus"=>1]);

        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoOperateBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }

        dump($data);exit;
        return [];
    }

}
