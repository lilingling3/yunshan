<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/9/9
 * Time: 下午4:20
 */

namespace Auto\Bundle\ManagerBundle\Helper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GlobalHelper extends AbstractHelper{
    /**
     * 缴费订单表中的缴费类型
     * @return array
     */
    public function get_payment_order_kind_arr()
    {
        $arr = [
            '1'=>'车辆维修',
            '2'=>'违规停车费',
            '3'=>'车辆停运费',
            '4'=>'违规恶意用车',
            '5'=>'车辆清洁',
            '6'=>'人工服务费',
            '7'=>'异地还车费',
            '8'=>'车辆使用押金',
            '9'=>'违章服务费',
            '99'=>'其他费用'
        ];
        return $arr;
    }



    /**
     * 缴费订单表中的缴费类型
     * @return array
     */
    public function get_payment_order_kind_data($a)
    {
        $arr = [
            '1'=>'车辆维修',
            '2'=>'违规停车费',
            '3'=>'车辆停运费',
            '4'=>'违规恶意用车',
            '5'=>'车辆清洁',
            '6'=>'人工服务费',
            '7'=>'异地还车费',
            '8'=>'车辆使用押金',
            '9'=>'违章服务费',
            '99'=>'其他费用'
        ];
        return $arr[$a];
    }

    /**
     * 姓名保护(只保留首尾)
     * @return string
     */
    public function name_protect($name)
    {

        if (empty($name)) { return false; }

        // 去掉名字中 "·"
        $name = str_replace("·", "", $name);

        $length = mb_strlen($name, 'utf-8');

        if ($length >= 1) {
            
            $firstName = mb_substr($name, 0, 1, 'utf-8');

            $protectName = " ";
            $lastName = "*";
            if ($length >= 3) {

                $lastName = mb_substr($name, $length-1, 1, 'utf-8');
                for ($i=0; $i < $length - 2; $i++) {

                    $protectName .= "*";
                }
            }
        }

        return $firstName . $protectName . $lastName;
    }

    /**
     * 电话号保护
     */
    public function mobile_protect($mobile)
    {

        if (empty($mobile)) { return false; }

        return substr($mobile,0, 3) . '****' . substr($mobile,7, 4);
    }

    /**
     * 取回车辆问题
     */
    public function car_problem(){
        $arr = array(
            1 => '车辆异常',
            2 => '车身剐蹭',
            3 => '前后挡风破损',
            4 => '反光镜破损',
            5 => '门窗破损',
            6 => '门窗未关',
            7 => '雨刷器破损',
            8 => '保险杠破损'
        );
        return $arr;
    }

}