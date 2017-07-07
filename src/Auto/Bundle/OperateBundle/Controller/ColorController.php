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
use Auto\Bundle\ManagerBundle\Form\ColorType;
/**
 * @Route("/color")
 */

class ColorController extends Controller{
    /**
     * @Route("/list", methods="GET", name="auto_operate_color_list")
     * @Template()
     */
    public function listAction(){
        $colors =  	$this->getDoctrine()
            ->getRepository('AutoManagerBundle:Color')
            ->findAll();
        $form = $this->createForm(new ColorType(), null, [
            'action' => $this->generateUrl('auto_operate_color_add'),
            'method' => 'POST'
        ]);
    return ['colors'=>$colors,'form'  => $form->createView()];
    }
    /**
     * @Route("/add", methods="POST", name="auto_operate_color_add")
     * @Template("AutoOperateBundle:Color:list.html.twig")
     */
    public function addAction(Request $req){
        $color = new \Auto\Bundle\ManagerBundle\Entity\Color();

        $form = $this->createForm(new ColorType(), $color, [
            'action' => $this->generateUrl('auto_operate_color_add'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($color);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_operate_color_list'));
        }
        return ['form'  => $form->createView()];
    }
}