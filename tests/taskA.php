<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 13:02
 */

namespace crontab;

class taskA extends CronTask
{
    public function main()
    {
        file_put_contents(__DIR__ . '/log.txt', 'lalallalalla' . PHP_EOL, FILE_APPEND);
    }

}