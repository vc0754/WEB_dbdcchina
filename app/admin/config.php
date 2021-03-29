<?php
//配置文件
return [
	'view_replace_str' => [
		'__CSS__'      => '/static/admin/css',
		'__PUBLIC__'   => '/static/public',
		'__JS__'       => '/static/admin/js'
	],
    
    //网站配置参数
    'web_config'    => [
        'is_log'    => 1,   //后台是否开启 写入操作日志
        'file_type' => 'jpg,jpeg,png,gif,mp4,zip', //文件允许上传类型
        'file_size' => 2*1024*1024, //文件上传最大值 2M
    ],
    
    //自定义参数 后台版本
    'version'   => [
        'mb'    => false,    //手机版本
        'en'    => false,    //英文版本
    ],
];