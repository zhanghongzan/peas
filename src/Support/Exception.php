<?php
/**
 * Peas Framework
 *
 * 异常处理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */

class PeasException extends \Exception
{
    /**
     * 存储未捕获到的异常
     *
     * @var array
     */
    public static $_exceptions = array();


    /**
     * 调用父类的构造方法
     *
     * @param  string $message 自定义的异常信息
     * @param  int $code 异常代码
     * @return void
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
     * 格式化异常信息
     *
     * @param  Exception $e 异常
     * @return string 格式化的异常信息
     */
    public static function toString(Exception $e)
    {
        $infoArr = self::getExceptionInfo($e);
        $infoStr = $infoArr['time'] . ' ' . $infoArr['name'] . '['.$infoArr['code'].']:' . $infoArr['message'];
        $infoStr.= '[in ' . $infoArr['file'] . ':' . $infoArr['line'] . '](Trace:' . $infoArr['traceString'] . ')';
        return $infoStr;
    }

    /**
     * 获取数组形式的异常信息
     *
     * @param  Exception $e 异常
     * @return array 异常信息数组
     */
    public static function getExceptionInfo(Exception $e)
    {
        $eInfo = array();
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
     * 自定义捕捉异常方法，用于捕捉未使用try...catch捕获的异常
     *
     * @param  Exception $e
     * @return void
     */
    public static function _handler(Exception $e)
    {
        array_push(self::$_exceptions, $e);
        self::printToLog($e);
    }
}