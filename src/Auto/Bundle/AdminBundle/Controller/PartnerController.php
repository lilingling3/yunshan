<?php

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Auto\Bundle\ManagerBundle\Entity\Partner;
use Auto\Bundle\ManagerBundle\Form\PartnerType;

/**
 * Partner controller.
 *
 * @Route("/partner")
 */
class PartnerController extends Controller
{

    /**
     * Lists all Partner entities.
     *
     * @Route("/list", name="auto_admin_partner_index")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $req)
    {
        $status = $req->query->get('status');
        $id = $req->query->get('id');
        $pqb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Partner')
                ->createQueryBuilder('p')
                ->select('p');
        if ($status == '0' || $status == '1') {
            $pqb
                ->andWhere($pqb->expr()->eq('p.status', ':status'))
                ->setParameter('status', $status);
        }
        if($id){
            $pqb
                ->andWhere($pqb->expr()->eq('p.id', ':id'))
                ->setParameter('id', $id);
        }
        $entities = $pqb->select('p')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();;
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Partner')
                ->createQueryBuilder('p');
        $name = $qb
            ->select('p.name,p.id')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return array(
            "names"=>$name,
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Partner entity.
     *
     * @Route("/", name="auto_admin_partner_create")
     * @Method("POST")
     * @Template("AutoManagerBundle:Partner:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Partner();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $code = $this->random(6);

            $em = $this->getDoctrine()->getManager();

            $member = new \Auto\Bundle\ManagerBundle\Entity\Member();
            $member->setMobile($code)->setRoles(['PARTNER'])->setName($entity->getName());
            $em->persist($member);
            $em->flush();

            $entity->setCode($code)->setMember($member);
            $em->persist($entity);
            $em->flush();


            return $this->redirect($this->generateUrl('auto_admin_partner_index'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Partner entity.
     *
     * @param Partner $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Partner $entity)
    {
        $form = $this->createForm(new PartnerType(), $entity, array(
            'action' => $this->generateUrl('auto_admin_partner_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Partner entity.
     *
     * @Route("/new", name="auto_admin_partner_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        // $entity = new Partner();
        // $form   = $this->createCreateForm($entity);
        $form = $this->createForm(new PartnerType(), null, [
            'action' => $this->generateUrl('auto_admin_partner_create'),
            'method' => 'POST'
        ]);
        return array(
            'car' => null,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Partner entity.
     *
     * @Route("/{id}", name="auto_admin_partner_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AutoManagerBundle:Partner')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Partner entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Partner entity.
     *
     * @Route("/edit/{id}", name="auto_admin_partner_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AutoManagerBundle:Partner')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Partner entity.');
        }

        $form = $this->createForm(new PartnerType(), $entity, [
            'action' => $this->generateUrl('auto_admin_partner_update', ['id' => $entity->getId()]),
            'method' => 'POST'
        ]);
        $form = $form->createView();

        $ids = $this->get('auto_manager.cache_helper')->getCarPlatesByIDs(explode(',', $entity->getVisibleCars()));


        $carIdPlates = [];
        foreach ($ids as $k => $v) {
            if(empty($k) || empty($v)){
                continue;
            }
            $carIdPlates[] = ['id' => $k, 'plate' => $v];
        }

        return array(
            'car' => $carIdPlates,
            'form' => $form
        );
    }

    /**
     * Creates a form to edit a Partner entity.
     *
     * @param Partner $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Partner $entity)
    {
        $form = $this->createForm(new PartnerType(), $entity, array(
            'action' => $this->generateUrl('auto_admin_partner_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Partner entity.
     *
     * @Route("/{id}", name="auto_admin_partner_update")
     * @Method("POST")
     * @Template("AutoManagerBundle:Partner:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AutoManagerBundle:Partner')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Partner entity.');
        }
        $form = $this->createForm(new PartnerType(), $entity, [
            'action' => $this->generateUrl('auto_admin_rentalcar_update', ['id' => $entity->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            /*  $deleteForm = $this->createDeleteForm($id);
              $editForm = $this->createEditForm($entity);
              $editForm->handleRequest($request);*/

            if ($form->isValid()) {
                $em->persist($entity);
            }
            $em->flush();

            return $this->redirect($this->generateUrl('auto_admin_partner_index'));
        }

        return array(
            'entity'      => $entity
        );
    }
    /**
     * Deletes a Partner entity.
     *
     * @Route("/{id}", name="auto_admin_partner_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AutoManagerBundle:Partner')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Partner entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('partner'));
    }

    /**
     * Creates a form to delete a Partner entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('auto_admin_partner_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
            ;
    }

    private function random($w)
    {
        $n = current(unpack('L', openssl_random_pseudo_bytes(4)));

        return sprintf("%0{$w}.0f", ($n & 0x7fffffff) / 0x7fffffff * (pow(10, $w) - 1));
    }
}
