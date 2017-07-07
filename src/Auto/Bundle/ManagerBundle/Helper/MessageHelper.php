<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/8/14
 * Time: 下午4:55
 */

namespace Auto\Bundle\ManagerBundle\Helper;

class MessageHelper extends AbstractHelper{

    public function get_message_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\Message $m) {

            $message = [
                'messageID' => $m->getId(),
                'title'     => $m->getKind()==1?'系统消息':'活动消息',
                'content'   =>$m->getContent(),
                'url'       =>$m->getUrl(),
                'createTime'=>$m->getCreateTime()->format('Y/m/d H:i'),
                'read'      =>$m->getStatus()
            ];

            return $message;
        };
    }
}