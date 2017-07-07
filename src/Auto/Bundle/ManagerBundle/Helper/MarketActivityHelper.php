<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/3/25
 * Time: 上午11:28
 */

namespace Auto\Bundle\ManagerBundle\Helper;


class MarketActivityHelper extends AbstractHelper{

    public function get_market_activity_normalizer()
    {

        return function (\Auto\Bundle\ManagerBundle\Entity\MarketActivity $a) {
            $base_url = $this->curlHelper->base_url();

            $activity = [
                'adImage'=> $base_url.$this->templating->render('{{ localname|photograph }}',['localname' =>
                        $a->getImage()]),
                'link'  =>$a->getLink(),
                'title'  =>$a->getTitle(),
                'kind'  =>$a->getKind(),
                'subject' =>$a->getSubject(),
                'thumb' =>$a->getThumb()?$base_url.$this->templating->render('{{ localname|photograph }}',
                        ['localname' =>
                        $a->getThumb()]):'',
            ];


            return $activity;
        };
    }

    public function setCurlHelper($curlHelper)
    {
        $this->curlHelper = $curlHelper;
    }


}