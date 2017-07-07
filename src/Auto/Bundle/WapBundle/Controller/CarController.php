<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: ����5:09
 */

namespace Auto\Bundle\WapBundle\Controller;

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
     * @Route("/list", methods="GET", name="auto_wap_car_list")
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
            ->where($qb->expr()->in('c.id',[2,4]))
            ->getQuery()
            ->getResult();
        ;

        return ['cars'=>array_map($this->get('auto_manager.car_helper')->get_car_introduction_normalizer(),$cars)];
    }
    /**
     * @Route("/introduce/{id}", methods="GET", name="auto_wap_car_introduce",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function introduceAction($id){
        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);
        return ['car'=>call_user_func($this->get('auto_manager.car_helper')->get_car_introduction_normalizer(),$car)];
    }

    /**
     * @Route("/introduction/show/{id}", methods="GET", name="auto_wap_car_introduce_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function introduceShowAction($id){

        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);

        return ['car'=>call_user_func($this->get('auto_manager.car_helper')->get_car_introduction_normalizer(),$car)];
    }

    /**
     * @Route("/introduction/show2/{id}", methods="GET", name="auto_wap_car_introduce_show2",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function introduceShow2Action($id){

        $car = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Car')
            ->find($id);

        return ['car'=>call_user_func($this->get('auto_manager.car_helper')->get_car_introduction_data_normalizer(),$car)];
    }


}