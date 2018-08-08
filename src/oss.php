<?php
/**
 * Created by PhpStorm.
 * User: chicho
 * Date: 2018/4/13
 * Time: 14:48
 */

return [

    //支持驱动：阿里云（oss）, 腾讯云（cos）
    'driver' => 'oss',

    //驱动连接参数
    'connection' => [

        //阿里云-oss
        'oss' => [
            'access_id' => '',
            'access_secret' => '',
            'endpoint' => '',
        ],

        //腾讯云-cos
        'cos' => [
            'access_id' => '',
            'access_secret' => '',
            'region' => '',
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

    //是否上传到对象存储同时保存到本地
    'is_save_to_local' => false,

    //true - 不使用对象存储  false - 使用对象存储
    'un_oss' => false,

    //静态文件防止的目录
    'domain' => 'http://localhost'



];