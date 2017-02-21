<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 20:29
 */
use mmapi\core\Config;
use mmapi\core\App;

$vpath = dirname(__DIR__);
require_once $vpath . '/vendor/autoload.php';

Config::set('vpath', $vpath);
Config::set('conf_path', __DIR__);
Config::set('conf_file', ['crontab.php']);
App::start();

$argv = $_SERVER['argv'];
//去除文件
array_shift($argv);
//去除第二个参数 剩下的都是参数
$action    = array_shift($argv);
$arguments = [];
foreach ($argv as $arg) {
    list($k, $v) = explode("=", $arg);
    $arguments[$k] = (string)$v;
}

//启动主线程
$main = new crontab\CronMain(Config::get('crontab'));
$main->setRunFile(__FILE__)
    ->setLogger(new crontab\log\CronLog());
if (is_null($action)) {
    $main->start();
} else {
    $main->run($action, $arguments);
}