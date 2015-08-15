<?php
namespace Peas\Log;

/**
 * Peas Framework
 *
 * 日志读写类，静态方法，可用于全局日志记录
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class LogCenter
{
    /**
     * 日志读写类
     *
     * @var Logger
     */
    private static $_logger = null;

    /**
     * 初始化日志读写类
     *
     * @param  array $config 详见Logger类参数说明
     * @return void
     */
    public static function init(array $config = [])
    {
        self::$_logger = new Logger($config);
    }


    /**
     * 系统无法使用
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function emergency($message, array $context = array())
    {
        self::$_logger->emergency($message, $context);
    }

    /**
     * 必须立刻处理的问题
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function alert($message, array $context = array())
    {
        self::$_logger->alert($message, $context);
    }

    /**
     * 严重错误
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function critical($message, array $context = array())
    {
        self::$_logger->critical($message, $context);
    }

    /**
     * 错误
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function error($message, array $context = array())
    {
        self::$_logger->error($message, $context);
    }

    /**
     * 警告
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function warning($message, array $context = array())
    {
        self::$_logger->warning($message, $context);
    }

    /**
     * 需要注意
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function notice($message, array $context = array())
    {
        self::$_logger->notice($message, $context);
    }

    /**
     * 信息记录
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function info($message, array $context = array())
    {
        self::$_logger->info($message, $context);
    }

    /**
     * 调试信息
     *
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function debug($message, array $context = array())
    {
        self::$_logger->debug($message, $context);
    }

    /**
     * 记录指定级别的日志
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public static function log($level, $message, array $context = array())
    {
        self::$_logger->log($level, $message, $context);
    }
}
