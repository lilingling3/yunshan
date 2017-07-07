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
use Auto\Bundle\ManagerBundle\Entity\DispatchRentalCar;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/dispatch")
 */
class DispatchController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_dispatch_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1,Request $req)
    {
        $rental_car= $req->query->get('rentalCar');

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:DispatchRentalCar')
                ->createQueryBuilder('d')
        ;
        $dispatchs =
            new Paginator(
                $qb
                    ->select('d')
                    ->orderBy('d.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );

        $total = ceil(count($dispatchs) / self::PER_PAGE);

        return ['dispatchs'=>$dispatchs,'page'=>$page,'total'=>$total];
    }

}