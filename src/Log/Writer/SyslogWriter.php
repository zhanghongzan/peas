<?php
namespace Peas\Log\Writer;

/**
 * Peas Framework
 *
 * 系统日志写入类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class SyslogWriter extends AbstractWriter
{
    /**
     * 记录日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return void
     */
    public function write(array $logInfo)
    {
        $message = $this->getFormatter()->format($logInfo);
        syslog($logInfo['levelCode'], $message);
    }
}
