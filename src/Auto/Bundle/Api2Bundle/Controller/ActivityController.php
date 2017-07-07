<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/9/7
 * Time: 下午5:18
 */

namespace Auto\Bundle\Api2Bundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Auto\Bundle\ManagerBundle\Entity\Message;

/**
 * @Route("/activity")
 */
class ActivityController extends BaseController {

    /**
     * @Route("/list", methods="POST")
     */

    public function listAction(Request $req){

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:MarketActivity')
                ->createQueryBuilder('a');

        $activities = $qb
            ->select('a')
            ->where($qb->expr()->gt('a.endTime',':time'))
            ->andWhere($qb->expr()->lte('a.startTime',':time'))
            ->setParameter('time',new \DateTime())
            ->orderBy('a.startTime','DESC')
            ->getQuery()
            ->getResult();

        if(empty($activities)){

            return new JsonResponse([
                'errorCode' =>  self::E_NO_AD,
                'errorMessage' =>  self::M_NO_AD,

            ]);
        }

        return new JsonResponse([
            'errorCode' =>  self::E_OK,
            'activities' => array_map($this->get('auto_manager.market_activity_helper')
                    ->get_market_activity_normalizer()
                , $activities),
        ]);

    }

}