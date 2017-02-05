<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/2/5
 * Time: 12:02
 */

namespace crontab;

use \InvalidArgumentException;

class CronTime
{
    /* *
     * 0 7 * * *  每天早上7点
     * 20 0-23/2 * * * 每月每天的午夜 0 点 20 分, 2 点 20 分, 4 点 20 分
     * 第1列表示分钟1～59 每分钟用*或者 1表示
     * 第2列表示小时1～23（0表示0点）
     * 第3列表示日期1～31
     * 第4列表示月份1～12
     * 第5列标识号星期0～6（0表示星期天）
     * */
    protected $minute = [];
    protected $hour = [];
    protected $day = [];
    protected $month = [];
    protected $week = [];
    /** @var CronTime[] */
    static protected $instances = [];
    /**
     * @var string 时间配置
     */
    protected $config = '';

    /**
     * CronTime constructor. 构造函数
     *
     * @param string $config 时间配置
     */
    private function __construct($config)
    {
        $this->config = trim($config);
        $this->format();
    }

    /**
     * @desc   get
     * @author chenmingming
     *
     * @param string $config crontab 时间配置
     *
     * @return CronTime
     */
    static public function get($config): CronTime
    {
        $key = md5($config);
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($config);
        }

        return self::$instances[$key];
    }

    /**
     * 格式化crontab时间设置字符串,用于比较
     *
     * @throws InvalidArgumentException
     */
    private function format()
    {
        //格式检查
        $reg = '#^((\*(/\d+)?|((\d+(-\d+)?)(?3)?)(,(?4))*))( (?2)){4}$#';
        if (!preg_match($reg, $this->config)) {
            throw new InvalidArgumentException("crontab格式不合法 :[{$this->config}]");
        }
        //分别解析分、时、日、月、周
        $parts        = explode(' ', $this->config);
        $this->minute = self::parseCronPart($parts[0], 0, 59);//分
        $this->hour   = self::parseCronPart($parts[1], 0, 59);//时
        $this->day    = self::parseCronPart($parts[2], 1, 31);//日
        $this->month  = self::parseCronPart($parts[3], 1, 12);//月
        $this->week   = self::parseCronPart($parts[4], 0, 6);//周（0周日）
    }

    /**
     * 解析crontab时间计划里一个部分(分、时、日、月、周)的取值列表
     *
     * @param string $part  时间计划里的一个部分，被空格分隔后的一个部分
     * @param int    $f_min 此部分的最小取值
     * @param int    $f_max 此部分的最大取值
     *
     * @return array 若为空数组则表示可任意取值
     * @throws InvalidArgumentException
     */
    static private function parseCronPart($part, $f_min, $f_max): array
    {
        $list = [];

        //处理"," -- 列表
        if (false !== strpos($part, ',')) {
            $arr = explode(',', $part);
            foreach ($arr as $v) {
                $tmp  = self::parseCronPart($v, $f_min, $f_max);
                $list = array_merge($list, $tmp);
            }

            return $list;
        }

        //处理"/" -- 间隔
        $tmp  = explode('/', $part);
        $part = $tmp[0];
        $step = isset($tmp[1]) ? $tmp[1] : 1;

        //处理"-" -- 范围
        if (false !== strpos($part, '-')) {
            list($min, $max) = explode('-', $part);
            if ($min > $max) {
                throw new InvalidArgumentException('使用"-"设置范围时，左不能大于右');
            }
        } elseif ('*' == $part) {
            $min = $f_min;
            $max = $f_max;
        } else {//数字
            $min = $max = $part;
        }

        //空数组表示可以任意值
        if ($min == $f_min && $max == $f_max && $step == 1) {
            return $list;
        }

        //越界判断
        if ($min < $f_min || $max > $f_max) {
            throw new InvalidArgumentException('数值越界。应该：分0-59，时0-59，日1-31，月1-12，周0-6');
        }

        return $max - $min > $step ? range($min, $max, $step) : [(int)$min];
    }

    /**
     * @return array
     */
    public function getMinute(): array
    {
        return $this->minute;
    }

    /**
     * @return array
     */
    public function getHour(): array
    {
        return $this->hour;
    }

    /**
     * @return array
     */
    public function getDay(): array
    {
        return $this->day;
    }

    /**
     * @return array
     */
    public function getMonth(): array
    {
        return $this->month;
    }

    /**
     * @return array
     */
    public function getWeek(): array
    {
        return $this->week;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    /**
     * @desc   check 检查某个时间戳是否可以执行定时任务
     * @author chenmingming
     *
     * @param null|int $timestamp
     *
     * @return bool
     */
    public function check($timestamp = null): bool
    {
        //初始化时间戳 不传默认当前时间戳
        is_null($timestamp) && $timestamp = time();
        list($minute, $hour, $day, $month, $week) = explode('-', date('i-G-j-n-w', $timestamp));

        return (!$this->minute || in_array($minute, $this->minute))
            && (!$this->hour || in_array($hour, $this->hour))
            && (!$this->day || in_array($day, $this->day))
            && (!$this->month || in_array($month, $this->month))
            && (!$this->week || in_array($week, $this->week));
    }
}