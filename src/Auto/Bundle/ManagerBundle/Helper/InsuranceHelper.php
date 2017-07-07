<?php
/**
 * Created by sublime.
 * User: luyao
 * Date: 16/9/9
 * Time: 下午3:14
 */
namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class InsuranceHelper extends AbstractHelper{

    //商户编号
    private $appkey = "100486";

    //商户密钥
    public $secretkey = '825e9f64c3488b9b';

    //api接口host
    private $host = 'http://api.carsvs.com';

    /**
     * 车辆保险添加接口
     */
    public function add($data)
    {
        $api = '/policy/1.0.0/add';

        // 将需加密数据转换为字符串
        $string2encrypt = urlencode(json_encode($data));

        // 获取加密数据
        $request = $this->doContentEncrypt($string2encrypt);

        $returnMsg = $this->curlHelper->do_post($this->host.$api, $request);

        // 验签
        $result = $this->verify_sign($returnMsg);

        return ['sign'=>$result,'response'=>json_decode($returnMsg)];
    }

    /**
     * 车辆保险结算接口
     */
    public function underwrite($data)
    {
        $api = '/policy/1.0.0/underwrite';


        // 将需加密数据转换为字符串
        $string2encrypt = urlencode(json_encode($data));

        // 获取加密数据
        $request = $this->doContentEncrypt($string2encrypt);

        $returnMsg = $this->curlHelper->do_post($this->host.$api, $request);

        // 验签
        $result = $this->verify_sign($returnMsg);

        return ['sign'=>$result,'response'=>json_decode($returnMsg)];

    }

    /**
     * 车辆保险位置上传接口
     */
    public function reportgps($data)
    {
        $api = '/policy/1.0.0/reportgps';


        // 将需加密数据转换为字符串
        $string2encrypt = urlencode(json_encode($data));

        // 获取加密数据
        $request = $this->doContentEncrypt($string2encrypt);

        $returnMsg = $this->curlHelper->do_post($this->host.$api, $request);


        return ['response'=>json_decode($returnMsg)];
    }


    /**
     * 验证签名
     */
    public function verify_sign($result)
    {
        $detail = json_decode($result);

        $sign = '';
        if (!empty($detail)) {

            $sign = strtoupper(md5($detail->bizcontent));
        }

        return $sign === $detail->sign ? true : false;
    }

    /**
     * 业务数据加密
     */
    public function doContentEncrypt($data)
    {

        //使用方法
        $secretKey  = $this->HexString2Bytes($this->secretkey);

        $bizcontent = base64_encode($this->des_encrypt($data, $secretKey));

        $content = [
            'appkey'     => (string)$this->appkey,
            'bizcontent' => (string)$bizcontent,
            'timestamp'  => (string)time(),
            'sign'       => (string)strtoupper(md5($data))
        ];

        // 查询参数字符串
        $bizStr = '';
        foreach ($content as $k => $v) {
            $bizStr .= $k.'='.$v.'&';
        }
        // 删除最后一位'&'符号
        $bizStr = trim($bizStr, '&');

        return $bizStr;
    }


    /**
     * 业务数据解密
     */
    public function doContentDecrypt($data)
    {

        //使用方法
        $secretKey  = $this->HexString2Bytes($this->secretkey);

        $bizcontent = $this->des_decrypt(base64_decode($data), $secretKey);

        return $bizcontent;
    }

    /**
     * DES加密
     *
     * 标准方法，兼容JAVA DES/ECB/PCKS5Padding
     *
     * @param $str
     * @param $key
     * @return string
     */
    public function des_encrypt($str, $key)
    {
        $block = mcrypt_get_block_size('des', 'ecb');

        $pad = $block - (strlen($str) % $block);

        $str .= str_repeat(chr($pad), $pad);

        return mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);

    }

    /**
     * DES解密
     *
     * 标准方法，兼容JAVA DES/ECB/PCKS5Padding
     *
     * @param $str
     * @param $key
     * @return string
     */
    public function des_decrypt($str, $key)
    {
        $str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);


        $block = mcrypt_get_block_size('des', 'ecb');

        $pad = ord($str[($len = strlen($str)) - 1]);

        return substr($str, 0, strlen($str) - $pad);
    }

    /**
     * Parse字符，根据卡萨维斯DESUtil.java中源码修改
     *
     * @param $c
     * @return int
     */
    private function parse($c)
    {
        if ($c >= 'a')
            return (ord($c) - ord('a') + 10) & 0x0f;
        if ($c >= 'A')
            return (ord($c) - ord('A') + 10) & 0x0f;
        return (ord($c) - ord('0')) & 0x0f;
    }

    /**
     * 从字符串到字节数组转换
     *
     * 根据卡萨维斯DESUtil.java中源码修改
     *
     * @param $hexstr
     * @return string
     */
    private function HexString2Bytes($hexstr)
    {
        $b = '';
        for ($i = 0; $i < strlen($hexstr); $i += 2) {
            $c0 = substr($hexstr, $i, 1);
            $c1 = substr($hexstr, $i + 1, 1);

            $b .= chr(($this->parse($c0) << 4) | $this->parse($c1));
        }
        return $b;
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }

}