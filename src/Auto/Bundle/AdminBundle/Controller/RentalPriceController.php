<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/21
 * Time: 上午11:45
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\RentalPrice;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\RentalPriceType;

/**
 * @Route("/rentalPrice")
 */
class RentalPriceController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rental_price_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $car=$req->query->get('car');
        $province=$req->query->get('province');
        $city=$req->query->get('city');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalPrice')
                ->createQueryBuilder('c')
        ;
        if($car){
            $qb
                ->where($qb->expr()->eq('c.car',':car'))
                ->setParameter('car', $car);
        }

        if($province){
            $qb
                 ->join('c.area','r')
                ->where($qb->expr()->eq('r.parent',':area'))
                ->setParameter('area', $province);
        }
        if($city){
            $qb
                ->where($qb->expr()->eq('c.area',':area'))
                ->setParameter('area', $city);
        }
        $rentalprices =
            new Paginator(
                $qb
                    ->select('c')
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $cars= $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:car')
            ->findAll();
        $provinces=$this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:area')
            ->findBy(['parent'=>null]);

        $total = ceil(count($rentalprices) / self::PER_PAGE);

        return ['rentalprices'=>$rentalprices,'cars'=>$cars,'provinces'=>$provinces,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_rental_price_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new RentalPriceType(), null, [
            'action' => $this->generateUrl('auto_admin_rental_price_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/new", methods="POST", name="auto_admin_rental_price_create")
     * @Template("AutoAdminBundle:RentalPrice:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $rentalprice = new \Auto\Bundle\ManagerBundle\Entity\RentalPrice();

        $form = $this->createForm(new RentalPriceType(), $rentalprice, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalprice);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_price_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rental_price_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $rentalprice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalPrice')
            ->find($id);
        $form = $this->createForm(new RentalPriceType(), $rentalprice, [
            'action' => $this->generateUrl('auto_admin_rental_price_update', ['id' => $rentalprice->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'      => $form->createView(),
            'rentalprice' => $rentalprice
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_rental_price_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RentalPrice:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $rentalprice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalPrice')
            ->find($id);

        $form = $this->createForm(new RentalPriceType(), $rentalprice, [
            'action' => $this->generateUrl('auto_admin_rental_price_update', ['id' => $rentalprice->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($rentalprice);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_price_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_rental_price_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $rentalprice = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:RentalPrice')
            ->find($id);
        $man->remove($rentalprice);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rental_price_list'));
    }
}