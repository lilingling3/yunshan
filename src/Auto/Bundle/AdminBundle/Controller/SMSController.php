<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/11/23
 * Time: 下午5:25
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\SMS;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/sms")
 */
class SMSController  extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_sms_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:SMS')
                ->createQueryBuilder('s')
        ;
        $SMSCodes =
            new Paginator(
                $qb
                    ->select('s')
                    ->orderBy('s.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($SMSCodes) / self::PER_PAGE);

        return ['SMSCodes'=>$SMSCodes,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/send", methods="GET", name="auto_admin_sms_code_send")
     * @Template()
     */

    public function sendAction(){

        $form = $this->createFormBuilder()
            ->add('content','textarea')
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_sms_code_send_sms'))
            ->getForm();
        return ['form'=>$form->createView()];

    }


    /**
     * @Route("/sendSMS", methods="POST", name="auto_admin_sms_code_send_sms")
     * @Template("AutoAdminBundle:SMS:send.html.twig")
     */

    public function sendSMSAction(Request $req){

        $form = $this->createFormBuilder()
            ->add('content','textarea')
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_sms_code_send_sms'))
            ->setMethod("POST")
            ->getForm();


        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();

            $mobile = $data['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);

            if(empty($member)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'没有该用户!']
                );

            }else{

                $this->get("auto_manager.sms_helper")->add(
                    $member->getMobile(),$data['content']
                );

                return $this->redirect($this->generateUrl('auto_admin_sms_list'));

            }
        }
            return ['form'=>$form->createView()];

    }



    /**
     * @Route("/code/list/{page}", methods="GET", name="auto_admin_sms_code_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function codeListAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:SMSCode')
                ->createQueryBuilder('s')
        ;
        $SMSCodes =
            new Paginator(
                $qb
                    ->select('s')
                    ->orderBy('s.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($SMSCodes) / self::PER_PAGE);

        return ['SMSCodes'=>$SMSCodes,'page'=>$page,'total'=>$total];
    }
}