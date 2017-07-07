<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/18
 * Time: 下午5:58
 */

namespace Auto\Bundle\OperateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Auto\Bundle\ManagerBundle\Form\CarOperateType;
/**
 * @Route("/car")
 */

class CarController extends Controller{
    /**
     * @Route("/list", methods="GET", name="auto_operate_car_list")
     * @Template()
     */
    public function listAction(){
        $cars =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();
        return ['cars'=>$cars];
    }
    /**
     * @Route("/add", methods="GET", name="auto_operate_car_add")
     * @Template()
     */
    public function addAction(){
        $form = $this->createForm(new CarOperateType(), null, [
            'action' => $this->generateUrl('auto_operate_car_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/create", methods="POST", name="auto_operate_car_create")
     * @Template("AutoOperateBundle:Car:add.html.twig")
     */
    public function createAction (Request $req){
        $car = new \Auto\Bundle\ManagerBundle\Entity\Car();

        $form = $this->createForm(new CarOperateType(), $car, [
            'action' => $this->generateUrl('auto_operate_car_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($car);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_operate_car_list'));
        }
        return ['form'  => $form->createView()];

    }


}