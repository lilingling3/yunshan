<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\Member;
use Auto\Bundle\ManagerBundle\Entity\AuthMember;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
/**
 * @Route("/account")
 */


class AccountController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/auth", methods="GET", name="auto_mobile_account_auth")
     * @Template()
     */
    public function authAction()
    {
        $baseUrl = $this->get('auto_manager.curl_helper')->base_url();
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
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());

        return ['auth'=>$auth,'wallet'=>$this->getUser()->getWallet(),"couponsCount"=>$couponsCount,"rechargeActivity"=>$data["rechargeActivity"]];
    }
    /**
     * @Route("/identify", methods="GET", name="auto_mobile_account_identify")
     * @Template()
     */
    public function identify1Action()
    {

        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        return ['auth'=>$auth,'url'=>$url];

    }


    /**
     * @Route("/identify/upload", methods="POST", name="auto_mobile_account_auth_upload")
     * @Template()
     */
    public function authUploadAction(Request $req)
    {

        $idImage = isset($_FILES['idImage'])?$_FILES['idImage']:'';

        $idHandImage = isset($_FILES['idHandImage'])?$_FILES['idHandImage']:'';

        $licenseAuthImage = isset($_FILES['licenseImage'])?$_FILES['licenseImage']:'';

        if($idImage&&$idImage['tmp_name'])
            $id_path_name = $this->get('mojomaja_photograph.helper.photograph')->persist($idImage['tmp_name']);
        if($idHandImage&&$idHandImage['tmp_name'])
            $id_hand_path_name = $this->get('mojomaja_photograph.helper.photograph')->persist($idHandImage['tmp_name']);
        if($licenseAuthImage&&$licenseAuthImage['tmp_name'])
            $license_path_name = $this->get('mojomaja_photograph.helper.photograph')->persist($licenseAuthImage['tmp_name']);


        $auth = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:AuthMember')
            ->findOneBy(['member'=>$this->getUser()]);

        if(empty($auth)){
            $auth =new \Auto\Bundle\ManagerBundle\Entity\AuthMember();
            $auth->setMember($this->getUser());

        }else{
            $auth->setAuthTime(null);

        }

        if(isset($license_path_name))
            $auth->setLicenseImage($license_path_name)
                ->setApplyTime(new \DateTime());
        if(isset($id_path_name))
            $auth->setIdImage($id_path_name)
                ->setApplyTime(new \DateTime());
        if(isset($id_hand_path_name))
            $auth->setIdHandImage($id_hand_path_name);
            $auth->setApplyTime(new \DateTime());

        $auth->setSubmitType(null);


        $man = $this->getDoctrine()->getManager();
        $man->persist($auth);
        $man->flush();


        return $this->redirect($this->generateUrl('auto_mobile_account_identify'));

    }

    public function upload_image($binary_code){

        $helper = $this->get('mojomaja_photograph.helper.photograph');
        $filename = tempnam(sys_get_temp_dir(), "lecar");
        file_put_contents($filename, $binary_code);

        $name = "";
        $tmp = tempnam(null, null);
        if (copy($filename, $tmp)) {
            chmod($tmp, 0644);
            $name = $helper->persist($tmp, true);
        }

        unlink($tmp);
        unlink($filename);

        return $name;
    }
}
