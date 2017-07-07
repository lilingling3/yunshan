<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/14
 * Time: 下午6:04
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Auto\Bundle\ManagerBundle\Entity\SettleClaim;
use Auto\Bundle\ManagerBundle\Form\SettleClaimType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/settleClaim")
 */
class SettleClaimController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_settle_claim_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');


        $licensePlaces=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:SettleClaim')
                ->createQueryBuilder('m')
                ->select('m')
        ;


            if($licensePlace&&$plate_number){
                $qb->join('m.maintenanceRecord','i')
                    ->join('i.rentalCar','r')
                    ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                    ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                    ->setParameter('licensePlate', $plate_number)
                    ->setParameter('licensePlace', $licensePlace);
            }
        $claimRecords =
            new Paginator(
                $qb
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($claimRecords) / self::PER_PAGE);

        return ['claimRecords'=>$claimRecords,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new/{id}", methods="GET", name="auto_admin_settle_claim_new",requirements={"id"="\d+"})
     * @Template()
     */
    public function newAction($id,Request $req)
    {
        $maintenanceParent = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $parentPlateNumber = $maintenanceParent->getRentalCar()->getLicense();
        $form = $this->createFormBuilder([])
            ->add('formType', new SettleClaimType())
            ->add('downTime', 'text', ['label'  => '事故时间','data' => '','required' => false])
            ->add('applyTime', 'text', ['label'  => '报案时间','data' => '','required' => false])
            ->add('settleTime', 'text', ['label'  => '交案时间','data' => '','required' => false])
            ->add('claimTime', 'text', ['label'  => '理赔时间','data' => '','required' => false])
            ->setAction($this->generateUrl('auto_admin_settle_claim_create', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();
        return ['form'  => $form->createView(),'parentPlateNumber'=>$parentPlateNumber];
    }

    /**
     * @Route("/new/{id}", methods="POST", name="auto_admin_settle_claim_create",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:SettleClaim:new.html.twig")
     */
    public function createAction(Request $req,$id)
    {
        $maintenanceParent = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        if(empty($maintenanceParent)){
            return $this->render(
                "AutoAdminBundle:Default:message.html.twig",
                ['message'=>'非法操作!']
            );
        }


        $form = $this->createFormBuilder([])
            ->add('formType', new SettleClaimType())
            ->add('downTime', 'text', ['label'  => '事故时间','data' => '','required' => false])
            ->add('applyTime', 'text', ['label'  => '报案时间','data' => '','required' => false])
            ->add('settleTime', 'text', ['label'  => '交案时间','data' => '','required' => false])
            ->add('claimTime', 'text', ['label'  => '理赔时间','data' => '','required' => false])
            ->setAction($this->generateUrl('auto_admin_settle_claim_create', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();
        $settleClaim = new \Auto\Bundle\ManagerBundle\Entity\SettleClaim();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $settleClaim = $data['formType'];
            $man = $this->getDoctrine()->getManager();

            $settleClaim->setMaintenanceRecord($maintenanceParent);
            if($data['downTime']){
                $settleClaim->setDownTime(new \DateTime($data['downTime']));
            }else{
                $settleClaim->setDownTime(null);
            }
            if($data['applyTime']){
                $settleClaim->setApplyTime(new \DateTime($data['applyTime']));
            }else{
                $settleClaim->setApplyTime(null);
            }
            if($data['settleTime']){
                $settleClaim->setSettleTime(new \DateTime($data['settleTime']));
            }else{
                $settleClaim->setSettleTime(null);
            }
            if($data['claimTime']){
                $settleClaim->setClaimTime(new \DateTime($data['claimTime']));
            }else{
                $settleClaim->setClaimTime(null);
            }
            $man->persist($settleClaim);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_settle_claim_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);
        $parentPlateNumber = $settleClaim->getMaintenanceRecord()->getRentalCar()->getLicense();

        $downTime = '';
        if($settleClaim->getDownTime()){
            $downTime = $settleClaim->getDownTime()->format('Y-m-d H:i:s');
        }
        $applyTime = '';
        if($settleClaim->getApplyTime()){
            $applyTime = $settleClaim->getApplyTime()->format('Y-m-d H:i:s');
        }
        $settleTime = '';
        if($settleClaim->getSettleTime()){
            $settleTime = $settleClaim->getSettleTime()->format('Y-m-d H:i:s');
        }
        $claimTime = '';
        if($settleClaim->getClaimTime()){
            $claimTime = $settleClaim->getClaimTime()->format('Y-m-d H:i:s');
        }
        $form = $this->createFormBuilder([])
            ->add('formType', new SettleClaimType(),['data'=>$settleClaim])
            ->add('downTime', 'text', ['label'  => '事故时间','data' => $downTime,'required' => false])
            ->add('applyTime', 'text', ['label'  => '报案时间','data' => $applyTime,'required' => false])
            ->add('settleTime', 'text', ['label'  => '交案时间','data' => $settleTime,'required' => false])
            ->add('claimTime', 'text', ['label'  => '理赔时间','data' => $claimTime,'required' => false])
            ->setAction($this->generateUrl('auto_admin_settle_claim_update', ['id' =>  $settleClaim->getId()]))
            ->setMethod("POST")
            ->getForm();

        return [
            'form'  => $form->createView(),'parentPlateNumber'=>$parentPlateNumber
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_settle_claim_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);
        $form = $this->createFormBuilder([])
            ->add('formType', new SettleClaimType(),['data'=>$settleClaim])
            ->add('downTime', 'text', ['label'  => '事故时间','data' => '','required' => false])
            ->add('applyTime', 'text', ['label'  => '报案时间','data' => '','required' => false])
            ->add('settleTime', 'text', ['label'  => '交案时间','data' => '','required' => false])
            ->add('claimTime', 'text', ['label'  => '理赔时间','data' => '','required' => false])
            ->setAction($this->generateUrl('auto_admin_settle_claim_update', ['id' =>  $settleClaim->getId()]))
            ->setMethod("POST")
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();
            $settleClaim = $data['formType'];
            $man = $this->getDoctrine()->getManager();

            if($data['downTime']){
                $settleClaim->setDownTime(new \DateTime($data['downTime']));
            }else{
                $settleClaim->setDownTime(null);
            }
            if($data['applyTime']){
                $settleClaim->setApplyTime(new \DateTime($data['applyTime']));
            }else{
                $settleClaim->setApplyTime(null);
            }
            if($data['settleTime']){
                $settleClaim->setSettleTime(new \DateTime($data['settleTime']));
            }else{
                $settleClaim->setSettleTime(null);
            }
            if($data['claimTime']){
                $settleClaim->setClaimTime(new \DateTime($data['claimTime']));
            }else{
                $settleClaim->setClaimTime(null);
            }

            $man->persist($settleClaim);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_settle_claim_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_settle_claim_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $settleClaim = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:SettleClaim')
            ->find($id);
        $man->remove($settleClaim);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_settle_claim_list'));
    }




}