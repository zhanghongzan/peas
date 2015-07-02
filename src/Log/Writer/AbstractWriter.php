<?php
namespace Peas\Log\Writer;

use Peas\Log\Formatter\BaseFormatter;
use Peas\Log\Formatter\FormatterInterface;

/**
 * Peas Framework
 *
 * 日志写入类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * 格式化类
     *
     * @var FormatterInterface
     */
    protected $_formatter;


    /**
     * 设置格式化类
     *
     * @param FormatterInterface $formatter
     * @return WriterInterface
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->_formatter = $formatter;
        return $this;
    }

    /**
     * 设置格式化类
     *
     * @return FormatterInterface
    */
    public function getFormatter()
    {
        if (!$this->_formatter) {
            $this->_formatter = $this->_getDefaultFormatter();
        }
        return $this->_formatter;
    }

    /**
     * 获取默认格式化类
     *
     * @return BaseFormatter
     */
    protected function _getDefaultFormatter()
    {
        return new BaseFormatter();
    }

    /**
     * 记录日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return void
     */
    abstract public function write(array $logInfo);
}
