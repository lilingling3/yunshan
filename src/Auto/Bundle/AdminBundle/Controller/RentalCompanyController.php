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
use Auto\Bundle\ManagerBundle\Entity\Company;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\RentalCompanyType;

/**
 * @Route("/rentalCompany")
 */
class RentalCompanyController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_rental_company_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $name=$req->query->get('name');
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Company')
                ->createQueryBuilder('c')
        ;
        if($name){
            $qb
                ->where( $qb->expr()->eq('c.name',':name') )
                ->setParameter('name', $name);
        }
        $companys =
            new Paginator(
                $qb
                    ->select('c')
                    ->andWhere($qb->expr()->eq('c.kind', ':kind'))
                    ->setParameter('kind', \Auto\Bundle\ManagerBundle\Entity\Company::COMPANY_KIND_RENTAL)
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($companys) / self::PER_PAGE);

        return ['companys'=>$companys,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_rental_company_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new RentalCompanyType(), null, [
            'action' => $this->generateUrl('auto_admin_rental_company_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_rental_company_create")
     * @Template("AutoAdminBundle:RentalCompany:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $company = new \Auto\Bundle\ManagerBundle\Entity\Company();

        $form = $this->createForm(new RentalCompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_rental_company_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $company->setKind(\Auto\Bundle\ManagerBundle\Entity\Company::COMPANY_KIND_RENTAL);
            $man = $this->getDoctrine()->getManager();
            $man->persist($company);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_company_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_rental_company_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);

        $form = $this->createForm(new RentalCompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_rental_company_update', ['id' =>  $company->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_rental_company_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:RentalCompany:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);
        $form = $this->createForm(new RentalCompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_rental_company_update', ['id' => $company->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $man = $this->getDoctrine()->getManager();
            $man->persist($company);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_rental_company_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_rental_company_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);
        $man->remove($company);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_rental_company_list'));
    }
}