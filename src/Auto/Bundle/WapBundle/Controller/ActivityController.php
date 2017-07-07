<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\WapBundle\Controller;
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
     * @Route("/appReturnCar", methods="GET", name="auto_wap_app_return_car")
     * @Template()
     */
    public function appReturnCarAction()
    {
        return [];

    }

    /**
     * @Route("/carshow", methods="GET", name="auto_wap_activity_carshow")
     * @Template()
     */
    public function carShowAction()
    {
        return [];

    }

    /**
     * @Route("/socialSpread", methods="GET", name="auto_wap_activity_socialSpread")
     * @Template()
     */
    public function socialSpreadAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];

    }


    /**
     * @Route("/carshow/protocol", methods="GET", name="auto_wap_activity_carshow_protocol")
     * @Template()
     */
    public function carShowProtocolAction()
    {
        return [];

    }
    /**
     * @Route("/tg0fd", methods="GET", name="auto_wap_activity_tg0fd")
     * @Template()
     */
    public function tg0fdAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];

    }
    /**
     * @Route("/tg0fd/protocol", methods="GET", name="auto_wap_activity_tg0fd_protocol")
     * @Template()
     */
    public function tg0fdProtocolAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];

    }

    /**
     * @Route("/socialSpread/protocol", methods="GET", name="auto_wap_activity_socialSpread_protocol")
     * @Template()
     */
    public function socialSpreadProtocolAction()
    {
        return [];

    }

    /**
     * @Route("/goldfinger", methods="GET", name="auto_wap_activity_Goldfinger")
     * @Template()
     */
    public function goldfingerAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }



    /**
     * @Route("/rechargeProtocol", methods="GET", name="auto_wap_activity_rechargeProtocol")
     * @Template()
     */
    public function rechargeProtocolAction()
    {

        return [ ];
    }


    /**
     * @Route("/lecar", methods="GET", name="auto_wap_activity_lecar")
     * @Template()
     */
    public function lecarAction()
    {

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }
    /**
     * @Route("/main", methods="GET", name="auto_wap_activity_mian")
     * @Template()
     */
    public function mainAction()
    {

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }

    /**
     * @Route("/mascot", methods="GET", name="auto_wap_activity_mascot")
     * @Template()
     */
    public function mascotAction()
    {

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }


    /**
     * @Route("/map", methods="GET", name="auto_wap_activity_map")
     * @Template()
     */
    public function mapAction()
    {

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }

    /**
     * @Route("/recharge", methods="GET", name="auto_wap_activity_recharge")
     * @Template()
     */
    public function rechargeAction()
    {

        return [];
    }


    /**
     * @Route("/codelogin", methods="GET", name="auto_wap_activity_codelogin")
     * @Template()
     */
    public function codeloginAction()
    {
        return ['source'=>7];
    }

    /**
     * @Route("/codelogin4", methods="GET", name="auto_wap_activity_codelogin4")
     * @Template("AutoWapBundle:Activity:codelogin.html.twig")
     */
    public function codelogin4Action()
    {
        return ['source'=>4];
    }
    /**
     * @Route("/codelogin5", methods="GET", name="auto_wap_activity_codelogin5")
     * @Template("AutoWapBundle:Activity:codelogin.html.twig")
     */
    public function codelogin5Action()
    {
        return ['source'=>5];
    }

    /**
     * @Route("/codelogin6", methods="GET", name="auto_wap_activity_codelogin6")
     * @Template("AutoWapBundle:Activity:codelogin.html.twig")
     */
    public function codelogin6Action()
    {
        return ['source'=>6];
    }

    /**
     * @Route("/codelogin/verify", methods="POST", name="auto_wap_activity_codelogin_verify")
     * @Template("AutoWapBundle:Activity:codelogin.html.twig")
     */
    public function codeloginVerifyAction(Request $req)
    {
        $mobile = $req->request->getInt('mobile');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile' => $mobile]);

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_wap_index2'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile';
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }


    /**
     * @Route("/ulogin", methods="GET", name="auto_wap_activity_ulogin")
     * @Template()
     */
    public function uloginAction()
    {
        return ['source'=>4];
    }


    /**
     * @Route("/ulogin5", methods="GET", name="auto_wap_activity_codelogin5")
     * @Template("AutoWapBundle:Activity:ulogin.html.twig")
     */
    public function ulogin5Action()
    {
        return ['source'=>5];
    }

    /**
     * @Route("/ulogin6", methods="GET", name="auto_wap_activity_codelogin6")
     * @Template("AutoWapBundle:Activity:ulogin.html.twig")
     */
    public function ulogin6Action()
    {
        return ['source'=>6];
    }

    /**
     * @Route("/valentine", methods="GET", name="auto_wap_activity_valentine")
     * @Template()
     */
    public function valentineAction()
    {

        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];
    }
    /**
     * @Route("/valentine/protocol", methods="GET", name="auto_wap_activity_valentine_protocol")
     * @Template()
     */
    public function valentineProtocolAction()
    {

        return [];
    }
    /**
 * @Route("/shenmiren", methods="GET", name="auto_wap_activity_shenmiren")
 * @Template()
 */
    public function shenmirenAction()
    {

        return [];
    }

    /**
     * @Route("/welfare", methods="GET", name="auto_wap_activity_welfare")
     * @Template()
     */
    public function welfareAction()
    {

        return [];
    }

    /**
     * @Route("/phone", methods="GET", name="auto_wap_activity_phone")
     * @Template()
     */
    public function testphoneAction()
    {
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $signPackage = $this->get('auto_manager.wechat_helper')->getSignPackage();

        return ['signPackage'=>$signPackage,'url'=>$url];

    }

    /**
 * @Route("/fuliyure", methods="GET", name="auto_wap_activity_fuliyure")
 * @Template()
 */
    public function fuliyureAction()
    {

        return [];

    }

    /**
     * @Route("/midAutumn", methods="GET", name="auto_wap_activity_midAutumn")
     * @Template()
     */
    public function midAutumnAction()
    {

        return [];

    }
}