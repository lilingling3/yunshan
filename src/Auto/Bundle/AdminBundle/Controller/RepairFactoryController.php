<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/5/20
 * Time: 18:00
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Company;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\CompanyType;

/**
 * @Route("/repairFactory")
 */
class RepairFactoryController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_repair_factory_list",
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
                    ->setParameter('kind', 5)
                    ->orderBy('c.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($companys) / self::PER_PAGE);

        return ['companys'=>$companys,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_repair_factory_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new CompanyType(), null, [
            'action' => $this->generateUrl('auto_admin_repair_factory_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_repair_factory_create")
     * @Template("AutoAdminBundle:Company:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $company = new \Auto\Bundle\ManagerBundle\Entity\Company();

        $form = $this->createForm(new CompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_repair_factory_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $company->setKind(5);
            $man = $this->getDoctrine()->getManager();
            $man->persist($company);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_repair_factory_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_repair_factory_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);

        $form = $this->createForm(new CompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_repair_factory_update', ['id' =>  $company->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_repair_factory_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Company:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);
        $form = $this->createForm(new CompanyType(), $company, [
            'action' => $this->generateUrl('auto_admin_repair_factory_update', ['id' => $company->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $company->setKind(5);
            $man = $this->getDoctrine()->getManager();
            $man->persist($company);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_repair_factory_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_repair_factory_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $company = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Company')
            ->find($id);
        $man->remove($company);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_repair_factory_list'));
    }
}