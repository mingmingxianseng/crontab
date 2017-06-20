<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 11:32
 */

namespace crontab;

use \Exception;

class CronMain
{
    protected $start_time;//定时任务开启的时间
    protected $pid;

    /** @var  Logger */
    protected $logger;
    /** @var CronTask[] */
    protected $cronTasks = [];
    //定时任务数量
    protected $cronTaskCount = 0;
    protected $options = [
        'namespace' => '',
        'php_bin'   => 'php',
        'pid_path'  => '/tmp/crontab.pid',
        'log_path'  => '/dev/null',
        'entrance'  => '',
        'paths'     => [],
        'tasks'     => [
            [
                //该任务描述
                'name'    => 'demo',
                //该任务名称
                'action'  => 'demoA',
                //该任务参数
                'arg'     => 'id=1',
                //该任务时间配置
                'crontab' => '*/1 * * * *',
                //该任务标准输出重定向
                'log'     => '/dev/null',
            ],
        ],
    ];

    /**
     * crontabMain constructor.
     *
     * @param array $options 配置数组
     *
     * @throws Exception
     */
    public function __construct(array $options)
    {
        $this->options = array_replace($this->options, $options);
        if (!$this->options['entrance']) {
            $this->options['entrance'] = $_SERVER['argv'][0];
        }
    }

    /**
     * @desc   exceptionHandle
     * @author chenmingming
     *
     * @param \Throwable $e
     */
    public function exceptionHandle(\Throwable $e)
    {
        $this->log($e->__toString());

        //删除pid文件
        $this->delPidFile();
    }

    /**
     * @desc   stop
     * @author chenmingming
     */
    public function stop()
    {
        if (!is_file($this->options['pid_path'])) {
            throw new Exception("pid_file is not exist");
        }
        if (!$this->delPidFile()) {
            throw new Exception("stop main process failed!");
        }
    }

    /**
     * @desc   delPidFile
     * @author chenmingming
     * @return bool
     */
    public function delPidFile(): bool
    {
        return unlink($this->options['pid_path']);
    }

    /**
     * @desc   createPidFile
     * @author chenmingming
     * @throws Exception
     */
    private function createPidFile()
    {
        if (!function_exists('posix_getpid')) {
            throw new Exception("crontab need posix_getpid function");
        }
        $this->pid = posix_getpid();
        $rs        = file_put_contents($this->options['pid_path'], $this->pid);
        if ($rs <= 0) {
            throw new Exception('pid_file_path can not write anything~');
        }
    }

    /**
     * @desc   run
     * @author chenmingming
     */
    public function start()
    {
        set_exception_handler([$this, 'exceptionHandle']);
        $this->start_time = time();
        if (PHP_SAPI != 'cli') {
            throw new Exception("crontab must run in cli,actual is " . PHP_SAPI);
        }
        $this->createPidFile();
        $this->log('主进程启动成功')
            ->log('主进程 pid:' . $this->pid . ' @' . $this->options['pid_path']);
        $this->parseTasks();
        while (true) {
            foreach ($this->cronTasks as $crontabTask)
                $crontabTask->run();
            sleep(10);
            //每次循环前查看有没有
            if ($this->isStop())
                break;
        }
        $this->log("主进程 [{$this->pid}] 退出.");
    }

    /**
     * @desc   parseTasks
     * @author chenmingming
     */
    protected function parseTasks()
    {
        foreach ($this->options['tasks'] as $task) {
            $newTask           = new CronTask($task, $this);
            $this->cronTasks[] = $newTask;
            $this->cronTaskCount++;
            $this->log('load task ' . $newTask->getName());
        }
        $this->log('所有任务加载成功.');
        $this->log('任务数量:' . $this->cronTaskCount);
        if ($this->cronTaskCount <= 0)
            throw new Exception("配置中没有任务");
    }

    /**
     * @desc   checkStop
     * @author chenmingming
     * @return bool
     */
    protected function isStop()
    {
        $pid = file_get_contents($this->options['pid_path']);
        if ($pid != $this->pid) {
            $this->log('pid is diff.prepare to exit.[' . $pid . ']');

            return true;
        }

        return false;
    }

    /**
     * @desc   log 记录日志
     * @author chenmingming
     *
     * @param  string $msg   日志内容
     * @param string  $label 标签
     *
     * @return CronMain
     */
    public function log(string $msg, $label = 'main'): CronMain
    {
        if ($this->logger) {
            if ($label) {
                $msg = "[{$this->pid}] " . $msg;
            }
            $this->logger->write($msg, $label);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPhpBinPath(): string
    {
        return $this->options['php_bin'];
    }

    /**
     * @return string
     */
    public function getExecFilePath(): string
    {
        return $this->options['entrance'];
    }

    /**
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->options['log_path'];
    }

    /**
     * @return string
     */
    public function getPidFilePath(): string
    {
        return $this->options['pid_path'];
    }

    /**
     * @return int
     */
    public function getPid(): string
    {
        if ($this->pid === null) {
            $this->pid = file_get_contents($this->options['pid_path']);
        }

        return $this->pid;
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     *
     * @return CronMain
     */
    public function setLogger(Logger $logger): CronMain
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @desc   isRunning 判断守护进程是否已经启动
     * @author chenmingming
     * @return bool
     */
    public function isRunning()
    {
        if ($this->getPid()) {
            exec("ps -eo pid | grep {$this->getPid()}", $output);
            if ($output) {
                return true;
            }
        }

        return false;
    }

    public function open()
    {
        $command = sprintf("%s %s > %s &"
            , $this->getPhpBinPath()
            , $this->getExecFilePath()
            , $this->getLogPath()
        );
        exec($command);
    }

    public function close()
    {
        $this->delPidFile();
    }
}