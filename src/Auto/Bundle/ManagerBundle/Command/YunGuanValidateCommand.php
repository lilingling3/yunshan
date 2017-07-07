<?php
/**
 * Created by PhpStorm.
 * User: liyandong
 * Date: 16/6/2
 * Time: 17:39
 * 各种总数统计
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class YunGuanValidateCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:yunguan:validate')
            ->setDescription('yunguan:validate')
//            ->addOption('day', 'day', InputOption::VALUE_OPTIONAL, 'day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $man = $this->getContainer()->get('doctrine')->getManager();
        $qb =
            $man
                ->getRepository('AutoManagerBundle:AuthMember')
                ->createQueryBuilder('m')
        ;
        //为经过第三方审核的认证成功用户
        $authMembers = $qb
            ->select('m')
            ->andWhere($qb->expr()->isNull('m.validateResult'))

            ->andWhere($qb->expr()->eq('m.licenseImageAuthError', ':licenseImageAuthError'))
            ->setParameter('licenseImageAuthError', 0)

            ->andWhere($qb->expr()->eq('m.idImageAuthError', ':idImageAuthError'))
            ->setParameter('idImageAuthError', 0)

            ->andWhere($qb->expr()->eq('m.idHandImageAuthError', ':idHandImageAuthError'))
            ->setParameter('idHandImageAuthError', 0)

            ->andWhere($qb->expr()->eq('m.mobileCallError', ':mobileCallError'))
            ->setParameter('mobileCallError', 0)

            ->andWhere($qb->expr()->eq('m.validateError', ':validateError'))
            ->setParameter('validateError', 0)

            ->orderBy('m.createTime', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($authMembers as $v) {
            $this->updateValidateResult($v);
        }


    }


    protected function updateValidateResult(\Auto\Bundle\ManagerBundle\Entity\AuthMember $authMember){
        $man = $this->getContainer()->get('doctrine')->getManager();
        $rentalStation = $man->getRepository('AutoManagerBundle:RentalStation')->find(7);

        $param = array(
            'sfzh' => $authMember->getMember()->getIdNumber(),  //身份证号
            'xm' => $authMember->getMember()->getName(),    //姓名
            'xb' => $authMember->getMember()->getSex()==901?'男':'女', //性别
            'mz' => $authMember->getMember()->getNation(),  //民族
            'lxdh' => $authMember->getMember()->getMobile(),    //联系电话
            'zz' => $authMember->getMember()->getAddress(), //住址
            'msmc' => $rentalStation->getName(),  //门店名称
            'msdh' => $rentalStation->getContactMobile(),    //门店电话
            'msdz' => $rentalStation->getStreet(),   //门店地址
            'qydm' => $rentalStation->getCompany()->getEnterpriseCode(),  //企业代码
            'qymc' =>  $rentalStation->getCompany()->getName(),   //企业名称
            'qydz' => $rentalStation->getArea()->getParent()->getParent()->getName().$rentalStation->getArea()->getParent()->getName().$rentalStation->getArea()->getName().$rentalStation->getCompany()->getAddress()  //企业地址
        );
//        dump($param);die;
        $url = "http://zulin.beijingyunshu.cn:11000/ReportWS/services/IReport?WSDL" ;

        //header("content-type:text/html;charset=utf-8");
        libxml_disable_entity_loader(false);
        try{
            $client = new \SoapClient($url, array('cache_wsdl' => WSDL_CACHE_NONE));
        }catch (\SoapFault $fault){
            dump(date('Y-m-d H:i:s').': 运管局第三方认证不可访问');
            return false;
        }

//        code	int	必填	0：数据报送成功且当前承租人身份核实没有问题
//        XXX：正整数，业务类型的数据错误，租赁企业应根据错误类型进行处理，并重新进行必要的数据报送。
//        -XXX:负整数，当前承租人身份核实报警
//        message	String	必填	包含具体的错误信息和当前承租人身份核实的报警信息
        $arr = $client->sendData($param);
        $result = '';
        if( $arr->code == 0 ){
            $result = '承租人身份核实没有问题。';
            $authMember->setValidateResult($result);
            $man->persist($authMember);
            $man->flush();
        }else if( $arr->code == 1 ){
            $result = '承租人身份核实报警!!!。';
            $authMember->setValidateResult($result);
            $authMember->setValidateError(1);
            $man->persist($authMember);
            $man->flush();

            $member = $man
                ->getRepository('AutoManagerBundle:Member')
                ->find($authMember->getMember()->getId());

            $status = \Auto\Bundle\ManagerBundle\Entity\AuthMember::AUTH_FAILED;
            //给用户发送短信
            $this->getContainer()->get("auto_manager.sms_helper")->add(
                $authMember->getMember()->getMobile(),
                $this->getContainer()->get("auto_manager.member_helper")->get_auth_error_message_new($status));


            $message = new \Auto\Bundle\ManagerBundle\Entity\Message();
            $message->setContent($this->getContainer()->get("auto_manager.member_helper")->get_auth_error_message_new($status,0))
                ->setKind(1)
                ->setMember($member)
                ->setStatus(\Auto\Bundle\ManagerBundle\Entity\Message::MESSAGE_UNREAD)
            ;
            $man->persist($message);
            $man->flush();
        }else{
            $result = '数据错误，请重新进行数据报送。';
        }
        dump('auth_member_id:'.$authMember->getId().'，'.$result);
        return true;


    }




}