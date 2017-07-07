<?php

namespace Auto\Bundle\MobileBundle\Controller;
header("Content-type: text/html; charset=utf-8");
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

class DefaultController extends Controller
{
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



    /*2.1登录页面*/
    /**
     * @Route("/login", methods="GET", name="auto_mobile_login")
     * @Template()
     */
    public function loginAction()
    {
        return [];
    }

    /**
     * @Route("/login/after", methods="get", name="auto_mobile_login_after")
     */
    public function loginAfterAction(Request $req)
    {

        if($this->get('auto_manager.wechat_helper')->isWeChat()){
            $wechat = $this->get('auto_manager.wechat_helper')->GetOpenid();
            if(!empty($wechat) && $wechat['openid']){
                $this->getUser()->setWechatId($wechat['openid']);
                $man = $this->getDoctrine()->getManager();
                $man->persist($this->getUser());
                $man->flush();
            }



        }

        return $this->redirect($this->generateUrl('auto_mobile_index'));

    }



    /**
     * @Route("/wechat/login", methods="get", name="auto_mobile_wechat_login")
     */
    public function wechatLoginAction(Request $req)
    {

        if($this->get('auto_manager.wechat_helper')->isWeChat()){
            $wechat = $this->get('auto_manager.wechat_helper')->GetOpenid();

            if(!empty($wechat) && $wechat['openid']){

                $member = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Member')
                    ->findOneBy(['wechatId'=>$wechat['openid']]);

                if(!empty($member)){
                    $this->authenticateUser($member);
                }
            }



        }

        return $this->redirect($this->generateUrl('auto_mobile_index'));

    }


    /**
     * @Route("/wechat/order", methods="get", name="auto_mobile_wechat_order")
     */
    public function wechatOrderAction(Request $req)
    {

        if($this->get('auto_manager.wechat_helper')->isWeChat()){
            $wechat = $this->get('auto_manager.wechat_helper')->GetOpenid();

            if($wechat['openid']){

                $member = $this->getDoctrine()
                    ->getRepository('AutoManagerBundle:Member')
                    ->findOneBy(['wechatId'=>$wechat['openid']]);

                if(!empty($member)){
                    $this->authenticateUser($member);
                }
            }



        }

        return $this->redirect($this->generateUrl('auto_mobile_rental_order_list'));

    }


    /**
     * @Route("/login/verify", methods="POST", name="auto_mobile_login_verify")
     * @Template("AutoMobileBundle:Default:login.html.twig")
     */
    public function loginVerifyAction(Request $req)
    {

        $mobile = $req->request->get('mobile');

       $code = $req->request->get('code');

        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_verify_login'),
            ['mobile'=>$mobile,"code"=>$code]);
        $data = json_decode($post_json,true);

        if($data['errorCode']!=0){
            return $this->render(
                "AutoMobileBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Member')
            ->findOneBy(['mobile' => $mobile]);

        $this->authenticateUser($member);

        return $this->redirect($this->generateUrl('auto_mobile_login_after'));
    }

    private function authenticateUser(Member $user)
    {
        $providerKey = 'mobile';
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());

        $this->container->get('security.context')->setToken($token);
    }

    /**
     * @Route("/out", methods="GET", name="auto_mobile_out")
     * @Template()
     */
    public function logoutAction(Request $request)
    {

        if($this->get('auto_manager.wechat_helper')->isWeChat()){


                if(!empty($this->getUser())){

                    $this->getUser()->setWechatId(null);
                    $man = $this->getDoctrine()->getManager();
                    $man->persist($this->getUser());
                    $man->flush();

                }


        }

        return $this->redirect($this->generateUrl('auto_mobile_logout'));
    }






    /**
     * @Route("/index", methods="GET", name="auto_mobile_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $isHasOrder = false;
        $data = "";
        $inCompleteOrder = [];

        if ($this->getUser()) {
            $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
            $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
                ('auto_api_order_list'),
                ['userID'=>$this->getUser()->getToken(),1]);
            $data = json_decode($post_json,true);
        }
        
        if (isset($data["orders"]) && $data["orders"]) {
            
            $status = $data["orders"][0]["status"];
            
            if ($status > 100 && $status < 199 ) {

                $isHasOrder      = true;
                $inCompleteOrder = $data["orders"][0];
            }
        }

        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->createQueryBuilder('s')
        ;
        $stations = $qb
                ->select('s')
                ->orderBy('s.createTime', 'DESC')
                ->where($qb->expr()->eq('s.online', ':online'))
                ->setParameter('online', 1)
                ->getQuery()
                ->setMaxResults(20)
                ->getResult();
        $newstations=array_map($this->get('auto_manager.station_helper')->get_station_normalizer()
            ,$stations);

        $newarray=array();
        foreach($newstations as $value){
            if( $value["usableRentalCarCount"]>0){
                $newarray[]=$value;
            }
            if(count($newarray)==3)break;
        }


        $orderqb= $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o')
        ;
        $stationsId=array();
        $latelyStations=array();
        $orders = $orderqb
            ->select('o')
            ->join('o.pickUpStation','os')
            ->orderBy('o.createTime', 'DESC')
            ->where($qb->expr()->eq('o.member', ':member'))
            ->andWhere($qb->expr()->eq('os.online', ':online'))
            ->setParameter('member', $this->getUser())
            ->setParameter('online', 1)
            ->getQuery()
            ->getResult();

        foreach($orders as $value){
            $station=call_user_func($this->get('auto_manager.station_helper')->get_station_normalizer(),
                $value->getPickUpStation());
            if(!in_array($station["rentalStationID"],$stationsId) && $station["usableRentalCarCount"]>0){
                $stationsId[]=$station["rentalStationID"];
                $latelyStations[]=$station;
            }
            if(count($stationsId)==3)break;
        }
        
        return ["newStations"=>$newarray,"latelyStations"=>$latelyStations,"auth"=>$auth,
                "isHasOrder"=>$isHasOrder,"rentalOrder"=>$inCompleteOrder];
    }
    
        /**
     * @Route("/seek", methods="POST", name="auto_mobile_seek")
     * @Template()
     */
    public function seekAction(Request $req)
    {
        $address = $req->request->get('address');
        $lng = $req->request->get('lng');
        $lat = $req->request->get('lat');
        return ["address"=>$address,"lng"=>$lng,"lat"=>$lat];
    }


}
