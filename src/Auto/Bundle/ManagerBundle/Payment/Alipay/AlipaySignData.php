<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/9/11
 * Time: 下午4:43
 */

namespace Auto\Bundle\ManagerBundle\Payment\Alipay;


class AlipaySignData {
    protected $values = array();

    public function SetPartner($value)
    {
        $this->values['partner'] = $value;
    }

    public function GetPartner()
    {
        return $this->values['partner'];
    }

    public function IsPartnerSet()
    {
        return array_key_exists('partner', $this->values);
    }


    public function SetSellerId($value)
    {
        $this->values['seller_id'] = $value;
    }

    public function GetSellerId()
    {
        return $this->values['seller_id'];
    }

    public function IsSellerIdSet()
    {
        return array_key_exists('seller_id', $this->values);
    }


    public function SetOutTradeNo($value)
    {
        $this->values['out_trade_no'] = $value;
    }

    public function GetOutTradeNo()
    {
        return $this->values['out_trade_no'];
    }

    public function IsOutTradeNoSet()
    {
        return array_key_exists('out_trade_no', $this->values);
    }


    public function SetSubject($value)
    {
        $this->values['subject'] = $value;
    }

    public function GetSubject()
    {
        return $this->values['subject'];
    }

    public function IsSubjectSet()
    {
        return array_key_exists('subject', $this->values);
    }


    public function SetBody($value)
    {
        $this->values['body'] = $value;
    }

    public function GetBody()
    {
        return $this->values['body'];
    }

    public function IsBodySet()
    {
        return array_key_exists('body', $this->values);
    }


    public function SetTotalFee($value)
    {
        $this->values['total_fee'] = $value;
    }

    public function GetTotalFee()
    {
        return $this->values['total_fee'];
    }

    public function IsTotalFeeSet()
    {
        return array_key_exists('total_fee', $this->values);
    }


    public function SetService($value)
    {
        $this->values['service'] = $value;
    }

    public function GetService()
    {
        return $this->values['service'];
    }

    public function IsServiceSet()
    {
        return array_key_exists('service', $this->values);
    }


    public function SetPaymentType($value)
    {
        $this->values['payment_type'] = $value;
    }

    public function GetPaymentType()
    {
        return $this->values['payment_type'];
    }

    public function IsPaymentTypeSet()
    {
        return array_key_exists('payment_type', $this->values);
    }


    public function SetNotifyUrl($value)
    {
        $this->values['notify_url'] = $value;
    }

    public function GetNotifyUrl()
    {
        return $this->values['notify_url'];
    }

    public function IsNotifyUrlSet()
    {
        return array_key_exists('notify_url', $this->values);
    }


    public function SetInputCharset($value)
    {
        $this->values['_input_charset'] = $value;
    }

    public function GetInputCharset()
    {
        return $this->values['_input_charset'];
    }

    public function IsInputCharsetSet()
    {
        return array_key_exists('_input_charset', $this->values);
    }


    public function SetSignType($value)
    {
        $this->values['sign_type'] = $value;
    }

    public function GetSignType()
    {
        return $this->values['sign_type'];
    }

    public function IsSignTypeSet()
    {
        return array_key_exists('sign_type', $this->values);
    }

    public function SetSign($value)
    {
        $this->values['sign'] = $value;
    }

    public function GetSign()
    {
        return $this->values['sign'];
    }

    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }
    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
    }


}