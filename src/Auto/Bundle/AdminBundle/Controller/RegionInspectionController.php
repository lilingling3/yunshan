<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 下午4:53
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Auto\Bundle\ManagerBundle\Form\InspectionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Inspection;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/regionInspection")
 */

class RegionInspectionController extends Controller {

    const PER_PAGE = 20;

    
    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_region_inspection_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Inspection')
                ->createQueryBuilder('i')
        ;

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

        $qb->join('i.rentalCar','r')
            ->join('r.rentalStation','rs')
            ->where($qb->expr()->in('rs.area',$areaIds));

        $inspections =
            new Paginator(
                $qb
                    ->select('i')
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($inspections) / self::PER_PAGE);
        return ['inspections'=>$inspections,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_region_inspection_new")
     * @Template()
     */
    public function newAction(Request $request)
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
        $inspection = new Inspection();

        $form = $this->createFormBuilder($inspection)
            ->add('inspectionTime','datetime', array(
                'placeholder' => '车检到期日期',
            ))
            ->getForm();

        return ['form'  => $form->createView(),'rentalcars'=>$rentalcars];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_region_inspection_create")
     * @Template("AutoAdminBundle:RegionInspection:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $inspection = new \Auto\Bundle\ManagerBundle\Entity\Inspection();

        $form = $this->createFormBuilder($inspection)
            ->add('inspectionTime','datetime', array(
                'placeholder' => '车检到期日期',
            ))
            ->getForm();

        $form->handleRequest($req);

        $rentalCarId = $req->request->getInt('rentalCar');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);

        if ($form->isValid() && !empty($rentalCar)) {
            $inspection->setRentalCar($rentalCar);
            $man = $this->getDoctrine()->getManager();
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_inspection_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_region_inspection_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
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
        $form = $this->createFormBuilder($inspection)
            ->add('inspectionTime','datetime', array(
                'placeholder' => '车检到期日期',
            ))
            ->getForm();
        return [
            'form'  => $form->createView(),
            'rentalcars'=>$rentalcars,
            'inspection'=>$inspection
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_region_inspection_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RegionInspection:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $form = $this->createFormBuilder($inspection)
            ->add('inspectionTime','datetime', array(
                'placeholder' => '车检到期日期',
            ))
            ->getForm();
        $form->handleRequest($req);
        $rentalCarId = $req->request->getInt('rentalCar');
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($rentalCarId);

        if ($form->isValid() && !empty($rentalCar)) {

            $man = $this->getDoctrine()->getManager();
            $inspection->setRentalCar($rentalCar);
            $man->persist($inspection);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_region_inspection_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_region_inspection_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $inspection = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->find($id);
        $man->remove($inspection);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_region_inspection_list'));
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_region_inspection_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RegionInspection:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $inspection = new \Auto\Bundle\ManagerBundle\Entity\Inspection();

        $inspection->setRentalCar($rentalcar);

        $form = $this->createForm(new InspectionType(), $inspection, [
            'action' => $this->generateUrl('auto_admin_region_inspection_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_region_inspection_list_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RegionInspection:renderList.html.twig")
     */
    public function listByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $inspections = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Inspection')
            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);

        return ['inspections'=>$inspections,'rentalcar' => $rentalcar];
    }


}