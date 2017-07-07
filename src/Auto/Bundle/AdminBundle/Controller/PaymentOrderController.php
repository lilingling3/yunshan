<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\PaymentOrder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\PaymentOrderType;

/**
 * @Route("/paymentOrder")
 */
class PaymentOrderController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_payment_order_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $mobile = $req->query->get('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:PaymentOrder')
                ->createQueryBuilder('po')
        ;
        if($mobile){
            $qb
                ->join('po.member','m')
                ->andWhere($qb->expr()->eq('m.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);
        }
        $paymentOrders =
            new Paginator(
                $qb
                    ->select('po')
                    ->orderBy('po.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );


        $total = ceil(count($paymentOrders) / self::PER_PAGE);

        $kinds = $this->get("auto_manager.global_helper")->get_payment_order_kind_arr();

        return ['lists'=>$paymentOrders,'page'=>$page,'total'=>$total,'mobile'=>$mobile,'kinds'=>$kinds];
    }
    /**
     * @Route("/new", methods="GET", name="auto_admin_payment_order_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createFormBuilder()
            ->add('amount','text',['label'=>'缴费金额（元）'])
            ->add('reason','textarea',['label'=>'缴费说明','required' => false])
            ->add('kind', 'choice', array(
                'label' => '缴费类型',
                'choices'  => $this->get("auto_manager.global_helper")->get_payment_order_kind_arr(),

            ))
            ->setAction($this->generateUrl('auto_admin_payment_order_create'))
            ->setMethod('POST')
            ->getForm();

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_payment_order_create")
     * @Template("AutoAdminBundle:PaymentOrder:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $paymentOrder = new \Auto\Bundle\ManagerBundle\Entity\PaymentOrder();

        $form = $this->createFormBuilder()
            ->add('amount','text',['label'=>'缴费金额（元）'])
            ->add('reason','textarea',['label'=>'缴费说明'])
            ->add('kind', 'choice', array(
                'label' => '缴费类型',
                'choices'  => $this->get("auto_manager.global_helper")->get_payment_order_kind_arr(),

            ))
            ->setAction($this->generateUrl('auto_admin_payment_order_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);

        $mobile = $req->request->getInt('mobile');
        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:member')
            ->findOneBy(['mobile'=>$mobile]);

        if(empty($member)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'输入的手机号码不存在']
            );
        }

        if ($form->isValid()) {
            $data = $form->getData();
            $paymentOrder->setMember($member);
            $paymentOrder->setAmount($data['amount']);
            $paymentOrder->setKind($data['kind']);
            $paymentOrder->setReason($data['reason']);
            $man = $this->getDoctrine()->getManager();
            $man->persist($paymentOrder);
            $man->flush();

            //添加客户端消息
            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
            $message->setContent($this->get("auto_manager.payment_helper")->getMessage(2))
                ->setKind(1)
                ->setMember($member)
                ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
            ;
            $man = $this->getDoctrine()->getManager();
            $man->persist($message);
            $man->flush();

            //添加推送
            $this->get("auto_manager.payment_helper")->pushPaymentMessage($paymentOrder);


            return $this->redirect($this->generateUrl('auto_admin_payment_order_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/show/{id}", methods="GET", name="auto_admin_payment_order_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {
        $paymentOrder = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:PaymentOrder')
            ->find($id);
        $kinds = $this->get("auto_manager.global_helper")->get_payment_order_kind_arr();
        return ['paymentOrder'  => $paymentOrder,'kinds'=>$kinds ];
    }


}