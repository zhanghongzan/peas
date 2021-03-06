<?php
namespace Peas\Support;

/**
 * Peas Framework
 *
 * 异常处理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Exception extends \Exception
{
    /**
     * 日志写入类，设置了之后，调用printToLog方法、printTraceToLog方法可以将异常写入日志
     *
     * @var Psr\Log\LoggerInterface
     */
    private static $_logger = null;


    /**
     * 调用父类的构造方法
     *
     * @param  string $message 自定义的异常信息
     * @param  int    $code 异常代码
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * 格式化
     *
     * @return string
     */
    public function __toString()
    {
        return self::toString($this);
    }

    /**
     * 输出信息到日志
     *
     * @return void
     */
    public function printTraceToLog()
    {
        self::printToLog($this);
    }


    /**
     * 格式化异常信息
     *
     * @param  \Exception $e 异常
     * @return string 格式化的异常信息
     */
    public static function toString(\Exception $e)
    {
        $infoArr = self::getExceptionInfo($e);
        $infoStr = $infoArr['time'] . ' ' . $infoArr['name'] . '['.$infoArr['code'].']:' . $infoArr['message'];
        $infoStr.= '[in ' . $infoArr['file'] . ':' . $infoArr['line'] . '](Trace:' . $infoArr['traceString'] . ')';
        return $infoStr;
    }

    /**
     * 获取数组形式的异常信息
     *
     * @param  \Exception $e 异常
     * @return array 异常信息数组
     */
    public static function getExceptionInfo(\Exception $e)
    {
        $eInfo = [];
        $eInfo['time']        = date("Y-m-d H:i:m");
        $eInfo['name']        = get_class($e);
        $eInfo['message']     = $e->getMessage();
        $eInfo['file']        = $e->getFile();
        $eInfo['code']        = $e->getCode();
        $eInfo['line']        = $e->getLine();
        $eInfo['traceString'] = $e->getTraceAsString();
        $eInfo['trace']       = $e->getTrace();
        return $eInfo;
    }

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
     * 输出异常信息到日志
     *
     * @param  \Exception $e 异常
     * @return void
     */
    public static function printToLog(\Exception $e)
    {
        if (self::$_logger) {
            self::$_logger->error(self::toString($e));
        }
    }
}
