<?php

namespace crontab\log;

use crontab\Logger;

class CommonLog implements Logger
{
    public function write($log, $label = 'main')
    {
        if (is_array($log)) {
            foreach ($log as $str) {
                $this->write($str, $label);
            }
        } else {
            if (is_object($log) && method_exists($log, '__toString')) {
                $log = $log->__toString();
            } elseif (!is_string($log)) {
                $log = var_export($log, true);
            }
            echo "[" . date('Y-m-d H:i:s') . "][{$label}]" . $log . "\n\n";
        }
    }

}