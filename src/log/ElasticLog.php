<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2017/5/10
 * Time: 15:03
 */

namespace crontab\log;

use crontab\Logger;

class ElasticLog implements Logger
{
    private $client;
    private $options = [
        'hosts' => [
            'http://elasticsearch:9200',
        ],
        'index' => 'crontablog',
    ];

    public function __construct($options = [])
    {
        $this->options = array_replace($this->options, $options);
        if (!class_exists('\Elasticsearch\ClientBuilder')) {
            throw new \Exception('need sdk elasticsearch/elasticsearch.');
        }
        $this->client = \Elasticsearch\ClientBuilder::create()->setHosts($this->options['hosts'])->build();
    }

    public function write($log, $label = 'main')
    {
        if (is_object($log) && method_exists($log, '__toString')) {
            $log = $log->__toString();
        } elseif (!is_string($log)) {
            $log = var_export($log, true);
        }

        $this->client->index([
            'index' => $this->options['index'],
            'type'  => $label,
            'body'  => [
                'create_time' => time(),
                'content'     => $log,
            ],
        ]);
    }

}