<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/24
 * Time: 上午9:10
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalOrder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/orderFlow")
 */

class OrderFlowController extends Controller {

    // 每页显示个数
    const PER_PAGE = 20;

    // 订单类型
    
    //阿里支付 
    const ORDERTYPE_ALIPAY = 100;

    //微信支付
    const ORDERTYPE_WECHAT = 101;

    //手动
    const ORDERTYPE_MANUAL = 102;

    //退款
    const ORDERTYPE_REFUND = 4;

    //扣款
    const ORDERTYPE_CHARGE = 5;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_order_flow_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $status = $req->query->getInt('status');
        $mobile = $req->query->get('mobile');
        $province = empty($req->query->get('province')) ? '':$req->query->get('province');
        $city = empty($req->query->getInt('city')) ? '': $req->query->getInt('city');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('c')
                ->select('c')
                ->join('c.member','u')
        ;
            if($mobile){
                $qb
                    ->andWhere($qb->expr()->eq('u.mobile', ':mobile'))
                    ->setParameter('mobile', $mobile);

            }

        //订单已取消
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::CANCEL_ORDER){
            $qb ->andWhere($qb->expr()->isNull('c.useTime'))
                ->andWhere($qb->expr()->isNotNull('c.cancelTime'));

        }
        //已下单未取车
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_NO_TAKE_ORDER){
            $qb->andWhere($qb->expr()->isNull('c.useTime'))
                ->andWhere($qb->expr()->isNull('c.cancelTime'))
                ->andWhere($qb->expr()->isNull('c.endTime'));
        }
        //已下单
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PROCESS_HAS_TAKE_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.useTime'))
                ->andWhere($qb->expr()->isNull('c.endTime'));
        }
        //已下单已取车未付款
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::BACK_NO_PAY_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.endTime'))
                ->andWhere($qb->expr()->isNull('c.payTime'));
        }

        //已付款
        if($status == \Auto\Bundle\ManagerBundle\Entity\RentalOrder::PAYED_ORDER){
            $qb->andWhere($qb->expr()->isNotNull('c.payTime'));
        }

        

        if ($province !=='' && $province !=='请选择') {

            $distinctList =  $this->get('auto_manager.area_helper')->getDistinctList($province,$city);

            $qb
                ->join('c.pickUpStation','s')
                ->andWhere($qb->expr()->in('s.area', ':area'))
                ->setParameter('area', $distinctList)
            ;
        }

        $rentalOrders =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $should_obtain_fee=[];
        if(!empty($rentalOrders)) {
            foreach ($rentalOrders as $order) {
                if (!isset($should_obtain_fee[$order->getId()])) {
                    $should_obtain_fee[$order->getId()] = 0;
                }
                $should_obtain_fees = $this->get('auto_manager.order_helper')->get_charge_details($order);
                if (!$order->getCancelTime()) {
                    $should_obtain_fee[$order->getId()] = $should_obtain_fees['cost'];
                } else {
                    $should_obtain_fee[$order->getId()] = 0;
                }

            }
        }

        $cityList =  $this->get('auto_manager.area_helper')->getCitylist();

        $total = ceil(count($rentalOrders) / self::PER_PAGE);
        return ['rentalOrders'=>$rentalOrders,'should_obtain_fee'=>$should_obtain_fee,'page'=>$page,'total'=>$total,'cityList'=>$cityList,'querystring'=>$_SERVER["QUERY_STRING"]];

    }


    /**
     * @Route("/rechargeList/{page}", methods="GET", name="auto_admin_order_flow_recharge_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function rechargeListAction(Request $req,$page = 1)
    {
        $way = $req->query->getInt('way');
        $mobile = $req->query->get('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOrder')
                ->createQueryBuilder('c')
                ->select('c')
                ->join('c.member','u')
        ;
        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('u.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);

        }

        //支付宝充值
        if($way == self::ORDERTYPE_ALIPAY){
            $qb ->andWhere($qb->expr()->isNull('c.wechatTransactionId'))
                ->andWhere($qb->expr()->isNotNull('c.alipayTradeNo'));
        }
        //微信充值
        if($way == self::ORDERTYPE_WECHAT){
            $qb->andWhere($qb->expr()->isNotNull('c.wechatTransactionId'))
                ->andWhere($qb->expr()->isNull('c.alipayTradeNo'));
        }

        //人工充值
        if($way == self::ORDERTYPE_MANUAL){
            $qb->andWhere($qb->expr()->isNull('c.wechatTransactionId'))
                ->andWhere($qb->expr()->isNull('c.alipayTradeNo'))
                ->andWhere($qb->expr()->isNull('c.refundTime'));
                

        }

        $rechargeOrders =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->andWhere($qb->expr()->isNotNull('c.payTime'))
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($rechargeOrders) / self::PER_PAGE);
        return ['rechargeOrders'=>$rechargeOrders,'page'=>$page,'total'=>$total,'mobile'=>$mobile,'way'=>$way];

    }

    /**
     * @Route("/recharge/new", methods="GET", name="auto_admin_order_flow_recharge_new")
     * @Template()
     */
    public function newRechargeAction()
    {
        $form = $this->createFormBuilder([])
            ->add('mobile')
            ->add('amount')
            ->setAction($this->generateUrl('auto_admin_order_flow_recharge_create'))
            ->getForm();

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/recharge/new", methods="POST", name="auto_admin_order_flow_recharge_create")
     * @Template("AutoAdminBundle:OrderFlow:newRecharge.html.twig")
     */
    public function createRechargeAction(Request $req)
    {
        $form = $this->createFormBuilder([])
            ->add('mobile')
            ->add('amount')
            ->setAction($this->generateUrl('auto_admin_order_flow_recharge_create'))
            ->getForm();

        $form->handleRequest($req);
        $rechargeOrder = new \Auto\Bundle\ManagerBundle\Entity\RechargeOrder;

        if ($form->isValid()) {
            $data = $form->getData();
            //dump($data);die;
            $mobile = $data['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);
            if(empty($member)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请先注册用户!']
                );
            }
            $man = $this->getDoctrine()->getManager();
            $total = $member->getWallet()+$data['amount'];
            $member->setWallet($total);

            $rechargeOrder->setMember($member);
            $rechargeOrder->setAmount($data['amount']);
            $rechargeOrder->setActualAmount(0);
            $rechargeOrder->setPayTime(new \DateTime());
            $man->persist($rechargeOrder);
            $man->flush();

            // 充值记录
            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOperate')
                ->find(2);
            $operater = $this->getUser();
            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
            $record->setCreateTime(new \DateTime());
            $record->setMember($member);
            $record->setOperater($operater);
            $record->setOperate($operate);
            $record->setWalletAmount($total);
            $record->setAmount($data['amount']);
            $record->setRemark($operate->getName());
            $man->persist($record);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_order_flow_recharge_list'));
        }
        return ['form'  => $form->createView()];
    }



    /**
     * @Route("/refundList/{page}", methods="GET", name="auto_admin_order_flow_refund_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function refundListAction(Request $req,$page = 1)
    {
        $way = $req->query->getInt('way');
        $mobile = $req->query->get('mobile');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeRecord')
                ->createQueryBuilder('c')
        ;

        $qb
            ->select('c')
            ->join('c.member','u')
            ->andWhere($qb->expr()->neq('c.operate', ':operate1'))
            ->andWhere($qb->expr()->neq('c.operate', ':operate2'))
            ->andWhere($qb->expr()->neq('c.operate', ':operate3'))
            ->setParameter('operate1',1)
            ->setParameter('operate2',2)
            ->setParameter('operate3',3)
        ;

        if($mobile){
            $qb
                ->andWhere($qb->expr()->eq('u.mobile', ':mobile'))
                ->setParameter('mobile', $mobile);

        }

        //人工退款
        if($way == self::ORDERTYPE_REFUND){
            $qb
                ->andWhere($qb->expr()->eq('c.operate',':operate'))
                ->setParameter('operate',4)
            ;

        }

        // 人工扣款
        if($way == self::ORDERTYPE_CHARGE){
            $qb        
                ->andWhere($qb->expr()->eq('c.operate',':operate'))
                ->setParameter('operate',5)
            ;
        }


        $records =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->andWhere($qb->expr()->neq('c.operate',':operate'))
                    ->setParameter('operate',6)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        if (!empty($records)) {

            $rechargeRecords = array_map($this->get('auto_manager.recharge_helper')->get_recharge_record(),$records->getIterator()->getArrayCopy());
        } else {
            $rechargeRecords = [];
        }

        $total = ceil(count($rechargeRecords) / self::PER_PAGE);
        return ['rechargeRecords'=>$rechargeRecords,'page'=>$page,'total'=>$total,'mobile'=>$mobile,'way'=>$way];

    }

    /**
     * @Route("/recharge/refund/new", methods="GET", name="auto_admin_order_flow_recharge_refund_new")
     * @Template()
     */
    public function refundNewAction()
    {
        $form = $this->createFormBuilder([])
            ->add('mobile')
            ->add('actualamount')
            ->add('chargeamount')
            ->add('way', 'choice',['choices'=>array('4'=>'退款','5'=>'扣款')])
            ->add('remark')
            ->setAction($this->generateUrl('auto_admin_order_flow_recharge_refund_create'))
            ->getForm();

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/recharge/refund/new", methods="POST", name="auto_admin_order_flow_recharge_refund_create")
     * @Template("AutoAdminBundle:OrderFlow:refundNew.html.twig")
     */
    public function creaeteRefundAction(Request $req)
    {
        $form = $this->createFormBuilder([])
            ->add('mobile')
            ->add('actualamount')
            ->add('chargeamount')
            ->add('way', 'choice',['choices'=>array('4'=>'退款','5'=>'扣款')])
            ->add('remark')
            ->setAction($this->generateUrl('auto_admin_order_flow_recharge_refund_create'))
            ->getForm();

        $form->handleRequest($req);
        $rechargeOrder = new \Auto\Bundle\ManagerBundle\Entity\RechargeOrder;

        if ($form->isValid()) {
            $data = $form->getData();

            $mobile = $data['mobile'];
            $remark = $data['remark'];
            $actualAmount = $data['actualamount'];
            $chargeAmount = $data['chargeamount'];
            $operateType = $data['way'];

            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);

            if(empty($member)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'请先注册用户!']
                );
            }

            $man = $this->getDoctrine()->getManager();
          
            $wallet = $member->getWallet();
            $total = ($wallet - $chargeAmount) >= 0 ? $wallet - $chargeAmount: 0;
            $member->setWallet($total);

            $rechargeOrder->setMember($member);
            $rechargeOrder->setRefundTime(new \DateTime());
            $rechargeOrder->setActualAmount(0);
            $rechargeOrder->setActualRefundAmount($actualAmount);
            $rechargeOrder->setRefundAmount($chargeAmount);

            $man->persist($rechargeOrder);
            $man->flush();

            // 充值记录
            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeOperate')
                ->find($operateType);

            $operater = $this->getUser();
            $record = new \Auto\Bundle\ManagerBundle\Entity\RechargeRecord();
            $record->setCreateTime(new \DateTime());
            $record->setRechargeOrder($rechargeOrder);
            $record->setMember($member);
            $record->setWalletAmount($total);
            $record->setAmount($chargeAmount);
            $record->setOperater($operater);
            $record->setOperate($operate);
            $record->setRemark($remark);
            $man->persist($record);

            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_order_flow_refund_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/show{id}", methods="GET", name="auto_admin_order_flow_recharge_refund_show",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:OrderFlow:refundShow.html.twig")
     */
    public function showAction($id)
    {
        echo $id;
        $record = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeRecord')
            ->find($id);

        if (!empty($record)) {

            $rechargeRecords = call_user_func($this->get('auto_manager.recharge_helper')->get_recharge_record(),$record);
        } else {
            $rechargeRecords = [];
        }
        return ['record'=>$rechargeRecords];
    }

    /**
     * @Route("/check", methods="POST", name="auto_admin_order_flow_check")
     */
    public function checkAction(Request $req)
    {

        $recharge = $req->request->get('recharge');
        $mobile = $req->request->get('mobile');

        $member = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:member')
            ->findOneBy(['mobile'=>$mobile]);

        if (empty($member)) {

            return new JsonResponse([
                'errorCode'    => -40001,
                'errorMessage' => '手机号不存在'
            ]);
        }

        $totalAmount = $member->getWallet();

        if ($totalAmount - $recharge >= 0) {

            $walletAmount = $totalAmount - $recharge;
        } else {

            return new JsonResponse([
                'errorCode'    => -190001,
                'errorMessage' => '余额不足',
            ]);

        }
        
        return new JsonResponse([
            'errorCode'    => 0,
            'walletAmount' => $walletAmount
        ]);
    }


}   