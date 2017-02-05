<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2016/12/14
 * Time: 17:30
 */
return [
    'dispatch' => false,
    'crontab'  => [
        //定时任务状态
        'status'    => true,
        'namespace' => 'demo\\taskss',
        'tasks'     => [
            [
                'status'  => true,
                'name'    => '测试任务1',
                'action'  => 'taskA',
                'arg'     => 'id=1 name=2',
                'crontab' => '*/1 * * * *',
                'log'     => '/dev/shm/taskA.cutlog',
            ],
        ],
    ],
];