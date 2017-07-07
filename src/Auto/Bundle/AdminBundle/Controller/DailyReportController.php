<?php
/**
 * Created by PhpStorm.
 * User: sunshine
 * Date: 2017/5/17
 * Time: 下午2:27
 */

namespace Auto\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Auto\Bundle\ManagerBundle\Entity\Coupon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @Route("/daily")
 */
class DailyReportController  extends Controller {

    /**
     * @Route("/list/{page}", methods="GET", name="auto_admin_daily_list",
     * requirements={"page"="\d+"},
     * defaults={"page"=1})
     * @Template()
     */
    public function listAction(Request $req) {

        $qb =
            $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Area')
                ->createQueryBuilder('a');

        $provinceLists =
            $qb
                ->select('a')
                ->where($qb->expr()->isNull('a.parent'))
                ->getQuery()
                ->getResult();

        $provinceids = array();

        foreach($provinceLists as $province){
            $provinceids[] = $province->getId();
        }

        $cities =
            $qb
                ->select('a')
                ->where($qb->expr()->in('a.parent',$provinceids))
                ->andWhere($qb->expr()->like('a.name',':area'))
                ->setParameter('area','%市')
                ->getQuery()
                ->getResult();
        $cars =
            $this->getDoctrine()
                ->getRepository('AutoManagerBundle:Car')
                ->findAll();

        return ['cities' => $cities,'cars' => $cars];
    }

    /**
     * 导出报表
     *
     * @Route("/output", methods="POST", name="auto_admin_daily_output")
     */
    public function outPutExcelAction(Request $req) {


        $sTime = $req->request->get('startTime');
        $eTime = $req->request->get('endTime');
        $cModel = $req->request->get('carModel');
        $cityID  = $req->request->get('city');


        $qb =
            $this
                ->getDoctrine()
                ->getRepository('AutoManagerBundle:RentalOrder')
                ->createQueryBuilder('r')
                ->select('r')
                ->join('r.rentalCar','c')
                ->join('r.pickUpStation','p')
        ;

        // 订单起止时间
        if (!empty($sTime) && !empty($eTime)) {

            $qb
                ->where($qb->expr()->gte('r.createTime', ':startTime'))
                ->andWhere($qb->expr()->lte('r.createTime', ':endTime'))
                ->setParameter('startTime', new \DateTime($sTime))
                ->setParameter('endTime', new \DateTime($eTime))
            ;
        }

        // 车型
        if ($cModel) {

            $qb
                ->andWhere($qb->expr()->eq('c.car', ':rentalCar'))
                ->setParameter('rentalCar', $cModel)
            ;
        }

        // 区域
        if($cityID) {

            $cityAreas =$this->get('auto_manager.area_helper')->getDistinct($cityID);

            $qb
                ->andWhere($qb->expr()->in('p.area', ':area'))
                ->setParameter('area', $cityAreas)
            ;
        }

        $orderList =
            $qb
                ->getQuery()
                ->getResult();

        // warning and redirect
        if (empty($orderList)) {

            echo "<script>alert('无有效数据');window.location= '/admin/daily/list'</script>";

            return;
        }

        $list = [];
        foreach ($orderList as $val) {

            $list[] = $this->get('auto_manager.order_helper')->get_daily_data_normalizer($val);
        }

        $date = $sTime ? (new \DateTime($sTime))->format('m.d')  : (new \DateTime())->format('m.d');


        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        $phpExcelObject->getProperties()->setCreator("yunshan")
            ->setTitle("云杉运营日报")
            ->setSubject("Office 2005 XLSX Document")
            ->setDescription("云杉运营日报.")
            ->setKeywords("运营 日报");

        $phpExcelObject->getActiveSheet()->setTitle('广州');

        // 设置当前活跃Sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // 初始化头部
        $phpExcelObject->getActiveSheet()
            ->setCellValue('A1', "手机号")
            ->setCellValue('B1', "姓名")
            ->setCellValue('C1', "车牌号")
            ->setCellValue('D1', "车型名称")
            ->setCellValue('E1', "下单时间")
            ->setCellValue('F1', "取车时间")
            ->setCellValue('G1', "还车时间")
            ->setCellValue('H1', "用车时长")
            ->setCellValue('I1', "订单状态")
            ->setCellValue('J1', "产生费用(元)")
            ->setCellValue('K1', "里程")
            ->setCellValue('L1', "取车网点")
            ->setCellValue('M1', "租用时长")
            ->setCellValue('N1', "实际支付")
            ->setCellValue('O1', "优惠券抵扣")
            ->setCellValue('P1', "余额抵扣")
            ->setCellValue('Q1', "租赁类型")
            ->setCellValue('R1', "时段")
        ;

        // 定义初始数据行数
        $curRow = 2;

        // 插入数据
        foreach ($list as $_k => $_v) {
            $curColumn = 'A';
            $count = count($_v);

            for ($i = 1; $i <= $count; $i++) {
                $phpExcelObject->getActiveSheet()->setCellValue($curColumn . $curRow, array_shift($_v));
                $curColumn++;
            }
            $curRow ++;
        }

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'dailyReport'. $date .'.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }



}
