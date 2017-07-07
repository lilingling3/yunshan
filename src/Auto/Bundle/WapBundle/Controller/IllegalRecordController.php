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
 * @Route("/illegalRecord")
 */
class IllegalRecordController extends Controller{
    const PER_PAGE = 6;

    /**
     * @Route("/{page}", methods="GET", name="auto_wap_illegalRecord",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page){
        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:IllegalRecord')
                ->createQueryBuilder('u');
        $illegal= new Paginator(
            $qb->select('u')
                ->join('u.order','o')
                ->orderBy('u.handleTime', 'DESC')
                ->addorderBy('u.createTime', 'DESC')
                ->where($qb->expr()->eq('o.member', ':member'))
                ->setParameter('member', $this->getUser())
                ->setMaxResults(self::PER_PAGE)
                ->setFirstResult(self::PER_PAGE * ($page - 1))
        );

        return [
            "page"=>$page,
            'illegals' => array_map($this->get('auto_manager.illegalRecord_helper')->get_illegal_record_normalizer(),
                $illegal->getIterator()->getArrayCopy())
        ];
    }
    /**
     * @Route("/list/{id}", methods="GET", name="auto_wap_illegalRecord_show",
     * requirements={"id"="\d+"})
     * @Template()
     */
    public function showAction($id){
        $illegal = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:IllegalRecord')
            ->find($id);
        return ['illegal' => call_user_func($this->get('auto_manager.illegalRecord_helper')->get_illegal_record_normalizer(),
            $illegal)];
    }
}