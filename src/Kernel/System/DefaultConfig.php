<?php
namespace Peas\Kernel\System;

/**
 * Peas Framework
 *
 * Framework默认配置
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class DefaultConfig
{
    public static function get()
    {
        return [
            /* 常规设置  */
            '_default.timezone' => 'PRC',                                 // 默认时区


            /* 缓存默认设置，CacheCenter */
            '_cache.prefix'             => '',                            // key前缀
            '_cache.defaultLifetime'    => 86400,                         // int -1表示永久有效
            '_cache.defaultStore'       => 'file',                        // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称
            '_cache.defaultStoreConfig' => [                              // 默认存储器参数，没有可不传
                'directory' => _PATH . '/storage/app/cache',
            ],


            /* 日志默认设置 ，LogCenter */
            '_log.loggers'     => 'file',                                  // 启用的日志写入器，多个用','分开
            '_log.file.writer' => 'file',                                  // 指定写入器类型，自带file、syslog，支持自定义，但是自定义写入器需实现接口Peas\Log\Writer\WriterInterface，传入完整名称如：Peas\Log\Writer\FlieWriter
            '_log.file.writerConfig' => [                                  // 传入写入器构造函数的参数，自带syslog无需此参数，file需要以下示例参数
                'dir'         => _PATH . '/storage/logs',                  // file类型：Log文件目录
                'destination' => '',                                       // file类型：文件名，设置为空时将按时间生成Y-m-d格式的文件名
                'fileSize'    => 20,                                       // file类型：单位：M，单个日志文件大小限制，超过大小系统将自动备份
            ],
            '_log.file.formatter' => 'base',                               // 格式化器名称，自带base，支持自定义，自定义类型需实现FormatterInterface接口，传入完整名称如：Peas\Log\Formatter\BaseFormatter
            '_log.file.level' => ['emergency', 'alert', 'critical', 'error', 'info'], // 支持写入的日志等级


            /* 错误默认设置 */
            '_error.log.open' => true,                                     // 日志开关，是否记录到日志


            /* 未使用try...catch捕获的异常、异常printToLog默认设置 */
            '_exception.log.open' => true,                                 // 日志开关，是否记录到日志


            /* 404、500页面设置 */
            '_404.page' => dirname(dirname(dirname(__DIR__))) . '/page/404.tpl.php', // 404页面
            '_500.page' => dirname(dirname(dirname(__DIR__))) . '/page/500.tpl.php', // 500页面


            /* 模板引擎设置 */
            '_template.templateDir' => _PATH . '/resources/views',         // 模板文件目录
            '_template.compileDir'  => _PATH . '/storage/framework/views', // 编译文件目录
            '_template.cacheDir'    => _PATH . '/storage/framework/cache', // 缓存文件目录
        ];
    }
}
