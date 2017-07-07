<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\MobileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/car")
 */
class CarController extends Controller{

    /**
     * @Route("/list", methods="GET", name="auto_mobile_car_list")
     * @Template()
     */
    public function listAction(){
        $cars = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->findAll();

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Car')
                ->createQueryBuilder('c');

        $cars = $qb
            ->select('c')
            ->where($qb->expr()->in('c.id',[2,4,6,8]))
            ->getQuery()
            ->getResult();
        ;

        return ['cars'=>array_map($this->get('auto_manager.car_helper')->get_car_introduction_normalizer(),$cars)];
    }


    /**
     * @Route("/show/{id}", methods="GET", name="auto_mobile_car_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id){
       

        return ["id"=>$id];
    }


}