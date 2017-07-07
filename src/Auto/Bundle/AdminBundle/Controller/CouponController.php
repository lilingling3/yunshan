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
use Auto\Bundle\ManagerBundle\Entity\Coupon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\CouponType;
use Auto\Bundle\ManagerBundle\Form\CouponKindType;
use Auto\Bundle\ManagerBundle\Form\CouponActivityType;

/**
 * @Route("/coupon")
 */
class CouponController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_coupon_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
     //echo (((new \DateTime())->getTimestamp()-(new \DateTime('2016-04-22 08:00:00'))->getTimestamp())/3600);
        $mobile = $req->query->get('mobile');
        $dateType = $req->query->get('dateType');
        $startTime = $req->query->get('startTime');
        $endTime = $req->query->get('endTime');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Coupon')
                ->createQueryBuilder('c')
        ;
        if($mobile){
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile' => $mobile]);
            $qb
                ->andWhere($qb->expr()->eq('c.member', ':member'))
                ->setParameter('member', $member);
        }

        if($dateType == 1){
            $qb
                ->andWhere($qb->expr()->isNotNull('c.member'))
                ->andWhere($qb->expr()->gte('c.createTime', ':start_time'))
                ->andWhere($qb->expr()->lte('c.createTime', ':end_time'))
                ->setParameter('start_time', $startTime)
                ->setParameter('end_time', $endTime);

        }elseif($dateType == 2){
            $qb
                ->andWhere($qb->expr()->isNotNull('c.member'))
                ->andWhere($qb->expr()->gte('c.useTime', ':start_time'))
                ->andWhere($qb->expr()->lte('c.useTime', ':end_time'))
                ->setParameter('start_time', $startTime)
                ->setParameter('end_time', $endTime);
        }

        $coupons =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $orderIdArr = array();
        foreach ($coupons as $value) {
            $order = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->findOneBy(['coupon' => $value]);
            if(empty($order)){
                $orderIdArr[$value->getId()] = '';
            }else{
                $orderIdArr[$value->getId()] = $order->getId();
            }
        }
        $total = ceil(count($coupons) / self::PER_PAGE);
        return ['coupons'=>$coupons,'page'=>$page,'total'=>$total,'orderIdArr'=>$orderIdArr,
            'mobile'=>$mobile,'dateType'=>$dateType,'startTime'=>$startTime,'endTime'=>$endTime,
        ];


    }

    /**
     * @Route("/kind/list/{page}", methods="GET", name="auto_admin_coupon_kind_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function kindListAction($page = 1)
    {

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CouponKind')
                ->createQueryBuilder('k')
        ;
        $couponKinds =
            new Paginator(
                $qb
                    ->select('k')
                    ->orderBy('k.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($couponKinds) / self::PER_PAGE);
        return ['couponKinds'=>$couponKinds,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_coupon_new")
     * @Template()
     */
    public function newAction()
    {

        $form = $this->createFormBuilder()
            ->add('coupon', new CouponType())
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_coupon_create'))
            ->setMethod("POST")
            ->getForm();

        return ['form'  => $form->createView()];
    }



    /**
     * @Route("/new/code", methods="GET", name="auto_admin_coupon_code_new")
     * @Template()
     */
    public function newCodeAction()
    {
        $form = $this->createFormBuilder()
            ->add('coupon', new CouponType())
            ->add('count', 'text')
            ->setAction($this->generateUrl('auto_admin_coupon_code_create'))
            ->setMethod("POST")
            ->getForm();


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/create/code", methods="POST", name="auto_admin_coupon_code_create")
     * @Template()
     */
    public function createCodeAction(Request $req)
    {
        $coupon = new \Auto\Bundle\ManagerBundle\Entity\Coupon();

        $form = $this->createFormBuilder()
            ->add('coupon', new CouponType(),['data'=>$coupon])
            ->add('count', 'text')
            ->setAction($this->generateUrl('auto_admin_coupon_code_create'))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();

            $coupon = $data['coupon'];

            $count = $data['count'];




            for($i=0;$i<$count;$i++){

                $c = new \Auto\Bundle\ManagerBundle\Entity\Coupon();

                $date = (new \DateTime((new \DateTime('+'.$coupon->getKind()->getValidDay().' days'))->format('Y-m-d')));
                $c
                    ->setEndTime($date)
                    ->setKind($coupon->getKind())
                    ->setActivity($coupon->getActivity())
                    ->setCode(strtoupper($this->get('auto_manager.wechat_helper')->createNonceStr(6)))
                ;
                $man = $this->getDoctrine()->getManager();
                $man->persist($c);
                $man->flush();


            }

            return $this->redirect($this->generateUrl('auto_admin_coupon_list'));

        }
        return $this->render(
            "AutoAdminBundle:Coupon:newCode.html.twig",['form'  => $form->createView()]);
    }



    /**
     * @Route("/create", methods="POST", name="auto_admin_coupon_create")
     * @Template()
     */
    public function createAction(Request $req)
    {

        $coupon = new \Auto\Bundle\ManagerBundle\Entity\Coupon();

        $form = $this->createFormBuilder()
            ->add('coupon', new CouponType(),['data'=>$coupon])
            ->add('mobile', 'text')
            ->setAction($this->generateUrl('auto_admin_coupon_create'))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();

            $coupon = $data['coupon'];

            $mobile = $data['mobile'];
            $member = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Member')
                ->findOneBy(['mobile'=>$mobile]);


            if(empty($member)){

                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'没有该用户!']
                );

            }


            $date = (new \DateTime((new \DateTime('+'.$coupon->getKind()->getValidDay().' days'))->format('Y-m-d')));
            $coupon
                ->setEndTime($date)
                ->setMember($member)
                ->setCreateTime(new \DateTime())
            ;

            $man = $this->getDoctrine()->getManager();
            $man->persist($coupon);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_coupon_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/kind/new", methods="GET", name="auto_admin_coupon_kind_new")
     * @Template()
     */
    public function kindNewAction()
    {

        $form = $this->createForm(new CouponKindType(), null, [
            'action' => $this->generateUrl('auto_admin_coupon_kind_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/kind/edit/{id}/{page}", methods="GET", name="auto_admin_coupon_kind_edit",defaults={"page"=1})
     * @Template()
     */
    public function kindEditAction($id,$page,Request $req)
    {
        $coupun = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponKind')
            ->find($id);

        $form = $this->createForm(new CouponKindType(), $coupun, [
            'action' => $this->generateUrl('auto_admin_coupon_kind_update', ['id' => $coupun->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }

    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_coupon_kind_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Coupon:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $coupun = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponKind')
            ->find($id);
        $form = $this->createForm(new CouponKindType(), $coupun, [
            'action' => $this->generateUrl('auto_admin_coupon_kind_update', ['id' => $coupun->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($coupun);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_coupon_kind_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_coupon_kind_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $coupon = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponKind')
            ->find($id);
        $man->remove($coupon);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_coupon_kind_list'));
    }



    /**
     * @Route("/kind/create", methods="POST", name="auto_admin_coupon_kind_create")
     * @Template()
     */
    public function kindCreateAction(Request $req)
    {

        $couponKind = new \Auto\Bundle\ManagerBundle\Entity\CouponKind();

        $form = $this->createForm(new CouponKindType(), $couponKind, [
            'action' => $this->generateUrl('auto_admin_coupon_kind_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($couponKind);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_coupon_kind_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/activity/list/{page}", methods="GET", name="auto_admin_coupon_activity_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function activityListAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CouponActivity')
                ->createQueryBuilder('a')
        ;
        $activities =
            new Paginator(
                $qb
                    ->select('a')
                    ->orderBy('a.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($activities) / self::PER_PAGE);
        return ['activities'=>$activities,'page'=>$page,'total'=>$total];

    }

    /**
     * @Route("/activity/new", methods="GET", name="auto_admin_coupon_activity_new")
     * @Template()
     */
    public function activityNewAction()
    {

        $form = $this->createForm(new CouponActivityType(), null, [
            'action' => $this->generateUrl('auto_admin_coupon_activity_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/activity/create", methods="POST", name="auto_admin_coupon_activity_create")
     * @Template()
     */
    public function activityCreateAction(Request $req)
    {
        $activity = new \Auto\Bundle\ManagerBundle\Entity\CouponActivity();

        $form = $this->createForm(new CouponActivityType(), $activity, [
            'action' => $this->generateUrl('auto_admin_coupon_activity_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($activity);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_coupon_activity_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/activity/add/kind/{id}", methods="GET", name="auto_admin_coupon_activity_add_kind")
     * @Template()
     */
    public function activityAddKindAction($id)
    {

        $form = $this
            ->createFormBuilder()
            ->add('kind','entity', [
                'label'     => '优惠券种类',
                'class'     => 'AutoManagerBundle:CouponKind',
                'property'  => 'name'
            ])
            ->setAction($this->generateUrl('auto_admin_coupon_activity_create_kind',['id'=>$id]))
            ->setMethod('post')
            ->getForm();

        return ['form'  => $form->createView()];

    }

    /**
     * @Route("/activity/create/kind/{id}", methods="POST", name="auto_admin_coupon_activity_create_kind")
     * @Template()
     */
    public function activityCreateKindAction(Request $req,$id)
    {

        $activity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CouponActivity')
            ->find($id);

        $form = $this
            ->createFormBuilder()
            ->add('kind','entity', [
                'label'     => '优惠券种类',
                'class'     => 'AutoManagerBundle:CouponKind',
                'property'  => 'name'
            ])
            ->setAction($this->generateUrl('auto_admin_coupon_activity_create_kind',['id'=>$id]))
            ->setMethod('post')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();
            $kind = $data['kind'];
            $activity->addKind($kind);
            $man = $this->getDoctrine()->getManager();
            $man->persist($activity);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_coupon_activity_list'));
        }
        return ['form'  => $form->createView()];
    }



}