<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/11/6
 * Time: 上午11:43
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class CurlHelper
{

    function get_url_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1")
            return file_get_contents($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function do_put($url, $data, $getResult = true, $log = false)
    {
        $time_start = $this->microTime();
        if($log)
        {
            file_put_contents('/data/logs/debug.log', '[' . date('y/m/d H:i:s') . ']: start:'
                . $time_start . ': [url]:' . $url . ' [data]:' . ($data) . "\n", FILE_APPEND);
        }
        $headers = ['content-type: application/json'];
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, 1);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_NOSIGNAL, true);

        if (!$getResult) {
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT_MS, 500);
            if($log)
            {
                file_put_contents('/data/logs/debug.log', '[' . date('y/m/d H:i:s') . ']:middle:'
                    . $this->microTime() . ': [url]:' . $url . ' [data]:' . ($data) . "\n", FILE_APPEND);
            }
        }

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($handle);
        curl_close($handle);

        $time_use = $this->microTime();
        if($log)
        {
            file_put_contents('/data/logs/debug.log', '[' . date('y/m/d H:i:s') . ']:   end:'
                . $time_use . ': [url]:' . $url . ' [data]:' . ($data) . "\n", FILE_APPEND);
        }

        if (!empty($response)) {
            if($log)
            {
                file_put_contents('/data/logs/debug.log', '[' . date('y/m/d H:i:s') . ']:    re:'
                    . $time_use . ': [url]:' . $url . ' [data]:' . ($response) . "\n", FILE_APPEND);
            }
        }

        return $response;
    }

    private function sock_put($body)
    {

    }

    private function microTime()
    {

        list($usec, $sec) = explode(" ", microtime());

        return (int)(($usec + $sec) * 1000);
    }

    function do_post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }

//将json_decode后的array object转化为array
    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }


    function base_url()
    {

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseUrl = "$protocol$_SERVER[HTTP_HOST]";
        return $baseUrl;

    }

}