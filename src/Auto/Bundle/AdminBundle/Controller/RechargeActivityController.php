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
use Auto\Bundle\ManagerBundle\Entity\RechargeActivity;
use Auto\Bundle\ManagerBundle\Entity\RechargePriceStep;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\RechargeActivityType;
use Auto\Bundle\ManagerBundle\Form\RechargePriceStepType;

/**
 * @Route("/rechargeActivity")
 */
class RechargeActivityController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rechargeActivity_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

        $startTime = $req->query->get('startTime');
        $endTime = $req->query->get('endTime');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargeActivity')
                ->createQueryBuilder('r')
        ;

        if($startTime&&$endTime){
            $qb
                ->andWhere($qb->expr()->gte('r.startTime', ':start_time'))
                ->andWhere($qb->expr()->lte('r.startTime', ':end_time'))
                ->setParameter('start_time', $startTime)
                ->setParameter('end_time', $endTime)
            ;

        }
        $recharges =
            new Paginator(
                $qb
                    ->select('r')
                    ->orderBy('r.id', 'DESC')
                    ->andWhere($qb->expr()->neq('r.id',':default'))
                    ->setParameter('default',1)
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($recharges) / self::PER_PAGE);
        return ['recharges'=>$recharges,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_rechargeActivity_new")
     * @Template()
     */
    public function newAction()
    {
        $recharge = new \Auto\Bundle\ManagerBundle\Entity\RechargeActivity();

        $priceStep = [];
        for ($i=0; $i < 6; $i++) { 
            $priceStep[] = new \Auto\Bundle\ManagerBundle\Entity\RechargePriceStep();
        }
            
        $form = $this->createFormBuilder()
            ->add('formType', new RechargeActivityType(),['data'=>$recharge])
            ->add('priceStep1', new RechargePriceStepType(),['data'=>$priceStep[0]])
            ->add('priceStep2', new RechargePriceStepType(),['data'=>$priceStep[1]])
            ->add('priceStep3', new RechargePriceStepType(),['data'=>$priceStep[2]])
            ->add('priceStep4', new RechargePriceStepType(),['data'=>$priceStep[3]])
            ->add('priceStep5', new RechargePriceStepType(),['data'=>$priceStep[4]])
            ->add('priceStep6', new RechargePriceStepType(),['data'=>$priceStep[5]])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_rechargeActivity_create'))
            ->setMethod('POST')
            ->getForm();


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/create", methods="POST", name="auto_admin_rechargeActivity_create")
     * @Template("AutoAdminBundle:RechargeActivity:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $recharge = new \Auto\Bundle\ManagerBundle\Entity\RechargeActivity();

        $priceStep = [];
        for ($i=0; $i < 6; $i++) { 
            $priceStep[] = new \Auto\Bundle\ManagerBundle\Entity\RechargePriceStep();
        }
            
        $form = $this->createFormBuilder()
            ->add('formType', new RechargeActivityType(),['data'=>$recharge])
            ->add('priceStep1', new RechargePriceStepType(),['data'=>$priceStep[0]])
            ->add('priceStep2', new RechargePriceStepType(),['data'=>$priceStep[1]])
            ->add('priceStep3', new RechargePriceStepType(),['data'=>$priceStep[2]])
            ->add('priceStep4', new RechargePriceStepType(),['data'=>$priceStep[3]])
            ->add('priceStep5', new RechargePriceStepType(),['data'=>$priceStep[4]])
            ->add('priceStep6', new RechargePriceStepType(),['data'=>$priceStep[5]])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_rechargeActivity_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {

            $data = $form->getData();


            $recharge = $data['formType'];
            $recharge->setName($recharge->getName());
            $recharge->setStartTime(new \DateTime($data['startTime']));
            $recharge->setEndTime(new \DateTime($data['endTime']));
            $recharge->setDiscount(0);

            $man = $this->getDoctrine()->getManager();
            $man->persist($recharge);
            $man->flush();

            foreach ($priceStep as $key => $value) {
                $value->setActivity($recharge);
                $value->setStep($key + 1);

                $man->persist($value);
                $man->flush();
                // $man->clear();
            }

            return $this->redirect($this->generateUrl('auto_admin_rechargeActivity_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rechargeActivity_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $recharge = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeActivity')
            ->find($id);

        if (empty($recharge)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无活动!']
            );
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargePriceStep')
                ->createQueryBuilder('r')
        ;

        $priceStep =
            $qb
                ->select('r')
                ->orderBy('r.step', 'ASC')
                ->where($qb->expr()->eq('r.activity', ':activity'))
                ->setParameter('activity', $recharge)
                ->getQuery()
                ->getResult()
        ;
  
        $form = $this->createFormBuilder()
            ->add('formType', new RechargeActivityType(),['data'=>$recharge])
            ->add('priceStep1', new RechargePriceStepType(),['data'=>$priceStep[0]])
            ->add('priceStep2', new RechargePriceStepType(),['data'=>$priceStep[1]])
            ->add('priceStep3', new RechargePriceStepType(),['data'=>$priceStep[2]])
            ->add('priceStep4', new RechargePriceStepType(),['data'=>$priceStep[3]])
            ->add('priceStep5', new RechargePriceStepType(),['data'=>$priceStep[4]])
            ->add('priceStep6', new RechargePriceStepType(),['data'=>$priceStep[5]])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $recharge->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $recharge->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_rechargeActivity_update', ['id' => $recharge->getId()]))
            ->setMethod('POST')
            ->getForm();

        return ['form'=>$form->createView(),'activityId'=>$recharge->getId()];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_rechargeActivity_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RechargeActivity:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $recharge = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeActivity')
            ->find($id);

        if (empty($recharge)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无活动!']
            );
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargePriceStep')
                ->createQueryBuilder('r')
        ;

        $priceStep =
            $qb
                ->select('r')
                ->orderBy('r.step', 'ASC')
                ->where($qb->expr()->eq('r.activity', ':activity'))
                ->setParameter('activity', $recharge)
                ->getQuery()
                ->getResult()
        ;

        $form = $this->createFormBuilder()
            ->add('formType', new RechargeActivityType(),['data'=>$recharge])
            ->add('priceStep1', new RechargePriceStepType(),['data'=>$priceStep[0]])
            ->add('priceStep2', new RechargePriceStepType(),['data'=>$priceStep[1]])
            ->add('priceStep3', new RechargePriceStepType(),['data'=>$priceStep[2]])
            ->add('priceStep4', new RechargePriceStepType(),['data'=>$priceStep[3]])
            ->add('priceStep5', new RechargePriceStepType(),['data'=>$priceStep[4]])
            ->add('priceStep6', new RechargePriceStepType(),['data'=>$priceStep[5]])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $recharge->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $recharge->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_rechargeActivity_update', ['id' => $recharge->getId()]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {
            $data = $form->getData();

            $recharge = $data['formType'];
            $recharge->setName($recharge->getName());
            $recharge->setStartTime(new \DateTime($data['startTime']));
            $recharge->setEndTime(new \DateTime($data['endTime']));
            $recharge->setDiscount(0);

            $man = $this->getDoctrine()->getManager();
            $man->persist($recharge);
            $man->flush();

            foreach ($priceStep as $key => $value) {
                $value->setActivity($recharge);
                $value->setStep($key + 1);

                // $man->persist($value);
                $man->flush();
                // $man->clear();
            }

            return $this->redirect($this->generateUrl('auto_admin_rechargeActivity_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/show{id}", methods="GET", name="auto_admin_rechargeActivity_show",requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id)
    {

        $activity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeActivity')
            ->find($id);

        if (empty($activity)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无活动!']
            );
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RechargePriceStep')
                ->createQueryBuilder('r')
        ;

        $recharge_step =
            $qb
                ->select('r')
                ->orderBy('r.step', 'ASC')
                ->where($qb->expr()->eq('r.activity', ':activity'))
                ->setParameter('activity', $activity)
                ->getQuery()
                ->getResult()
        ;

        if (empty($recharge_step)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无阶梯数据!']
            );
        }

        $activity = call_user_func($this->get("auto_manager.recharge_helper")->get_recharge_activity_normalizer(),
                    $activity);


        if (empty($recharge_step)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无数据!']
            );
        }

        $step_price = array_map( $this->get('auto_manager.recharge_helper')->get_recharge_price_step_normalizer(), $recharge_step);

        return ['recharge'=>$activity,'stepPrice'=>$step_price];
    }

    /**
     * @Route("/delete{id}", methods="POST", name="auto_admin_rechargeActivity_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        echo $id;exit;
        $activity = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargeActivity')
            ->find($id);

        if (empty($activity)) {
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'无活动!']
            );
        }

        $recharge_step = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RechargePriceStep')
            ->delete(['activity'=>$activity]);

        return [];
    }
}