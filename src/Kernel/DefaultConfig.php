<?php
namespace Peas\Kernel;

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
            '_default.timezone' => 'PRC',                // 默认时区
            '_default.static'   => _ROOT,                // 静态文件host(结尾不含/)，为空表示默认为_ROOT，该值可以通过常量_STATIC访问


            /* 配置文件设置  */
            '_config.app'   => '',                       // 应用配置文件，多个可用数组
            '_config.route' => '',                       // 路由配置文件


            /* URL默认设置  */
            '_url.mode'      => 2,                       // 0:普通模式，1:pathinfo模式，2:rewrite普通模式 ，3:rewrite伪静态模式
            '_url.suffix'    => 'html',                  // rewrite伪静态模式后缀
            '_url.separator' => '/',                     // URL默认分隔符


            /* session默认设置  */
            '_session.autoStart' => false,               // 是否自动开启Session
            '_session.prefix'    => 'pf_',               // session名称前缀
            '_session.config'    => [],                  // 配置参数，参数名=>参数值，参数名为php默认配置名，这里的设置将覆盖默认设置，如：['session.gc_maxlifetime' => 1440, ...]


            /* cookie默认设置  */
            '_cookie.expire'   => 3600,                  // cookie默认有效期
            '_cookie.prefix'   => 'pf_',                 // cookie名称前缀
            '_cookie.path'     => '/',                   // cookie路径
            '_cookie.domain'   => '',                    // cookie域名
            '_cookie.httpOnly' => false,                 // 是否使用HttpOnly


            /* 缓存默认设置，CacheCenter */
            '_cache.prefix'             => '',           // key前缀
            '_cache.defaultLifetime'    => 86400,        // int -1表示永久有效
            '_cache.defaultStore'       => 'file',       // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称
            '_cache.defaultStoreConfig' => [             // 默认存储器参数，没有可不传
                'directory' => _PATH . '/storage/app/cache',
            ],


            /* 日志默认设置 ，LogCenter */
            '_log.loggers'     => 'file',                 // 启用的日志写入器，多个用','分开
            '_log.file.writer' => 'file',                 // 指定写入器类型，自带file、syslog，支持自定义，但是自定义写入器需实现接口Peas\Log\Writer\WriterInterface，传入完整名称如：Peas\Log\Writer\FlieWriter
            '_log.file.writerConfig' => [                 // 传入写入器构造函数的参数，自带syslog无需此参数，file需要以下示例参数
                'dir'         => _PATH . '/storage/logs', // file类型：Log文件目录
                'destination' => '',                      // file类型：文件名，设置为空时将按时间生成Y-m-d格式的文件名
                'fileSize'    => 20,                      // file类型：单位：M，单个日志文件大小限制，超过大小系统将自动备份
            ],
            '_log.file.formatter' => 'base',              // 格式化器名称，自带base，支持自定义，自定义类型需实现FormatterInterface接口，传入完整名称如：Peas\Log\Formatter\BaseFormatter
            '_log.file.level' => ['emergency', 'alert', 'critical', 'error', 'info'], // 支持写入的日志等级


            /* 未使用try...catch捕获的异常、异常printToLog默认设置 */
            '_exception.log.open'    => true,             // 日志开关，是否记录到日志
            '_exception.log.loggers' => '',               // 指定日志写入器类型，为空表示使用默认LogCenter配置


            /* 错误默认设置 */
            '_error.log.open'    => true,                 // 日志开关，是否记录到日志
            '_error.log.loggers' => '',                   // 指定日志写入器类型，为空表示使用默认LogCenter配置
            '_error.callback'    => 'Peas\Kernel\Application::errorHandlerCallback',   // 回调函数


            /* 404、500页面设置 */
            '_404.function' => '',                        // 404时执行的自定义方法，在载入_404.page页面之前执行
            '_404.page'     => '',                        // 404页面，为空表示默认不处理
            '_500.function' => '',                        // 500时执行的自定义方法，在载入_500.page页面之前执行
            '_500.page'     => '',                        // 500页面，为空表示默认不处理
        ];
    }
}
