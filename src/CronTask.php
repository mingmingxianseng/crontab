<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 11:58
 */

namespace crontab;

class CronTask
{
    /** @var  string 定时任务名称 */
    protected $name;
    /** @var  CronTime */
    protected $cronTime;
    /** @var  string 运行参数 */
    protected $arg = '';
    /** @var  string  动作 */
    protected $action;
    /** @var  string 标准输出重定向地址 默认空设备 */
    protected $log = '/dev/null';

    /**
     * @var CronMain
     */
    protected $cronMain;

    /**
     * @var array 最近10运行的时间戳数组
     */
    protected $runTimes = [];
    protected $runCounts = 0;

    /**
     * CronTask constructor.
     *
     * @param array $options 配置
     */
    public function __construct($options, CronMain $cronMain)
    {
        $this->setName($options['name'])
            ->setCronTime($options['crontab'])
            ->setArg($options['arg'])
            ->setAction($options['action'])
            ->setLog($options['log'])
            ->setCronMain($cronMain);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name 任务名称
     *
     * @return CronTask
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return CronTime
     */
    public function getCronTime()
    {
        return $this->cronTime;
    }

    /**
     * @return string
     */
    public function getArg()
    {
        return $this->arg;
    }

    /**
     * @param string $arg
     *
     * @return $this
     */
    public function setArg(string $arg)
    {
        $this->arg = $arg;

        return $this;
    }

    /**
     * @param string $cronTimeStr
     *
     * @return CronTask
     */
    public function setCronTime($cronTimeStr)
    {
        $this->cronTime = CronTime::get($cronTimeStr);

        return $this;
    }

    /**
     * @param CronMain $cronMain
     *
     * @return CronTask
     */
    public function setCronMain($cronMain)
    {
        $this->cronMain = $cronMain;

        return $this;
    }

    /**
     * @desc   run
     * @author chenmingming
     */
    public function run()
    {
        if ($this->cronTime->check() && !$this->hasRuned()) {
            $this->cronMain->log("启动:" . $this->name);
            $cmd = sprintf("%s %s %s %s >> %s"
                , $this->cronMain->getPhpBinPath()
                , $this->cronMain->getExecFilePath()
                , $this->action
                , $this->arg
                , $this->log??'/dev/null'
            );
            $this->cronMain->log($cmd, 'cmd');
            exec($cmd);
        }
    }

    /**
     * @desc   hasRuned 判断当前时间戳是否已经执行过
     * @author chenmingming
     * @return bool
     */
    protected function hasRuned(): bool
    {
        //去掉秒 时间戳精确到分钟
        $timestamp = strtotime(date('Y-m-d H:i:00'));
        if (in_array($timestamp, $this->runTimes)) {
            return true;
        } else {
            array_push($this->runTimes, $timestamp);
            $this->runCounts++;
            if (count($this->runTimes) > 10)
                array_shift($this->runTimes);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return CronTask
     */
    public function setAction(string $action): CronTask
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->log;
    }

    /**
     * @param string $log
     *
     * @return CronTask
     */
    public function setLog(string $log): CronTask
    {
        $this->log = $log ? $log : '/dev/null';

        return $this;
    }

    /**
     * @return int
     */
    public function getRunCounts(): int
    {
        return $this->runCounts;
    }

}