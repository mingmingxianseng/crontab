<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 11:45
 */

namespace crontab;

interface Logger
{
    /**
     * @desc   日志输出
     * @author chenmingming
     *
     * @param string $log   日志内容
     * @param string $label 日志分组
     *
     * @return void
     */
    public function write($log, $label = 'main');
}