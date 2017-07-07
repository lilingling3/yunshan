<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/7/20
 * Time: 下午2:54
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Car;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\MarketActivityType;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/marketActivity")
 */
class MarketActivityController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_marketActivity_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MarketActivity')
                ->createQueryBuilder('m')
        ;
        $adverts =
            new Paginator(
                $qb
                    ->select('m')
                    ->orderBy('m.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($adverts) / self::PER_PAGE);

        return ['adverts'=>$adverts,'page'=>$page,'total'=>$total];
    }

    /**
     * @Route("/new", methods="GET", name="auto_admin_marketActivity_new")
     * @Template()
     */
    public function newAction()
    {
        $advert = new \Auto\Bundle\ManagerBundle\Entity\MarketActivity();
        $form = $this->createFormBuilder()
            ->add('MarketActivity', new MarketActivityType(),['data'=>$advert])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_marketActivity_create'))
            ->setMethod('POST')
            ->getForm();


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_marketActivity_create")
     * @Template("AutoAdminBundle:MarketActivity:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $advert = new \Auto\Bundle\ManagerBundle\Entity\MarketActivity();
        $form = $this->createFormBuilder()
            ->add('MarketActivity', new MarketActivityType(),['data'=>$advert])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => ''])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => ''])
            ->setAction($this->generateUrl('auto_admin_marketActivity_create'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $advert = $data['MarketActivity'];
            $advert->setStartTime(new \DateTime($data['startTime']));
            $advert->setEndTime(new \DateTime($data['endTime']));
            $advert->setCreatetime(new \DateTime());
            $man = $this->getDoctrine()->getManager();
            $man->persist($advert);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_marketActivity_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_marketActivity_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $advert = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MarketActivity')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('MarketActivity', new MarketActivityType(),['data'=>$advert])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $advert->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $advert->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_marketActivity_update', ['id' => $advert->getId()]))
            ->setMethod('POST')
            ->getForm();

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit/{id}", methods="POST", name="auto_admin_marketActivity_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:MarketActivity:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $advert = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:MarketActivity')
            ->find($id);

        $form = $this->createFormBuilder()
            ->add('MarketActivity', new MarketActivityType(),['data'=>$advert])
            ->add('startTime', 'text', ['label'  => '开始时间','data' => $advert->getStartTime()->format('Y-m-d H:i:s')])
            ->add('endTime', 'text', ['label'  => '结束时间','data' => $advert->getEndTime()->format('Y-m-d H:i:s')])
            ->setAction($this->generateUrl('auto_admin_marketActivity_update', ['id' => $advert->getId()]))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($req);
        if ($form->isValid()) {
            $data = $form->getData();
            $advert = $data['MarketActivity'];
            $advert->setStartTime(new \DateTime($data['startTime']));
            $advert->setEndTime(new \DateTime($data['endTime']));
            $man = $this->getDoctrine()->getManager();
            $man->persist($advert);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_marketActivity_list'));
        }
        return ['form'  => $form->createView()];
    }
}