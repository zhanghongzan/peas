<?php
namespace Peas\Log\Formatter;

/**
 * Peas Framework
 *
 * 日志格式化类接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
interface LogFormatterInterface
{
    /**
     * 格式化一条日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return mixed 已格式化的日志信息
     */
    public function format(array $logInfo);
}
