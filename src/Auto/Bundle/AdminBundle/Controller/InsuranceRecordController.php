<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/22
 * Time: 上午10:05
 */

namespace Auto\Bundle\AdminBundle\Controller;
use Auto\Bundle\ManagerBundle\Form\InsuranceRecordType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\InsuranceRecord;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/insuranceRecord")
 */

class InsuranceRecordController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_insurance_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $satrtTime = $req->query->get('startTime');
        $endTime = $req->query->get('endTime');
        $rentalCarId = $req->query->get('rentalcarid');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:InsuranceRecord')
                ->createQueryBuilder('i')
        ;
        if($satrtTime&&$endTime){
            $qb
                ->andWhere($qb->expr()->gte('i.insuranceTime', ':satrtTime'))
                ->andWhere($qb->expr()->lte('i.insuranceTime', ':endTime'))
                ->setParameter('endTime', $endTime)
                ->setParameter('satrtTime', $satrtTime);
        }
        if($rentalCarId){
            $rentalCar = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($rentalCarId);

            $qb
                ->andWhere($qb->expr()->eq('i.rentalCar', ':rentalCar'))
                ->setParameter('rentalCar', $rentalCar);
        }

        $insurances =
            new Paginator(
                $qb
                    ->select('i')
                    ->orderBy('i.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($insurances) / self::PER_PAGE);
        return ['rentalcarid'=>$rentalCarId,'insurances'=>$insurances,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_insurance_record_new")
     * @Template()
     */
    public function newAction(Request $req)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $rentalCar = '';
        $url = $this->generateUrl('auto_admin_insurance_record_create');
        if($rentalCarId){
            $url = $this->generateUrl('auto_admin_insurance_record_create', ['rentalcarid' => $rentalCarId]);
            $rentalCar = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalCar')
                ->find($rentalCarId);
        }
        $form = $this->createForm(new InsuranceRecordType(), null, [
            'action' => $url,
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalCar];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_insurance_record_create")
     * @Template("AutoAdminBundle:InsuranceRecord:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $url = $this->generateUrl('auto_admin_insurance_record_list');
        if($rentalCarId){
            $url = $this->generateUrl('auto_admin_insurance_record_list', ['rentalcarid' => $rentalCarId]);
        }
        $insurance = new \Auto\Bundle\ManagerBundle\Entity\InsuranceRecord();

        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $this->generateUrl('auto_admin_insurance_record_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($insurance);
            $man->flush();

            return $this->redirect($url);
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_insurance_record_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction(Request $req,$id)
    {
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $rentalCarId = $req->query->get('rentalcarid');
        $url = $this->generateUrl('auto_admin_insurance_record_update', ['id' => $insurance->getId()]);
        if($rentalCarId){
            $url = $this->generateUrl('auto_admin_insurance_record_update', ['id' => $insurance->getId(),'rentalcarid' => $rentalCarId]);
        }
        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $url,
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_insurance_record_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $this->generateUrl('auto_admin_insurance_record_update', ['id' => $insurance->getId()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($insurance);
            $man->flush();
            $rentalCarId = $req->query->get('rentalcarid');
            $url = $this->generateUrl('auto_admin_insurance_record_list');
            if($rentalCarId){
                $url = $this->generateUrl('auto_admin_insurance_record_list', ['rentalcarid' => $rentalCarId]);
            }
            return $this->redirect($url);
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_insurance_record_delete",requirements={"id"="\d+"})
     */
    public function deleteAction(Request $req, $id)
    {
        $rentalCarId = $req->query->get('rentalcarid');
        $url = $this->generateUrl('auto_admin_insurance_record_list');
        if($rentalCarId){
            $url = $this->generateUrl('auto_admin_insurance_record_list', ['rentalcarid' => $rentalCarId]);
        }
        $man = $this->getDoctrine()->getManager();
        $insurance = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->find($id);
        $man->remove($insurance);
        $man->flush();

        return $this->redirect($url);
    }

    /**
     * @Route("/add/rentalcar/{id}", methods="GET", name="auto_admin_insurance_add_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:InsuranceRecord:new.html.twig")
     */
    public function addByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $insurance = new \Auto\Bundle\ManagerBundle\Entity\InsuranceRecord();

        $insurance->setRentalCar($rentalcar);

        $form = $this->createForm(new InsuranceRecordType(), $insurance, [
            'action' => $this->generateUrl('auto_admin_insurance_record_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalcar' => $rentalcar];
    }

    /**
     * @Route("/list/rentalcar/{id}", methods="GET", name="auto_admin_insurance_record_list_by_rentalcar",
     * requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:InsuranceRecord:renderList.html.twig")
     */
    public function listByRentalCarAction($id)
    {
        $rentalcar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($id);

        $insurances = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:InsuranceRecord')
            ->findBy(['rentalCar'=>$rentalcar],['id'=>'desc']);

        return ['insurances'=>$insurances,'rentalcar' => $rentalcar];
    }



}