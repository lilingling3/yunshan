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
use Auto\Bundle\ManagerBundle\Entity\CarLevel;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\CarLevelType;

/**
 * @Route("/carLevel")
 */
class CarLevelController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_car_level_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:CarLevel')
                ->createQueryBuilder('c')
        ;
        $levels =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($levels) / self::PER_PAGE);

        return ['levels'=>$levels,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_car_level_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new CarLevelType(), null, [
            'action' => $this->generateUrl('auto_admin_car_level_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_car_level_create")
     * @Template("AutoAdminBundle:CarLevel:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $level = new \Auto\Bundle\ManagerBundle\Entity\CarLevel();

        $form = $this->createForm(new  CarLevelType(), $level, [
            'action' => $this->generateUrl('auto_admin_car_level_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($level);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_level_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_car_level_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $level = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarLevel')
            ->find($id);

        $form = $this->createForm(new CarLevelType(), $level, [
            'action' => $this->generateUrl('auto_admin_car_level_update', ['id' =>  $level->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_car_level_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:CarLevel:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $level = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarLevel')
            ->find($id);
        $form = $this->createForm(new CarLevelType(), $level, [
            'action' => $this->generateUrl('auto_admin_car_level_update', ['id' => $level->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($level);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_level_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_car_level_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $level = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:CarLevel')
            ->find($id);
        $man->remove($level);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_car_level_list'));
    }
}