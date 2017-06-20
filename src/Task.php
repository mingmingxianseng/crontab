<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 16:24
 */

namespace crontab;

abstract class Task
{
    const ACTION = 'task';
    /** @var  Logger */
    static private $logger;

    /**
     * @desc   run
     * @author chenmingming
     *
     * @param array $args 参数
     *
     * @return void
     */
    abstract public function run();

    public function exceptionHandle(\Throwable $e)
    {
        $this->log($e);
    }

    public function main()
    {
        set_exception_handler([$this, 'exceptionHandle']);
        $this->run();
    }

    protected function log($msg, $label = null)
    {
        if (self::$logger) {
            if (is_null($label)) $label = array_pop(explode('\\', static::class));
            self::$logger->write($msg, $label);
        }
    }

    /**
     * @desc   setLogger
     * @author chenmingming
     *
     * @param Logger $logger
     */
    static public function setLogger(Logger $logger)
    {
        self::$logger = $logger;
    }
}