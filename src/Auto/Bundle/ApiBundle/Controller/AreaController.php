<?php

namespace Auto\Bundle\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @Route("/area")
 */
class AreaController extends BaseController
{
    const PER_PAGE = 20;

    /**
     * @Route("/all", methods="POST", name="auto_api_area_all")
     */

    public function allAction()
    {
        $allCityArr = $this->normalize(
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC'])
            ,
            2,
            3
        );

        return new JsonResponse($allCityArr);
    }

    /**
     * @Route("/twoCascading", methods="POST", name="auto_api_area_two_cascading")
     */

    public function twoCascadingAction()
    {
        $allCityArr = $this->normalize(
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->findBy(['parent' => null], ['id' => 'ASC'])
            ,
            1,
            3
        );

        return new JsonResponse($allCityArr);
    }

    private function normalize(Array $areas, $depth, $filter = 0)
    {
        function normalize(Area $area, $depth, $filter) {
            return [
                $area->getName(),
                $depth > 0
                    ? array_reduce(
                    $area->getChildren()->toArray(),
                    function ($siblings, Area $child) use ($depth, $filter) {
                        if($depth == 1) ksort($siblings);
                        list($name, $children) = normalize($child, $depth - 1, $filter);
                        if($depth == $filter){
                            $siblings += $children;
                        }else{
                            $siblings[$name] = $children;
                        }

                        return $siblings;
                    },
                    []
                )
                    : $area->getId()
            ];
        }

        return array_reduce(
            $areas,
            function ($carray, Area $area) use ($depth, $filter) {
                list($name, $children) = normalize($area, $depth, $filter);
                $carray[$name] = $children;

                return $carray;
            },
            []
        );
    }

    /**
     * @Route("/allTwo", methods="POST", name="auto_api_area_all_two")
     */
    public function allTwoAction(Request $req)
    {
        $flag = $req->query->get('flag');
        $allProvince = $this
            ->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->findBy(['parent' => null], ['id' => 'ASC']);
        $allCityArr = array();
        foreach ($allProvince as $province) {
            if($flag == 'all') {
                $cityArr = array(
                    '全部' => array(
                        '全部' => $province->getId()
                    )
                );
            }else{
                $cityArr = array(
                    '全部' => $province->getId()
                );
            }
            $citys = $province->getChildren()->toArray();
            if(!empty($citys)){
                foreach ($citys as $city) {
                    if($flag == 'all'){
                        $areaArr = array(
                            '全部'=>$city->getId(),
                        );
                        $areas = $city->getChildren()->toArray();
                        if(!empty($areas)) {
                            foreach ($areas as $area) {
                                $areaArr[$area->getName()] = $area->getId();
                            }
                        }
                        $cityArr[$city->getName()] = $areaArr;
                    }else{
                        $cityArr[$city->getName()] = $city->getId();
                    }

                }
            }
            $allCityArr[$province->getName()] = $cityArr;
        }
        return new JsonResponse($allCityArr);
    }

    /**
     * @Route("/allbyregion", methods="POST", name="auto_api_area_all_by_region")
     */
    public function allByRegionAction(Request $req)
    {
        $region = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Region')
            ->findOneBy(['member'=>$this->getUser()]);
        $area = array();
        if(!empty($region)){
            $oAreas = $region->getAreas();
            foreach ($oAreas as $oArea) {
                $child1 = $oArea->getChildren()->toArray();
                if(empty($child1)){
                    $area[$oArea->getParent()->getParent()->getName()][$oArea->getParent()->getName()][$oArea->getName()]=$oArea->getId();
                }else{
                    foreach ($child1 as $c1) {
                        $child2 = $c1->getChildren()->toArray();
                        if(empty($child2)){
                            $area[$oArea->getParent()->getName()][$oArea->getName()][$c1->getName()]=$c1->getId();
                        }else{
                            foreach ($child2 as $c2) {
                                $area[$oArea->getName()][$c1->getName()][$c2->getName()]=$c2->getId();
                            }
                        }
                    }
                }
            }
        }
        return new JsonResponse($area);
    }



}
