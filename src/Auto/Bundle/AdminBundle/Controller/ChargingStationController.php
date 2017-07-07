<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/5
 * Time: 上午11:19
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\ChargingStation;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\ChargingStationType;

/**
 * @Route("/chargingstation")
 */

class ChargingStationController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_chargingstation_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:ChargingStation')
                ->createQueryBuilder('s')
        ;
        $chargingstations =
            new Paginator(
                $qb
                    ->select('s')
                    ->orderBy('s.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );
        $total = ceil(count($chargingstations) / self::PER_PAGE);
        return ['chargingstations'=>$chargingstations,'page'=>$page,'total'=>$total];
    }

}