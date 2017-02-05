<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 11:11
 */

namespace crontab;

class timeTest extends \PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $nowtime     = strtotime('2017-02-05 12:00:00');
        $crontabTime = '*/10 * * * *';
        $bool        = CronTime::get($crontabTime)->check($nowtime);
        $this->assertEquals($bool, true);

        $nowtime = strtotime(date('2017-02-05 12:01'));
        $bool    = CronTime::get($crontabTime)->check($nowtime);

        $this->assertEquals($bool, false);
    }

    /**
     * @desc   test2
     * @author chenmingming
     * @expectedException \InvalidArgumentException
     */
    public function test2()
    {
        //测试定时任务配置不合法
        $crontabTime = '*/10 * * * * *';
        CronTime::get($crontabTime);
    }

    public function test3()
    {
        $main = new CronMain();

    }
}
