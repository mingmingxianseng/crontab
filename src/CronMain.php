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
    protected $php_bin_path = 'php';//php 所在文件
    protected $exec_file_path = ''; //执行文件路径
    protected $log_path = '/dev/null';//定时任务日志文件 默认黑洞
    protected $pid_file_path = '/tmp/crontab.pid';//保存进程id的文件
    protected $pid;

    /** @var  Logger */
    protected $logger;
    /** @var CronTask[] */
    protected $cronTasks = [];
    //定时任务数量
    protected $cronTaskCount = 0;
    protected $options = [
        'namespace' => '',
        'paths'     => [],
        'tasks'     => [
            [
                //该任务状态 true：开启 false:关闭
                'status'  => false,
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
        $this->options = array_merge($this->options, $options);
        set_exception_handler([$this, 'exceptionHandle']);
    }

    /**
     * @desc   exceptionHandle
     * @author chenmingming
     *
     * @param \Throwable $e
     */
    public function exceptionHandle(\Throwable $e)
    {
        $error = sprintf("[%s] %s @%s +%s"
            , $e->getCode()
            , $e->getMessage()
            , $e->getFile()
            , $e->getLine());
        $this->log($error);
        echo "\033[1;40;31m" . $error . "\e[0m\n";
        //删除pid文件
        $this->delPidFile();
    }

    /**
     * @desc   stop
     * @author chenmingming
     */
    public function stop()
    {
        if (!is_file($this->pid_file_path)) {
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
        return unlink($this->pid_file_path);
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
        if (!file_put_contents($this->pid_file_path, $this->pid)) {
            throw new Exception('pid_file_path can not write anything~');
        }
    }

    /**
     * @desc   run
     * @author chenmingming
     */
    public function start()
    {
        $this->start_time = time();
        if (PHP_SAPI != 'cli') {
            throw new Exception("crontab must run in cli,actual is " . PHP_SAPI);
        }
        $this->createPidFile();
        $this->log('main process started!')
            ->log('main process pid:' . $this->pid);
        $this->parseTasks();
        while (true) {
            foreach ($this->cronTasks as $crontabTask)
                $crontabTask->run();
            sleep(10);
            //每次循环前查看有没有
            if ($this->isStop()) {
                break;
            }
        }
        $this->log("main process[{$this->pid}] stopped!");
    }

    /**
     * @desc   run执行某个任务
     * @author chenmingming
     *
     * @param string $action 任务名称（定时任务文件名称）
     * @param array  $arg    任务所需参数
     *
     * @throws Exception
     */
    public function run($action, $arg = [])
    {
        $class = $this->options['namespace'] . "\\" . $action;
        if (!class_exists($class))
            throw new Exception("task $class not exist");
        $task = new $class();
        $task instanceof Task && $task->run($arg);
    }

    /**
     * @desc   parseTasks
     * @author chenmingming
     */
    protected function parseTasks()
    {
        foreach ($this->options['tasks'] as $task) {
            if ($task['status']) {
                $newTask = new CronTask($task['name'], $task['crontab'], $task['arg']);
                $newTask
                    ->setCronMain($this)
                    ->setAction($task['action'])
                    ->setLog($task['log']);
                $this->cronTasks[] = $newTask;
                $this->cronTaskCount++;
                $this->log('load task ' . $newTask->getName());
            }
        }
        $this->log('total tasks:' . $this->cronTaskCount);
        if ($this->cronTaskCount <= 0) {
            throw new Exception("没有设置定时任务");
        }
    }

    /**
     * @desc   checkStop
     * @author chenmingming
     * @return bool
     */
    protected function isStop()
    {
        $pid = file_get_contents($this->pid_file_path);
        if ($pid != $this->pid) {
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
                $msg = "[$label] " . $msg;
            }
            $this->logger->write($msg);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPhpBinPath(): string
    {
        return $this->php_bin_path;
    }

    /**
     * @param string $php_bin_path
     *
     * @return CronMain
     */
    public function setPhpBinPath($php_bin_path): CronMain
    {
        $this->php_bin_path = $php_bin_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getExecFilePath(): string
    {
        return $this->exec_file_path;
    }

    /**
     * @param string $exec_file_path
     *
     * @return CronMain
     */
    public function setExecFilePath($exec_file_path): CronMain
    {
        $this->exec_file_path = $exec_file_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->log_path;
    }

    /**
     * @param string $log_path
     *
     * @return CronMain
     */
    public function setLogPath($log_path): CronMain
    {
        $this->log_path = $log_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPidFilePath(): string
    {
        return $this->pid_file_path;
    }

    /**
     * @param string $pid_file_path
     *
     * @return CronMain
     */
    public function setPidFilePath($pid_file_path): CronMain
    {
        $this->pid_file_path = $pid_file_path;

        return $this;
    }

    /**
     * @return int
     */
    public function getPid(): string
    {
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
}