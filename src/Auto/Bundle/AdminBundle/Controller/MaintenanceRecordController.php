<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\MaintenanceRecordType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/maintenanceRecord")
 */

class MaintenanceRecordController extends Controller {

    const PER_PAGE = 20;

    
    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_maintenance_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {

        $type = $req->query->getInt('type');
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MaintenanceRecord')
                ->createQueryBuilder('m')
        ;

        if($type){
            $qb
                ->andWhere( $qb->expr()->eq('m.kind',':kind') )
                ->setParameter('kind', $type);
        }

        if($licensePlace&&$plate_number){
            $qb->join('m.rentalCar','r')
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                ->setParameter('licensePlate', $plate_number)
                ->setParameter('licensePlace', $licensePlace);
        }

        $maintenances =
            new Paginator(
                $qb
                    ->select('m')
                    ->andWhere( $qb->expr()->isNull('m.parent') )
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $thirdCar=[];
        foreach($maintenances as $maintenance){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:MaintenanceRecord')
                    ->createQueryBuilder('m');

        if(!isset($thirdCar[$maintenance->getId()])){$thirdCar[$maintenance->getId()]=0;}

            $thirdCarResult =
                $qb
                    ->select('m')
                    ->where($qb->expr()->eq('m.parent', ':parent'))
                    ->setParameter('parent', $maintenance)
                    ->getQuery()
                    ->getResult()
            ;
            $thirdCar[$maintenance->getId()]=count($thirdCarResult);
        }

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();


        $total = ceil(count($maintenances) / self::PER_PAGE);
        return ['maintenances'=>$maintenances,'thirdCar'=>$thirdCar,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_maintenance_record_new")
     * @Template()
     */
    public function newAction()
    {
        $maintenance = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord();
        $form = $this->createFormBuilder()
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name'
            ])
            ->add('plateNumber', 'text', ['label'  => '车牌号','data' => ''])
            ->add('downTime', 'text', ['label'  => '事故/故障时间','data' => ''])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => '','required' => false])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_create'))
            ->setMethod('POST')
            ->getForm();

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_maintenance_record_create")
     * @Template("AutoAdminBundle:MaintenanceRecord:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $maintenance = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord();

        $form = $this->createFormBuilder()
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name'
            ])
            ->add('plateNumber', 'text', ['label'  => '车牌号','data' => ''])
            ->add('downTime', 'text', ['label'  => '事故/故障时间','data' => ''])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => '','required' => false])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            //查询车辆
            $rentalCar = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findOneBy(['licensePlace' => $data['licensePlace'],'licensePlate'=>$data['plateNumber'] ]);
            if(empty($rentalCar)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'车辆不存在']
                );
            }
            $maintenance = $data['formType'];
            $maintenance->setRentalCar($rentalCar);
            $maintenance->setDownTime(new \DateTime($data['downTime']));
            if($data['maintenanceTime']){
                $maintenance->setMaintenanceTime(new \DateTime($data['maintenanceTime']));
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($maintenance);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_maintenance_record_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);

        $maintenanceTime = '';
        if($maintenance->getMaintenanceTime()){
            $maintenanceTime = $maintenance->getMaintenanceTime()->format('Y-m-d H:i:s');
        }

        $form = $this->createFormBuilder()
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name',
                'data' => $maintenance->getRentalCar()->getLicensePlace()
            ])
            ->add('plateNumber', 'text', ['label'  => '车牌号','data' => $maintenance->getRentalCar()->getLicensePlate()])
            ->add('downTime', 'text', ['label'  => '事故/故障时间','data' => $maintenance->getDownTime()->format('Y-m-d H:i:s')])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => $maintenanceTime,'required' => false])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_update', ['id' => $maintenance->getId()]))
            ->setMethod('POST')
            ->getForm();

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_maintenance_record_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $maintenanceTime = '';
        if($maintenance->getMaintenanceTime()){
            $maintenanceTime = $maintenance->getMaintenanceTime()->format('Y-m-d H:i:s');
        }

        $form = $this->createFormBuilder()
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('licensePlace','entity', [
                'label'     => '车牌归属地',
                'class'     => 'AutoManagerBundle:LicensePlace',
                'property'  => 'name',
                'data' => $maintenance->getRentalCar()->getLicensePlace()
            ])
            ->add('plateNumber', 'text', ['label'  => '车牌号','data' => $maintenance->getRentalCar()->getLicensePlate()])
            ->add('downTime', 'text', ['label'  => '事故/故障时间','data' => $maintenance->getDownTime()->format('Y-m-d H:i:s')])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => $maintenanceTime,'required' => false])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_update', ['id' => $maintenance->getId()]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {

            $data = $form->getData();
            //查询车辆
            $rentalCar = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->findOneBy(['licensePlace' => $data['licensePlace'],'licensePlate'=>$data['plateNumber'] ]);
            if(empty($rentalCar)){
                return $this->render(
                    "AutoAdminBundle:Default:message.html.twig",
                    ['message'=>'车辆不存在']
                );
            }
            $maintenance = $data['formType'];
            $maintenance->setRentalCar($rentalCar);
            $maintenance->setDownTime(new \DateTime($data['downTime']));
            if($data['maintenanceTime']){
                $maintenance->setMaintenanceTime(new \DateTime($data['maintenanceTime']));
            }else{
                $maintenance->setMaintenanceTime(null);
            }
            $man = $this->getDoctrine()->getManager();
            $man->persist($maintenance);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_maintenance_record_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $man->remove($maintenance);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_maintenance_record_list'));
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_maintenance_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:MaintenanceRecord:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $maintenance = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord();

        $maintenance->setRentalCar($rentalcar);

        $form = $this->createFormBuilder()
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('downTime', 'text', ['label'  => '事故/故障时间','data' => ''])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_create'))
            ->setMethod('POST')
            ->getForm();

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_maintenance_record_list_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:MaintenanceRecord:renderList.html.twig")
     */
    public function listByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $maintenances = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);

        return ['maintenances'=>$maintenances,'rentalcar' => $rentalcar];
    }


    /**
     * @Route("/thirdList/{page}", methods="GET", name="auto_admin_maintenance_record_third_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function thirdListAction(Request $req,$page = 1)
    {

        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MaintenanceRecord')
                ->createQueryBuilder('m')
        ;


        if($licensePlace&&$plate_number){
            $qb->join('m.parent','i')
                ->join('i.rentalCar','r')
                ->andWhere( $qb->expr()->eq('r.licensePlate',':licensePlate') )
                ->andWhere( $qb->expr()->eq('r.licensePlace',':licensePlace') )
                ->setParameter('licensePlate', $plate_number)
                ->setParameter('licensePlace', $licensePlace);
        }

        $maintenances =
            new Paginator(
                $qb
                    ->select('m')
                    ->andWhere( $qb->expr()->isNotNull('m.parent') )
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $licensePlaces=$this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->findAll();

        $total = ceil(count($maintenances) / self::PER_PAGE);
        return ['maintenances'=>$maintenances,'licensePlaces'=>$licensePlaces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/thirdNew/{id}", methods="GET", name="auto_admin_maintenance_record_third_new")
     * @Template()
     */
    public function thirdNewAction($id)
    {
        $maintenanceParent = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $parentPlateNumber = $maintenanceParent->getRentalCar()->getLicense();
        $form = $this->createFormBuilder([])
            ->add('formType', new MaintenanceRecordType())
            ->add('downTime', 'text', ['label'  => '事故时间','data' => ''])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => '','required' => false])
            ->add('thirdPartyLicensePlate', 'text', ['label'  => '三者车车牌号','data' => ''])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_create_third', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();
        return ['form'  => $form->createView(),'parentPlateNumber'=>$parentPlateNumber];
    }

    /**
     * @Route("/thirdNew/{id}", methods="POST", name="auto_admin_maintenance_record_create_third")
     * @Template("AutoAdminBundle:MaintenanceRecord:thirdNew.html.twig")
     */
    public function createThirdAction(Request $req,$id)
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
            ->add('formType', new MaintenanceRecordType())
            ->add('downTime', 'text', ['label'  => '事故时间','data' => ''])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => '','required' => false])
            ->add('thirdPartyLicensePlate', 'text', ['label'  => '三者车车牌号','data' => ''])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_create_third', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();


        $form->handleRequest($req);
        $MR = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord;

        if ($form->isValid()) {
            $data = $form->getData();
            $MR = $data['formType'];
            $man = $this->getDoctrine()->getManager();

            $MR->setThirdPartyLicensePlate($data['thirdPartyLicensePlate']);
            $MR->setParent($maintenanceParent);
            if($data['maintenanceTime']){
                $MR->setMaintenanceTime(new \DateTime($data['maintenanceTime']));
            }else{
                $MR->setMaintenanceTime(null);
            }
            $MR->setDownTime(new \DateTime($data['downTime']));
            $man->persist($MR);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/thirdEdit/{id}", methods="GET", name="auto_admin_maintenance_record_third_edit")
     * @Template()
     */
    public function thirdEditAction($id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $parentPlateNumber = $maintenance->getParent()->getRentalCar()->getLicense();
        $maintenanceTime = '';
        if($maintenance->getMaintenanceTime()){
            $maintenanceTime = $maintenance->getMaintenanceTime()->format('Y-m-d H:i:s');
        }
        $form = $this->createFormBuilder([])
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('downTime', 'text', ['label'  => '事故时间','data' => $maintenance->getDownTime()->format('Y-m-d H:i:s')])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => $maintenanceTime,'required' => false])
            ->add('thirdPartyLicensePlate', 'text', ['label'  => '三者车车牌号','data' => $maintenance->getThirdPartyLicensePlate()])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_update_third', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();
        return ['form'  => $form->createView(),'parentPlateNumber'=>$parentPlateNumber];
    }

    /**
     * @Route("/thirdEdit/{id}", methods="POST", name="auto_admin_maintenance_record_update_third")
     * @Template("AutoAdminBundle:MaintenanceRecord:thirdEdit.html.twig")
     */
    public function updateThirdAction(Request $req,$id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $parentPlateNumber = $maintenance->getParent()->getRentalCar()->getLicense();
        $maintenanceTime = '';
        if($maintenance->getMaintenanceTime()){
            $maintenanceTime = $maintenance->getMaintenanceTime()->format('Y-m-d H:i:s');
        }
        $form = $this->createFormBuilder([])
            ->add('formType', new MaintenanceRecordType(),['data'=>$maintenance])
            ->add('downTime', 'text', ['label'  => '事故时间','data' => $maintenance->getDownTime()->format('Y-m-d H:i:s')])
            ->add('maintenanceTime', 'text', ['label'  => '维修时间','data' => $maintenanceTime,'required' => false])
            ->add('thirdPartyLicensePlate', 'text', ['label'  => '三者车车牌号','data' => $maintenance->getThirdPartyLicensePlate()])
            ->setAction($this->generateUrl('auto_admin_maintenance_record_update_third', ['id' => $id]))
            ->setMethod("POST")
            ->getForm();


        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $maintenance = $data['formType'];
            $man = $this->getDoctrine()->getManager();

            $maintenance->setThirdPartyLicensePlate($data['thirdPartyLicensePlate']);
            if($data['maintenanceTime']){
                $maintenance->setMaintenanceTime(new \DateTime($data['maintenanceTime']));
            }else{
                $maintenance->setMaintenanceTime(null);
            }
            $maintenance->setDownTime(new \DateTime($data['downTime']));
            $man->persist($maintenance);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_maintenance_record_third_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/delThird/{id}", methods="GET", name="auto_admin_maintenance_record_third_delete",requirements={"id"="\d+"})
     */
    public function deleteThirdAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $man->remove($maintenance);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_maintenance_record_third_list'));
    }





}