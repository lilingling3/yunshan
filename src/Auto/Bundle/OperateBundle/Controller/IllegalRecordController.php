<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/16
 * Time: 下午5:09
 */

namespace Auto\Bundle\OperateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Auto\Bundle\ManagerBundle\Form\RentalCarOperateType;

/**
 * @Route("/illegal")
 */
class IllegalRecordController extends Controller
{
    /**
     * @Route("/list", methods="GET", name="auto_operate_illegal_list",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function listAction(){

            $operate = $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Operator')
                ->findOneBy(['member' => $this->getUser()]);
            $stations = $operate->getStations();
            $stationsId = array();
            foreach ($stations as $value) {
                $stationsId[] = $value->getId();
            }
            //car

            $qb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:RentalCar')
                    ->createQueryBuilder('c');
            $cars =
                $qb
                    ->select('c')
                    ->where($qb->expr()->in('c.rentalStation', $stationsId))
                    ->getQuery()
                    ->getResult();
            $carIdArray = array();
            foreach ($cars as $value) {
                $carIdArray[] = $value->getId();
            }
            $illegalqb =
                $this
                    ->getDoctrine()
                    ->getRepository('AutoManagerBundle:IllegalRecord')
                    ->createQueryBuilder('i');
            $illegals =
                $illegalqb->select('i')
                    ->orderBy('i.illegalTime', 'DESC')
                    ->where($illegalqb->expr()->in('i.rentalCar', $carIdArray))
                    ->andWhere($illegalqb->expr()->isNull('i.handleTime'))
                    ->getQuery()
                    ->getResult();

        return [
            'illegals' => $illegals
        ];
    }
    /**
     * @Route("/show/{id}", methods="GET", name="auto_operate_illegal_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id){
        $illegal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->find($id);
        return ["illegal"=>$illegal];
    }
}