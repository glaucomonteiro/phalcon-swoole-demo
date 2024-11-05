<?php

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function mainAction()
    {
        echo 'This is the default task and the default action that does nothing at all' . PHP_EOL;
    }
}
