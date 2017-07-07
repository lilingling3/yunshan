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
 * @Route("/account")
 */
class AccountController extends Controller
{
    const PER_PAGE = 20;

    /**
     * @Route("/auth", methods="GET", name="auto_wap_account_auth")
     * @Template()
     */
    public function authAction()
    {
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());
        return ['auth'=>$auth];

    }

    /**
     * @Route("/auth", methods="POST", name="auto_wap_account_auth_upload")
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
                $auth->setLicenseImage($name)
                    ->setApplyTime(new \DateTime());
                $auth->setAuthTime(null);
                $man = $this->getDoctrine()->getManager();
                $man->persist($auth);
                $man->flush();

            }

            unlink($tmp);
        }

        return $this->redirect($this->generateUrl('auto_wap_account_auth_status'));

    }

    /**
     * @Route("/auth/status", methods="GET", name="auto_wap_account_auth_status")
     * @Template()
     */
    public function authStatusAction()
    {
        $auth = $this->get('auto_manager.member_helper')->get_license_auth_status($this->getUser());

        if($auth['status']==200||$auth['status']==202||$auth['status']==203){
            return $this->redirect($this->generateUrl('auto_wap_account_auth'));
        }

        return ['auth'=>$auth];

    }


}