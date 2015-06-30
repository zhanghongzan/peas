<?php
namespace Peas\Support;

/**
 * Peas Framework
 *
 * 错误处理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class ErrorHandler
{
    /**
     * 捕获到的错误信息
     *
     * @var array
     */
    public static $errors = array();


    /**
     * 日志写入类，设置了之后，未捕获的异常、调用printToLog方法、printTraceToLog方法可以将异常写入日志
     *
     * @var Psr\Log\LoggerInterface
     */
    private static $_logger = null;

    /**
     *
     * @var 回调函数，在捕获到错误时调用，依次传入$errno, $errstr, $errfile, $errline四个参数
     */
    private static $_callback = null;


    /**
     * 设置日志写入类，设置了之后，未捕获的异常、调用printToLog方法、printTraceToLog方法可以将异常写入日志
     *
     * @param  Psr\Log\LoggerInterface $logger 日志写入类，需要实现Psr\Log\LoggerInterface接口
     * @return void
     */
    public static function setLogger($logger)
    {
        self::$_logger = $logger;
    }

    /**
     * 设置回调函数，在捕获到错误时调用，依次传入$errno, $errstr, $errfile, $errline四个参数
     *
     * @param function $callback
     */
    public static function setCallback($callback)
    {
        self::$_callback = $callback;
    }

    /**
     * 自定义错误处理，用于捕获异常
     *
     * @param  string $errno   错误报告级别
     * @param  string $errstr  出错信息
     * @param  string $errfile 出错所在文件
     * @param  int    $errline 出错所在行
     * @return void
    */
    public static function handler($errno, $errstr, $errfile, $errline)
    {
        $errInfo = date("Y-m-d H:i:m") . ' Error['.$errno.']:' . $errstr . '[in ' . $errfile . ':' . $errline . ']';
        array_push(self::$errors, $errInfo);

        if (self::$_logger) {
            self::$_logger->error($errInfo);
        }
        if (self::$_callback) {
            self::$_callback($errno, $errstr, $errfile, $errline);
        }
    }
}
