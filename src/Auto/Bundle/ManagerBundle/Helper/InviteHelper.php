<?php
/**
 * Created by sublime.
 * User: luyao
 * Date: 16/9/9
 * Time: 下午3:14
 */
namespace Auto\Bundle\ManagerBundle\Helper;


use Symfony\Component\Validator\Constraints\DateTime;

class InviteHelper extends AbstractHelper{

    //推送信息
    const PUSH_PAYMENT_MESSAGE = '1';

    //添加用户消息
    const ADD_PAYMENT_MESSAGE = '2';

    public function get_invite_normalizer()
    {
        return function (\Auto\Bundle\ManagerBundle\Entity\InviteReward $i) {

            $invite = [

                'inviter'       => $i->getRelative()->getInviter()->getName(),
                'inviteeMobile' => $i->getRelative()->getInviteeMobile() ? $this->globalHelper->mobile_protect($i->getRelative()->getInviteeMobile()): "",
                'createTime'    => $i->getRelative()->getCreateTime()->format('Y-m-d H:i:s'),
                'channel'       => $i->getRelative()->getChannel(),
                'amount'        => empty($i->getRechargeRecord()) ? null: $i->getRechargeRecord()->getAmount(),
                'rewardTime'    => empty($i->getRechargeRecord()) ? null: $i->getRechargeRecord()->getCreateTime()->format('Y-m-d H:i:s'),

                'inviteeName'   => $i->getInvitee()->getMember()->getName() ? $this->globalHelper->name_protect($i->getInvitee()->getMember()->getName()): ""

            ];

            return $invite;
        };
    }


    public function setGlobalHelper($globalHelper)
    {
        $this->globalHelper = $globalHelper;
    }
    
}