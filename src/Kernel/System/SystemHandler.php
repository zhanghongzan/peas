<?php
namespace Peas\Kernel\System;

use Peas\Config\Configure;
use Peas\Log\LogCenter;
use Peas\Log\Logger;
use Peas\Support\Exception;

/**
 * Peas Framework
 *
 * 错误处理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class SystemHandler
{
    /**
     * 捕获到的错误信息
     *
     * @var array
     */
    public static $errors = [];

    /**
     * 存储未捕获到的异常
     *
     * @var array
     */
    public static $exceptions = [];


    /**
     * 错误日志写入类，设置了之后，可以将错误写入日志
     *
     * @var Psr\Log\LoggerInterface
     */
    private static $_errorLogger = null;

    /**
     * 错误回调函数，在捕获到错误时调用，依次传入$errno, $errstr, $errfile, $errline四个参数
     *
     * @var string
     */
    private static $_errorCallback = null;


    /**
     * 初始化异常捕获、错误捕获
     *
     * @return void
     */
    public static function init()
    {
        if (Configure::get('_error.log.open')) {
            self::$_errorLogger = empty(Configure::get('_error.log.loggers')) ? LogCenter::getLogger() : new Logger(Configure::get('_error.log'));
        }
        if (Configure::get('_error.callback')) {
            self::$_errorCallback = Configure::get('_error.callback');
        }
        if (Configure::get('_exception.log.open')) {
            $exceptionLog = empty(Configure::get('_exception.log.loggers')) ? LogCenter::getLogger() : new Logger(Configure::get('_exception.log'));
            Exception::setLogger($exceptionLog);
        }
    }


    /**
     * 自定义捕捉异常方法，可用于捕捉未使用try...catch捕获的异常
     *
     * @param  \Exception $e
     * @return void
     */
    public static function exceptionHandler(\Exception $e)
    {
        array_push(self::$exceptions, $e);
        Exception::printToLog($e);
        Application::to500(Exception::toString($e));
    }


    /**
     * 自定义错误处理，用于捕获错误
     *
     * @param  string $errno   错误报告级别
     * @param  string $errstr  出错信息
     * @param  string $errfile 出错所在文件
     * @param  int    $errline 出错所在行
     * @return void
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $errInfo = date("Y-m-d H:i:m") . ' Error['.$errno.']:' . $errstr . '[in ' . $errfile . ':' . $errline . ']';
        array_push(self::$errors, $errInfo);

        if (self::$_errorLogger) {
            self::$_errorLogger->error($errInfo);
        }
        if (self::$_errorCallback) {
            self::$_errorCallback($errno, $errstr, $errfile, $errline, $errInfo);
        } else if ($errno == E_ERROR || $errno == E_USER_ERROR) {
            Application::to500($errInfo);
        }
    }
}
