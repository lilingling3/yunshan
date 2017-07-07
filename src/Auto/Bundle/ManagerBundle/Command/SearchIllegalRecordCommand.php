<?php
/**
 * Created by PhpStorm.
 * User: sunshine
 * Date: 2017/4/25
 * Time: 上午11:51
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SearchIllegalRecordCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('search:illegal:record')
            ->setDescription('search ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $res = $this->getContainer()->get('auto_manager.illegal_record_helper')->getCityList();

        var_dump($res);


    }


}