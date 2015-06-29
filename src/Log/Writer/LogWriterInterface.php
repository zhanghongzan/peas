<?php
namespace Peas\Log\Writer;

use Peas\Log\Formatter\LogFormatterInterface;

/**
 * Peas Framework
 *
 * 日志写入类接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
interface LogWriterInterface
{
    /**
     * 设置格式化类
     *
     * @param LogFormatterInterface $formatter
     * @return LogWriterInterface
     */
    public function setFormatter(LogFormatterInterface $formatter);

    /**
     * 设置格式化类
     *
     * @return LogFormatterInterface
     */
    public function getFormatter();

    /**
     * 记录日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return void
     */
    public function write(array $logInfo);
}
