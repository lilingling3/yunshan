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
use Auto\Bundle\ManagerBundle\Entity\CarStartTbox;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\CarStartTboxType;

/**
 * @Route("/device")
 */
class DeviceController extends Controller {


    /**
     * @Route("/get/carStart/{id}", methods="GET", name="auto_admin_device_get_car_start")
     * @Template()
     */
    public function getAction($id)
    {
        $carStart = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->findOneBy(['rentalCar'=>$id]);

        if(empty($carStart)){
            return  $this->redirect($this->generateUrl("auto_admin_device_new_car_start",['cid'=>$id]));
        }else{
            return  $this->redirect($this->generateUrl("auto_admin_device_edit_car_start",['id'=>$carStart->getId()]));
        }
    }

    /**
     * @Route("/new/carStart/{cid}", methods="GET", name="auto_admin_device_new_car_start")
     * @Template()
     */
    public function newCarStartAction($cid)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($cid);

        $carStart = new CarStartTbox();
        $carStart->setRentalCar($rentalCar);

        $form = $this->createForm(new CarStartTboxType(), $carStart, [
            'action' => $this->generateUrl('auto_admin_device_new_create_start',['cid'=>$cid]),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView(),'rentalCar'=>$rentalCar];
    }

    /**
     * @Route("/new/carStart/{cid}", methods="POST", name="auto_admin_device_new_create_start")
     * @Template("AutoAdminBundle:Device:newCarStart.html.twig")
     */
    public function createCarStartAction(Request $req,$cid)
    {
        $rentalCar = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalCar')
            ->find($cid);

        $carStart = new CarStartTbox();
        $carStart->setRentalCar($rentalCar);

        $form = $this->createForm(new CarStartTboxType(), $carStart, [
            'action' => $this->generateUrl('auto_admin_device_new_create_start',['cid'=>$cid]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($carStart);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));
        }
            return ['form'  => $form->createView(),'rentalCar'=>$rentalCar];
    }


    /**
     * @Route("/edit/carStart/{id}", methods="GET", name="auto_admin_device_edit_car_start",requirements={"id"="\d+"})
     * @Template()
     */
    public function editCarStartAction($id)
    {
        $carStart = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->find($id);

        $form = $this->createForm(new CarStartTboxType(), $carStart, [
            'action' => $this->generateUrl('auto_admin_device_edit_car_update', ['id' => $carStart->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView(),
            'rentalCar'=>$carStart->getRentalCar()
        ];
    }


    /**
     * @Route("/edit/carStart/{id}", methods="POST", name="auto_admin_device_edit_car_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateCarStartAction(Request $req, $id)
    {
        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarStartTbox')
            ->find($id);
        $form = $this->createForm(new CarStartTboxType(), $car, [
            'action' => $this->generateUrl('auto_admin_car_update', ['id' => $car->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($car);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rentalcar_list'));
        }
        return ['form'  => $form->createView()];
    }


}