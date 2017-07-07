<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/account2")
 */
class Account2Controller extends Controller
{

    /**
     * @Route("/auth", methods="GET", name="auto_wap_account_auth2")
     * @Template()
     */
    public function authAction()
    {
       // $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
        $baseUrl='http://lecarshare.com';
        $post_json = $this->get('auto_manager.curl_helper')->do_post($baseUrl.$this->generateUrl
            ('auto_api_message_options'),
            []);
        $data = json_decode($post_json,true);
        if($data['errorCode']!=0){
            return $this->render(
                "AutoWapBundle:Default:message.html.twig",
                ['message' => $data['errorMessage']]);
        }
        $couponsCount = $this->get('auto_manager.coupon_helper')->get_member_coupon_count($this->getUser());

        $orderqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('o');

        $orders=
            $orderqb->select('o')
                ->where($orderqb->expr()->isNull('o.cancelTime'))
                ->andWhere($orderqb->expr()->eq('o.member', ':member'))
                ->setParameter('member', $this->getUser())
                ->getQuery()
                ->getResult() ;
        $mileages=0;
        foreach($orders as $o){
            $mileages += $o->getMileage()?round($o->getMileage()/1000,2):0;
        }



        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        return ['auth'=>$auth,"couponsCount"=>$couponsCount,"ordersCount"=>count($orders),"mileages"=>$mileages,"rechargeActivity"=>$data["rechargeActivity"]];

    }



    /**
     * @Route("/identify", methods="GET", name="auto_wap_account_identify2")
     * @Template()
     */
    public function identifyAction()
    {
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        return ['auth'=>$auth];

    }

    /**
     * @Route("/identify", methods="POST", name="auto_wap_account_auth_upload2")
     * @Template()
     */
    public function authUploadAction()
    {

        $img2 = isset($_FILES['file2'])?$_FILES['file2']:'';

        /*
         * @var $auth \Auto\Bundle\ManagerBundle\Entity\AuthMember
         */

        $helper = $this->container->get('mojomaja_photograph.helper.photograph');

        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member'=>$this->getUser()]);

        if(empty($auth)){
            $auth = new AuthMember();
        }

        $auth->setMember($this->getUser());

        if($img2){


            $tmp = tempnam(null, "lp");
            if (copy($img2["tmp_name"], $tmp)) {


                chmod($tmp, 0644);

                $name = $helper->persist($tmp, true);
                $auth->setLicenseImage($name);
                $auth->setApplyTime(new \DateTime());
                $auth->setCreateTime(new \DateTime());
                $auth->setAuthTime(null);
                $man = $this->getDoctrine()->getManager();
                $man->persist($auth);
                $man->flush();

            }

            unlink($tmp);
        }

        return $this->redirect($this->generateUrl('auto_wap_account_identify_status'));

    }

    /**
     * @Route("/identify/status", methods="GET", name="auto_wap_account_identify_status")
     * @Template()
     */
    public function identifyStatusAction()
    {
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());

        if($auth['status']!=201){
            return $this->redirect($this->generateUrl('auto_wap_account_identify2'));
        }

        return ['auth'=>$auth];

    }




}