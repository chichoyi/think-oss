<?php
/**
 * Created by PhpStorm.
 * User: chicho
 * Date: 2018/4/13
 * Time: 14:48
 */

return [

    //支持驱动：阿里云（oss）, 此版本暂时只支持使用阿里云驱动
    'driver' => 'oss',

    //驱动连接参数
    'connection' => [

        //阿里云-oss
        'oss' => [
            'access_id' => '',
            'access_secret' => '',
            'endpoint' => '',
            'endpoint_internal' => '',
        ],

    ],

    //文件目录
    'directory' => [
        'DEFAULT' => [ 'dir' => 'default/', 'type' => 'default'],
        'PRIVATE' => [ 'dir' => 'private/default/', 'type' => 'private_default'],
        //...
    ],

    //生产桶
    'buckets' => [
        'default' => 'your bucket',
        'private_bucket' => 'your private bucket',
        //...
    ],

    //自动切换测试桶的标志,
    'test_sign' => 0,

    //测试桶
    'buckets_for_test' => [
        'default' => 'your bucket',
        'private_bucket' => 'your private bucket',
        //...
    ],



];