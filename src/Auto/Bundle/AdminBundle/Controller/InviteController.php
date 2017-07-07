<?php
/**
 * Created by sublime.
 * User: luyao
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/invite")
 */
class InviteController extends Controller {


	const PER_PAGE = 20;

	/**
     * @Route("/list/{page}", methods="GET", name="auto_admin_invite_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

        $activity =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InviteActivity')
                ->findAll();
        
        $cashback = 0;
        $coupon = "暂无优惠券";
        $id = 0;

        if ($activity) {
        

            foreach ($activity as $k => $v) {
                $id       = $v->getId();
                $coupon   = $v->getKind()->getName();
                $cashback = $v->getCashBack();
            }

        }


        return ['activityid'=>$id,'cashback'=>$cashback,'coupon'=>$coupon,'condition'=> empty($activity) ? true:false];
    }


    /**
     * @Route("/inviteReward/new", methods="GET", name="auto_admin_invite_reward_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createFormBuilder([])
            ->add('kind','entity', [
                'label'     => '优惠券种类',
                'class'     => 'AutoManagerBundle:CouponKind',
                'property'  => 'name'
            ])
            ->add('cashback')
            ->setAction($this->generateUrl('auto_admin_invite_reward_create'))
            ->getForm();

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/inviteReward/new", methods="POST", name="auto_admin_invite_reward_create")
     * @Template("AutoAdminBundle:Invite:new.html.twig")
     */
    public function createInviteRewardAction(Request $req)
    {
        $form = $this->createFormBuilder([])
            ->add('kind','entity', [
                'label'     => '优惠券种类',
                'class'     => 'AutoManagerBundle:CouponKind',
                'property'  => 'name'
            ])
            ->add('cashback')
            ->setAction($this->generateUrl('auto_admin_invite_reward_create'))
            ->getForm();

        $form->handleRequest($req);
        $inviteActivity = new \Auto\Bundle\ManagerBundle\Entity\InviteActivity;

        if ($form->isValid()) {
            
            $data = $form->getData();

            $inviteActivity->setCashBack($data['cashback']);
            $inviteActivity->setKind($data['kind']);

            $man = $this->getDoctrine()->getManager();
            $man->persist($inviteActivity);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_invite_record_list'));
        }
        return ['form'  => $form->createView()];
    }



    /**
     * @Route("/inviteReward/delete/{id}", methods="GET", name="auto_admin_invite_reward_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        
        $inviteActivity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InviteActivity')
            ->find($id);

        if ($inviteActivity) {

            $man = $this->getDoctrine()->getManager();
            $man->remove($inviteActivity);
            $man->flush();

        }

        return $this->redirect($this->generateUrl('auto_admin_invite_record_list'));
    }




}