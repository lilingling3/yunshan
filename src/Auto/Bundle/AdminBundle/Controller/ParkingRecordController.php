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
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/parkingRecord")
 */
class ParkingRecordController extends Controller {

    const PER_PAGE = 20;

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_parking_record_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req,$page = 1)
    {
        $start = self::PER_PAGE*($page - 1);
        $limit = self::PER_PAGE;
        $list = $this->get('auto_manager.easy_stop_helper')->getParkingOrderList($start,$limit);
//        if(empty($list)){
//            $list = array();
//        }
        $total = count($list);
        $nextFlag = $total < self::PER_PAGE ? false : true;

        return ['list'=>$list,'page'=>$page,'nextFlag'=>$nextFlag];
    }

}