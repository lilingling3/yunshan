<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/11
 * Time: 下午4:42
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class AreaHelper extends AbstractHelper
{

    public function get_area_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Area $a) {
            $parent = !empty($a->getParent())?['id'=>$a->getParent()->getId(),'name'=>$a->getParent()->getName()]:[];
            return [
                'name'=>$a->getName(),
                'parent'=>$parent,
            ];
        };
    }

    /**
     * 根据id获取区域信息
     **/

    public function getArea($area_name = '',$area_id = null)
    {
        $qb = $this->em->createQueryBuilder();

            $qb
                ->select('a')
                ->from('AutoManagerBundle:Area', 'a');
            if($area_name){
                $qb
                    ->where($qb->expr()->eq('a.name', ':name'))
                    ->setParameters([
                        'name' => $area_name,
                    ])
                ;
            }
        if($area_id){
            $qb
                ->where($qb->expr()->eq('a.id', ':aid'))
                ->setParameters([
                    'aid' => $area_id,
                ])
            ;
        }

        return $qb->getQuery()
                ->getOneOrNullResult()
            ;
    }



    /**
     * 取所有地级市
     */
    public function getCitylist($type=false)
    {
        $qb = $this->em->createQueryBuilder();

        $list = 
            $qb
                ->select('a')
                ->from('AutoManagerBundle:Area', 'a')
                ->where($qb->expr()->like('a.name',':area'))
                ->setParameter('area','%市')
                ->getQuery()
                ->getResult();


        $listArr = [];

        if ($type) {
            
            return $list;

        } else {

            if (!empty($list)) 
            {
                
                foreach ($list as $key => $value) 
                {
                    $listArr[$value->getId()] = $value->getName();
                }
            }

            return $listArr;
        }
    }

    /**
     * 取地级市下辖所有区
     */
    public function getDistinct($city_id)
    {
        $qb = $this->em->createQueryBuilder();

        $list = 
            $qb
                ->select('a')
                ->from('AutoManagerBundle:Area', 'a')
                ->where($qb->expr()->eq('a.parent',':area'))
                ->setParameter('area',$city_id)
                ->getQuery()
                ->getResult();
                
        $listArr = [];
        if (!empty($list)) 
        {
            
            foreach ($list as $key => $value) 
            {
                $listArr[] = $value->getId();
            }
        }

        return $listArr;
    }

    /**
     * 取所有区
     */
    public function getDistinctList($provinceName,$cityId=null)
    {
        $listArr = [];

        if (isset($cityId) && $cityId) {

            $listArr = self::getDistinct($cityId);
        } else if (is_string($provinceName) && $provinceName) {
            
            $listArr = self::getDistinctByProvinceName($provinceName);
        }

        return $listArr;
    }


    /**
     * 根据省名取得所有的区
     */
    public function getDistinctByProvinceName($province)
    {

        if (is_string($province) && $province) {

            // 获取省信息
            $provinceInfo = self::getArea($province);

            if (empty($provinceInfo)) {

                return false;
            }

            // 获取该省下城市列表
            $cityList = $provinceInfo->getChildren()->toArray();
            
            $distinctList = [];
            foreach ($cityList as $city) {

                // 获取区列表
                $distinctArr = $city->getChildren()->toArray();

                foreach ($distinctArr as $distinct) {
                    
                    $distinctList[] = $distinct->getId();
                }
            }

            return $distinctList;
        }

        return false;
    }
}