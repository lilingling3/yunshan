<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/10
 * Time: 下午2:28
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\ChargingPile;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\ChargingPileType;

/**
 * @Route("/chargingpile")
 */

class ChargingPileController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_charging_pile_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:ChargingPile')
                ->createQueryBuilder('p')
        ;
        $piles =
            new Paginator(
                $qb
                    ->select('p')
                    ->orderBy('p.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($piles) / self::PER_PAGE);
        return ['piles'=>$piles,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_charging_pile_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new ChargingPileType(), null, [
            'action' => $this->generateUrl('auto_admin_charging_pile_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_charging_pile_create")
     * @Template("AutoAdminBundle:ChargingPile:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $pile = new \Auto\Bundle\ManagerBundle\Entity\ChargingPile();

        $form = $this->createForm(new ChargingPileType(), $pile, [
            'action' => $this->generateUrl('auto_admin_charging_pile_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($pile);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_charging_pile_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_charging_pile_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $pile = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:ChargingPile')
            ->find($id);
        $form = $this->createForm(new ChargingPileType(), $pile, [
            'action' => $this->generateUrl('auto_admin_charging_pile_update', ['id' => $pile->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_charging_pile_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:ChargingPile:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $pile = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:ChargingPile')
            ->find($id);
        $form = $this->createForm(new ChargingPileType(), $pile, [
            'action' => $this->generateUrl('auto_admin_charging_pile_update', ['id' => $pile->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($pile);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_charging_pile_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_charging_pile_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $pile = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:ChargingPile')
            ->find($id);
        $man->remove($pile);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_charging_pile_list'));
    }



}