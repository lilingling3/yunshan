<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 15/10/10
 * Time: 下午2:34
 */

namespace Auto\Bundle\ManagerBundle\Helper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CarStartHelper extends AbstractHelper{

    public function operate($boxId,$action,$password=null){


        $kernel = $this->container->get("kernel");

        $application = new Application($kernel);
        $application->setAutoExit(false);


        if($password&&$action=='encode'){

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:operate',
                '--id' => $boxId,
                '--operate'=>$action,
                '--password'=>$password,
            ));

        }else{

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:operate',
                '--id' => $boxId,
                '--operate'=>$action,
            ));
        }

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();

        return $content;
    }




    public function action($boxId,$action,$password=null){


        $kernel = $this->container->get("kernel");

        $application = new Application($kernel);
        $application->setAutoExit(false);


        if($password&&$action=='encode'){

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:action',
                '--id' => $boxId,
                '--action'=>$action,
                '--password'=>$password,
            ));

        }else{

            $input = new ArrayInput(array(
                'command' => 'auto:carstart:action',
                '--id' => $boxId,
                '--action'=>$action,
            ));
        }

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();

        return $content;
    }


}