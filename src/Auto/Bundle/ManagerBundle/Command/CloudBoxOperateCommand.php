<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/6/20
 * Time: 下午4:00
 */

namespace Auto\Bundle\ManagerBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CloudBoxOperateCommand extends ContainerAwareCommand{

    public function configure()
    {
        $this
            ->setName('auto:cloudbox:operate')
            ->setDescription('cloud box operate')
            ->addOption('id', 'id', InputOption::VALUE_REQUIRED, 'cloud box id')
            ->addOption('operate', 'o', InputOption::VALUE_REQUIRED, 'operate option (open close gps find status)')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'password')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getOption('id');
        $operate = $input->getOption('operate');

        if($operate =='encode'){

            $password = $input->getOption('password');
            if(!$password){

                echo 0;exit;

            }
            $process = new Process('python /letv/data/tbox/operate.py '.$id.' '.$operate.' '.$password);
        }else{

            $process = new Process('python /letv/data/tbox/operate.py '.$id.' '.$operate);
        }



        try {
            $process->mustRun();
            $output->writeln($process->getOutput());
        } catch (ProcessFailedException $e) {
            $output->writeln(0);
        }

    }
}