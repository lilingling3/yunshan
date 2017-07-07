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
use Auto\Bundle\ManagerBundle\Form\CarType;

/**
 * @Route("/car")
 */
class CarController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_car_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $car = $req->query->get('car');
        if($car){
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Car')
                    ->createQueryBuilder('c')
            ;
            $cars =
                new Paginator(
                    $qb
                        ->select('c')
                        ->where($qb->expr()->eq('c.name', ':car'))
                        ->setParameter('car', $car)
                        ->orderBy('c.id', 'DESC')
                        ->setMaxResults(self::PER_PAGE)
                        ->setFirstResult(self::PER_PAGE * ($page - 1))
                );
        }else{
            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:Car')
                    ->createQueryBuilder('c')
            ;
            $cars =
                new Paginator(
                    $qb
                        ->select('c')
                        ->orderBy('c.id', 'DESC')
                        ->setMaxResults(self::PER_PAGE)
                        ->setFirstResult(self::PER_PAGE * ($page - 1))
                );
        }

        $total = ceil(count($cars) / self::PER_PAGE);

        return ['cars'=>$cars,'page'=>$page,'total'=>$total];
    }
    /**
     * @Route("/car/search", methods="POST", name="auto_admin_car_search")
     * @Template()
     */
    public function carSearchAction(Request $req){
        return $this->redirect(
            $this->generateUrl(
                'auto_admin_car_list',
                [
                    'car' => $req->request->get('car'),
                ]
            )
        );

    }
    /**
     * @Route("/new", methods="GET", name="auto_admin_car_new")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new CarType(), null, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);


        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_car_create")
     * @Template("AutoAdminBundle:Car:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $car = new \Auto\Bundle\ManagerBundle\Entity\Car();

        $form = $this->createForm(new CarType(), $car, [
            'action' => $this->generateUrl('auto_admin_car_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($car);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_list'));
        }
            return ['form'  => $form->createView()];
    }


    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_car_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);

        $form = $this->createForm(new CarType(), $car, [
            'action' => $this->generateUrl('auto_admin_car_update', ['id' => $car->getId()]),
            'method' => 'POST'
        ]);

        return [
            'form'  => $form->createView()
        ];
    }


    /**
     * @Route("/edit{id}", methods="POST", name="auto_admin_car_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Car:edit.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);
        $form = $this->createForm(new CarType(), $car, [
            'action' => $this->generateUrl('auto_admin_car_update', ['id' => $car->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($car);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_car_list'));
        }
        return ['form'  => $form->createView()];
    }


    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_car_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);
        $man->remove($car);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_car_list'));
    }
}