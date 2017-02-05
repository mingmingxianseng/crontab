<?php

namespace crontab\log;

use crontab\Logger;

class CronLog implements Logger
{
    public function write($log)
    {
        if (is_array($log)) {
            foreach ($log as $str) {
                $this->write($str);
            }
        } else {
            echo "[" . date('Y-m-d H:i:s') . "]" . $log . "\n\n";
        }
    }

}