<?php
namespace Peas\Log\Writer;

use Peas\Log\Formater\FormatterInterface;

/**
 * Peas Framework
 *
 * 日志写入类接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
interface WriterInterface
{
    /**
     * 设置格式化类
     *
     * @param FormatterInterface $formatter
     * @return WriterInterface
     */
    public function setFormatter(FormatterInterface $formatter);

    /**
     * 设置格式化类
     *
     * @return FormatterInterface
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
