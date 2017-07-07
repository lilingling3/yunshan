<?php
/**
 * Created by PhpStorm.
 * User: dongzhen
 * Date: 16/6/6
 * Time: 下午2:45
 */
namespace Auto\Bundle\ManagerBundle\Helper;

class EasyStopHelper extends AbstractHelper{

    //应用平台给予的token
    private $AppId='fassdfasfasfdas';



















    /**
     * 获得签名
     */
    public function getSign($dataArr){

        ksort($dataArr);

        // 查询参数字符串
        $queryStr = '';
        foreach ($dataArr as $k => $v) {
            $queryStr .= $k.'='.$v.'&';
        }

        // 删除最后一位'&'符号  将％后跟的两位数字母变为大写
        $queryStr = strtoupper(trim($queryStr, '&'));

        // 拼接得到签名串
        $signature = urlencode($queryStr);

        //获得Token&
        $token = $this->token.'&';


        //生成签名
        $signValue =$this->getSignature($signature, $token);

        return $signValue;

    }


    
    /**
     * @brief 使用HMAC-SHA1算法生成oauth_signature签名值
     *
     * @param $token 密钥
     * @param $str 源串
     *
     * @return 签名值
     */
    private function getSignature($str, $token) {
        $signature = "";
        if (function_exists('hash_hmac')) {
            //hash_hmac — 使用 HMAC 方法生成带有密钥的哈希值
            $signature = base64_encode(hash_hmac("sha1", $str, $token, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($token) > $blocksize) {
                $key = pack('H*', $hashfunc($token));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                'H*', $hashfunc(
                    ($key ^ $opad) . pack(
                        'H*', $hashfunc(
                            ($key ^ $ipad) . $str
                        )
                    )
                )
            );
            $signature = base64_encode($hmac);
        }
        return $signature;
    }





}