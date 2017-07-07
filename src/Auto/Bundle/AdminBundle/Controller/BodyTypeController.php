<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: ����2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\BodyType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\BodyTypeType;

/**
 * @Route("/bodyType")
 */
class BodyTypeController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_body_type_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BodyType')
                ->createQueryBuilder('b')
        ;
        $types =
            new Paginator(
                $qb
                    ->select('b')
                    ->orderBy('b.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($types) / self::PER_PAGE);

        return ['types'=>$types,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_body_type_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new BodyTypeType(), null, [
            'action' => $this->generateUrl('auto_admin_body_type_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_body_type_create")
     * @Template("AutoAdminBundle:BodyType:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $type = new \Auto\Bundle\ManagerBundle\Entity\BodyType();

        $form = $this->createForm(new  BodyTypeType(), $type, [
            'action' => $this->generateUrl('auto_admin_body_type_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($type);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_body_type_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_body_type_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $type = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BodyType')
            ->find($id);

        $form = $this->createForm(new BodyTypeType(), $type, [
            'action' => $this->generateUrl('auto_admin_body_type_update', ['id' =>  $type->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_body_type_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:BodyType:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $type = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BodyType')
            ->find($id);
        $form = $this->createForm(new BodyTypeType(), $type, [
            'action' => $this->generateUrl('auto_admin_body_type_update', ['id' => $type->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($type);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_body_type_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_body_type_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $type = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BodyType')
            ->find($id);
        $man->remove($type);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_body_type_list'));
    }
}