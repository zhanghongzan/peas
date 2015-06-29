<?php
namespace Peas\Log\Formater;

/**
 * Peas Framework
 *
 * 日志格式化类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class BaseFormatter implements FormatterInterface
{
    /**
     * 格式化一条日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return string 已格式化的日志信息
     */
    public function format(array $logInfo)
    {
        $replace = [];
        foreach ($logInfo['context'] as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }
        return date('Y-m-d H:i:s', $logInfo['datetime']) . '[' . $logInfo['level'] . ']: ' . strtr($logInfo['message'], $replace);
    }
}
