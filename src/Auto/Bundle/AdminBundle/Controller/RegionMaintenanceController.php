<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\RegionMaintenanceRecordType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/regionMaintenance")
 */

class RegionMaintenanceController extends Controller {

    const PER_PAGE = 20;

    
    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_maintenance_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $type = $req->query->getInt('type');
        $licensePlace = $req->query->get('licensePlace');
        $plate_number = $req->query->get('rentalCarId');

        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MaintenanceRecord')
                ->createQueryBuilder('m')
        ;
        $qb->join('m.rentalCar','r')
            ->join('r.rentalStation','rs')
            ->where($qb->expr()->in('rs.area',$areaIds));

        if($type){
            $qb
                ->andWhere( $qb->expr()->eq('m.kind',':kind') )
                ->setParameter('kind', $type);
        }

        if($licensePlace&&$plate_number){
            $qb
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
     * @Route("/new", methods="GET", name="auto_admin_region_maintenance_record_new")
     * @Template()
     */
    public function newAction()
    {
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $areaIds = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $areaIds[] = $oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $areaIds[] = $c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $areaIds[] = $c2->getId();
                            }
                        }
                    }
                }
            }
        }
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->createQueryBuilder('c')
                ->select('c')
        ;
        $rentalcars = $qb
            ->join('c.rentalStation','s')
            ->where($qb->expr()->in('s.area',$areaIds))
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
        $form = $this->createForm(new RegionMaintenanceRecordType(), null, [
            'action' => $this->generateUrl('auto_admin_region_maintenance_record_create'),
            'method' => 'POST'
        ]);
        return ['form'  => $form->createView(),'rentalcars'=>$rentalcars];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_region_maintenance_record_create")
     * @Template("AutoAdminBundle:MaintenanceRecord:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $maintenance = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord();

        $form = $this->createForm(new RegionMaintenanceRecordType(), $maintenance, [
            'action' => $this->generateUrl('auto_admin_region_maintenance_record_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        $rentalCarId = $req->request->getInt('rentalCar');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);

        if ($form->isValid() && !empty($rentalCar)) {
            $maintenance->setRentalCar($rentalCar);
            $man = $this->getDoctrine()->getManager();
            $man->persist($maintenance);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_region_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_region_maintenance_record_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $form = $this->createForm(new RegionMaintenanceRecordType(), $maintenance, [
            'action' => $this->generateUrl('auto_admin_region_maintenance_record_update', ['id' => $maintenance->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_region_maintenance_record_update",requirements={"id"="\d+"})
     * @Template()
     */
    public function updateAction(Request $req, $id)
    {
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $form = $this->createForm(new RegionMaintenanceRecordType(), $maintenance, [
            'action' => $this->generateUrl('auto_admin_region_maintenance_record_update', ['id' => $maintenance->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($maintenance);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_maintenance_record_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_region_maintenance_record_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $maintenance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MaintenanceRecord')
            ->find($id);
        $man->remove($maintenance);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_region_maintenance_record_list'));
    }
//
//    /**
//     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_region_maintenance_add_by_rentalcar",
//     * requirements={"id"="\d+"})
//     * @Template("AutoAdminBundle:MaintenanceRecord:new.html.twig")
//     */
//    public function addByRentalCarAction($id)
//    {
//        $rentalcar = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:RentalCar')
//            ->find($id);
//
//        $maintenance = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord();
//
//        $maintenance->setRentalCar($rentalcar);
//
//        $form = $this->createForm(new RegionMaintenanceRecordType(), $maintenance, [
//            'action' => $this->generateUrl('auto_admin_region_maintenance_record_create'),
//            'method' => 'POST'
//        ]);
//
//        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
//    }
//
//    /**
//     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_region_maintenance_record_list_by_rentalcar",
//     * requirements={"id"="\d+"})
//     * @Template("AutoAdminBundle:MaintenanceRecord:renderList.html.twig")
//     */
//    public function listByRentalCarAction($id)
//    {
//        $rentalcar = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:RentalCar')
//            ->find($id);
//
//        $maintenances = $this->getDoctrine()
//            ->getRepository('AutoManagerBundle:MaintenanceRecord')
//            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);
//
//        return ['maintenances'=>$maintenances,'rentalcar' => $rentalcar];
//    }
//
//    /**
//     * @Route("/thirdNew", methods="GET", name="auto_admin_region_maintenance_record_third_new")
//     * @Template()
//     */
//    public function thirdNewAction()
//    {
//        $form = $this->createFormBuilder([])
//            ->add('maintenanceRecord', new RegionMaintenanceRecordType())
//            ->add('thirdPartyLicensePlate')
//            ->add('parent','entity', [
//                'label'     => '车牌号',
//                'class'     => 'AutoManagerBundle:RentalCar',
//                'property'  => 'License'
//            ])
//            ->setAction($this->generateUrl('auto_admin_region_maintenance_record_create_third'))
//            ->setMethod("POST")
//            ->getForm();
//        return ['form'  => $form->createView()];
//    }
//
//    /**
//     * @Route("/thirdNew", methods="POST", name="auto_admin_region_maintenance_record_create_third")
//     * @Template("AutoAdminBundle:MaintenanceRecord:newThird.html.twig")
//     */
//    public function createThirdAction(Request $req)
//    {
//        $MaintenanceRecord = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord;
//        $form = $this->createFormBuilder([])
//            ->add('maintenanceRecord', new RegionMaintenanceRecordType(),['data'=>$MaintenanceRecord])
//            ->add('thirdPartyLicensePlate')
//            ->add('parent','entity', [
//                'label'     => '车牌号',
//                'class'     => 'AutoManagerBundle:RentalCar',
//                'property'  => 'License'
//            ])
//            ->setAction($this->generateUrl('auto_admin_region_maintenance_record_create_third'))
//            ->getForm();
//
//        $form->handleRequest($req);
//        $MR = new \Auto\Bundle\ManagerBundle\Entity\MaintenanceRecord;
//
//        if ($form->isValid()) {
//            $data = $form->getData();
//            $maintenanceRecord = $data['maintenanceRecord'];
//            $rentalCar=$data['parent'];
//            $man = $this->getDoctrine()->getManager();
//
//            $parentId=$this->getDoctrine()
//                ->getRepository('AutoManagerBundle:MaintenanceRecord')
//                ->findOneBy(['rentalCar'=>$rentalCar]);
//            $parent=$this->getDoctrine()
//                ->getRepository('AutoManagerBundle:MaintenanceRecord')
//                ->find($parentId->getId());
//            $MR->setThirdPartyLicensePlate($data['thirdPartyLicensePlate']);
//            $MR->setParent($parent);
//            $MR->setMaintenanceReason($maintenanceRecord->getMaintenanceReason());
//            $MR->setMaintenanceTime($maintenanceRecord->getMaintenanceTime());
//            $MR->setMaintenanceAmount($maintenanceRecord->getMaintenanceAmount());
//            $MR->setStatus(1);
//            //$MR->setRentalCar($maintenanceRecord->getRentalCar());
//            $MR->setKind(1);
//            $MR->setCompany($maintenanceRecord->getCompany());
//            $MR->setDownTime($maintenanceRecord->getDownTime());
//            $MR->setMaintenanceProject($maintenanceRecord->getMaintenanceProject());
//            $man->persist($MR);
//            $man->flush();
//
//            return $this->redirect($this->generateUrl('auto_admin_region_maintenance_record_list'));
//        }
//        return ['form'  => $form->createView()];
//    }





}