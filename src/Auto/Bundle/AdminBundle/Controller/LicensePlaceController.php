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
use Auto\Bundle\ManagerBundle\Entity\LicensePlace;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\LicensePlaceType;

/**
 * @Route("/licensePlace")
 */
class LicensePlaceController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_license_place_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:LicensePlace')
                ->createQueryBuilder('l')
        ;
        $places =
            new Paginator(
                $qb
                    ->select('l')
                    ->orderBy('l.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($places) / self::PER_PAGE);

        return ['places'=>$places,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_license_place_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new LicensePlaceType(), null, [
            'action' => $this->generateUrl('auto_admin_license_place_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_license_place_create")
     * @Template("AutoAdminBundle:LicensePlace:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $place = new \Auto\Bundle\ManagerBundle\Entity\LicensePlace();

        $form = $this->createForm(new LicensePlaceType(), $place, [
            'action' => $this->generateUrl('auto_admin_license_place_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($place);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_license_place_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_license_place_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $place = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->find($id);

        $form = $this->createForm(new LicensePlaceType(), $place, [
            'action' => $this->generateUrl('auto_admin_license_place_update', ['id' =>  $place->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_license_place_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:LicensePlace:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $place = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:LicensePlace')
            ->find($id);
        $form = $this->createForm(new LicensePlaceType(), $place, [
            'action' => $this->generateUrl('auto_admin_license_place_update', ['id' => $place->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($place);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_license_place_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_license_place_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $place = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:LicensePlace')
                ->find($id);
        $man->remove($place);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_license_place_list'));
    }
}