<?php
/**
 * Created by PhpStorm.
 * User: xuhu
 * Date: 16/6/20
 * Time: 下午4:25
 */

namespace Auto\Bundle\ManagerBundle\Helper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CloudBoxHelper extends AbstractHelper{

    public function operate($boxId,$action,$password=null){

        $kernel = $this->container->get("kernel");

        $application = new Application($kernel);
        $application->setAutoExit(false);


        if($password&&$action=='encode'){

            $input = new ArrayInput(array(
                'command' => 'auto:cloudbox:operate',
                '--id' => $boxId,
                '--operate'=>$action,
                '--password'=>$password,
            ));

        }else{

            $input = new ArrayInput(array(
                'command' => 'auto:cloudbox:operate',
                '--id' => $boxId,
                '--operate'=>$action,
            ));
        }

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();
        return intval($content);
    }


}