<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: ÏÂÎç2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Color;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\ColorType;

/**
 * @Route("/color")
 */
class ColorController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_color_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Color')
                ->createQueryBuilder('c')
        ;
        $colors =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($colors) / self::PER_PAGE);

        return ['colors'=>$colors,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_color_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new ColorType(), null, [
            'action' => $this->generateUrl('auto_admin_color_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_color_create")
     * @Template("AutoAdminBundle:Color:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $color = new \Auto\Bundle\ManagerBundle\Entity\Color();

        $form = $this->createForm(new ColorType(), $color, [
            'action' => $this->generateUrl('auto_admin_color_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($color);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_color_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_color_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $color = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Color')
            ->find($id);

        $form = $this->createForm(new ColorType(), $color, [
            'action' => $this->generateUrl('auto_admin_color_update', ['id' =>  $color->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_color_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Color:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $color = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Color')
            ->find($id);
        $form = $this->createForm(new ColorType(), $color, [
            'action' => $this->generateUrl('auto_admin_color_update', ['id' => $color->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($color);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_color_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_color_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $color = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Color')
            ->find($id);
        $man->remove($color);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_color_list'));
    }
}