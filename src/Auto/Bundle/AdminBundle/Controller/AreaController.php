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
use Auto\Bundle\ManagerBundle\Entity\Area;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Auto\Bundle\ManagerBundle\Form\AreaType;
use Auto\Bundle\ManagerBundle\Form\BusinessDistrictType;
/**
 * @Route("/area")
 */
class AreaController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_area_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction($page = 1)
    {

        $repos = $this->getDoctrine()->getRepository('AutoManagerBundle:Area');
        $qb = $repos->createQueryBuilder('a')
            ->select('a');

        $qb->where($qb->expr()->isNull('a.parent'));
        $areas = $qb->getQuery()->getResult();

        return ['areas'=>$areas];
    }

    /**
     * @Route("/new/province", methods="GET", name="auto_admin_area_new_province")
     * @Template()
     */
    public function newProvinceAction()
    {
        $form = $this->createForm(new AreaType(), null, [
            'action' => $this->generateUrl('auto_admin_area_create_province'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new/province", methods="POST", name="auto_admin_area_create_province")
     * @Template("AutoAdminBundle:Area:newProvince.html.twig")
     */
    public function createProvinceAction(Request $req)
    {
        $area = new \Auto\Bundle\ManagerBundle\Entity\Area;
        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            /*            $data = $form->getData();
                        dump($data);exit;*/
            $man = $this->getDoctrine()->getManager();
            /*
                        $area->setName($data['name']);
                        $area->setLatitude($data['latitude']);
                        $area->setLongitude($data['longitude']);*/
            $man->persist($area);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_area_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new/{parent_id}", methods="GET", name="auto_admin_area_new",
     * requirements={"page"="\d+"},
     * defaults={"page"=1}
     * )
     * @Template()
     */
    public function newAction($parent_id)
    {
        $parent = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($parent_id);
        $area = new Area();
        $area->setParent($parent);

        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_create'),
            'method' => 'POST'
        ]);

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/new", methods="POST", name="auto_admin_area_create")
     * @Template("AutoAdminBundle:Area:new.html.twig")
     */
    public function createAction(Request $req)
    {
        $area = new Area();

        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {
            $data = $form->getData();
            $man = $this->getDoctrine()->getManager();
            $man->persist($area);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_area_list'));
        }

        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/del/{id}", methods="GET", name="auto_admin_area_delete",requirements={"id"="\d+"})
     */
    public function deleteAction($id)
    {
        $man = $this->getDoctrine()->getManager();
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($id);
        $man->remove($area);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_area_list'));
    }

    /**
     * @Route("/province/edit/{id}", methods="GET", name="auto_admin_area_province_edit",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Area:newProvince.html.twig")
     */
    public function provinceEditAction($id)
    {
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($id);

        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_province_update', ['id' =>  $area->getId()]),
            'method' => 'POST'
        ]);
        return [
            'form'  => $form->createView()
        ];
    }
    /**
     * @Route("/edit/{id}", methods="GET", name="auto_admin_area_edit",requirements={"id"="\d+"})
     * @Template()
     */
    public function editAction($id)
    {
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($id);

        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_update', ['id' =>  $area->getId()]),
            'method' => 'POST'
        ]);
        return [
            'form'  => $form->createView()
        ];
    }

    /**
     * @Route("/edit/updat/{id}", methods="POST", name="auto_admin_area_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Area:edit.html.twig")
     */
    public function editUpdateAction(Request $req, $id)
    {
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($id);
        $parent=$area->getParent();
        $form = $this->createForm(new AreaType(), null, [
            'action' => $this->generateUrl('auto_admin_area_update', ['id' => $area->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {
            $data = $form->getData();

            $area->setName($data->getName());
            $area->setLatitude($data->getLatitude());
            $area->setLongitude($data->getLongitude());
            $man = $this->getDoctrine()->getManager();
            $man->persist($area);
            $man->flush();
            $area->setParent($parent);
            $man->persist($area);
            $man->flush();
            return $this->redirect($this->generateUrl('auto_admin_area_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/province/update/{id}", methods="POST", name="auto_admin_area_province_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:Area:newProvince.html.twig")
     */
    public function areaUpdateAction(Request $req, $id)
    {
        $area = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:Area')
            ->find($id);
        $form = $this->createForm(new AreaType(), $area, [
            'action' => $this->generateUrl('auto_admin_area_province_update', ['id' => $area->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($area);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_area_list'));
        }
        return ['form'  => $form->createView()];
    }

    /**
     * @Route("/all", methods="GET", name="auto_admin_area_all")
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
     * @Route("/twoCascading", methods="GET", name="auto_admin_area_two_cascading")
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



    /**
     * @Route("/threeCascading", methods="get", name="auto_admin_area_three_cascading")
     */

    public function threeCascadingAction(Request $req )
    {
        $id =  $req->query->get('id');

        $allBusinessDistrictArr =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BusinessDistrict')
                ->findBy(['area' => $id], ['id' => 'ASC']);
        $arr=[];

        foreach($allBusinessDistrictArr as $value){
            $arr[]=[
                "name"=>$value->getName(),
                "id"=>$value->getId()
            ];
        }

        return new JsonResponse(["data"=>$arr]);
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
     * @Route("/allTwo", methods="GET", name="auto_admin_area_all_two")
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
     * @Route("/allbyregion", methods="GET", name="auto_admin_area_all_by_region")
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


    /**
     * @Route("/businessDistrict/list/{page}", methods="GET", name="auto_admin_area_businessDistrict_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1}))
     * @Template()
     */
    public function businessDistrictAction($page)
    {

        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:BusinessDistrict')
                ->createQueryBuilder('b')
        ;

        $businessDistricts =
            new Paginator(
                $qb
                    ->select('b')
                    ->orderBy('b.id', 'DESC')
                    ->setMaxResults(self::PER_PAGE)
                    ->setFirstResult(self::PER_PAGE * ($page - 1))
            );


        $total = ceil(count($businessDistricts) / self::PER_PAGE);
        return ["businessDistricts"=>$businessDistricts,"total"=>$total,"page"=>$page];
    }

    /**
     * @Route("/businessDistrict/edit/{id}", methods="GET", name="auto_admin_area_businessDistrict_edit")
     * @Template()
     */
    public function editBusinessDistrictAction($id)
    {
        $businessDistrict = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BusinessDistrict')
            ->find($id);

        $form = $this->createForm(new BusinessDistrictType(), $businessDistrict, [
            'action' => $this->generateUrl('auto_admin_area_businessDistrict_update', ['id' =>  $businessDistrict->getId()]),
            'method' => 'POST'
        ]);


        return [
            'form'  => $form->createView()
        ];
    }
    /**
     * @Route("/businessDistrict/update/{id}", methods="POST", name="auto_admin_area_businessDistrict_update",requirements={"id"="\d+"})
     * @Template("AutoAdminBundle:editBusinessDistrict.html.twig")
     */
    public function updateAction(Request $req, $id)
    {
        $businessDistrict = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BusinessDistrict')
            ->find($id);
        $form = $this->createForm(new BusinessDistrictType(), $businessDistrict, [
            'action' => $this->generateUrl('auto_admin_area_businessDistrict_update', ['id' => $businessDistrict->getId()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);
        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($businessDistrict);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_area_businessDistrict_list'));
        }
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("/businessDistrict/del/{id}", methods="GET", name="auto_admin_area_businessDistrict_del",requirements={"id"="\d+"})
     */
    public function businessDistrictDeleteAction($id){

        $man = $this->getDoctrine()->getManager();
        $businessDistrict = $this->getDoctrine()
            ->getRepository('AutoManagerBundle:BusinessDistrict')
            ->find($id);
        $rentalStations =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalStation')
                ->findBy(['businessDistrict' => $businessDistrict], ['id' => 'ASC']);
        foreach($rentalStations as $value){
            $value->setBusinessDistrict(null);
            $man->persist($value);
            $man->flush();
        }
        $man->remove($businessDistrict);
        $man->flush();

        return $this->redirect($this->generateUrl('auto_admin_area_businessDistrict_list'));
    }

    /**
     * @Route("/businessDistrict/new", methods="GET", name="auto_admin_area_businessDistrict_new")
     * @Template()
     */
    public function businessDistrictNewAction()
    {
        $form = $this->createForm(new BusinessDistrictType(), null, [
            'action' => $this->generateUrl('auto_admin_rental_area_businessDistrict_create'),
            'method' => 'POST'
        ]);
        return ['form'  => $form->createView()];
    }
    /**
     * @Route("businessDistrict/create", methods="POST", name="auto_admin_rental_area_businessDistrict_create")
     * @Template("AutoAdminBundle:Area:businessDistrictNew.html.twig")
     */
    public function businessDistrictCreateAction(Request $req)
    {
        $businessDistrict = new \Auto\Bundle\ManagerBundle\Entity\BusinessDistrict();

        $form = $this->createForm(new BusinessDistrictType(), $businessDistrict, [
            'action' => $this->generateUrl('auto_admin_rental_area_businessDistrict_create'),
            'method' => 'POST'
        ]);

        $form->handleRequest($req);

        if ($form->isValid()) {

            $man = $this->getDoctrine()->getManager();
            $man->persist($businessDistrict);
            $man->flush();

            return $this->redirect($this->generateUrl('auto_admin_area_businessDistrict_list'));
        }

        return ['form'  => $form->createView()];
    }
}