<?php
namespace Peas\Log;

use Peas\Support\Traits\ConfigTrait;
use Peas\Log\Writer\WriterInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Peas Framework
 *
 * 日志读写类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Logger extends AbstractLogger
{
    use ConfigTrait;

    /**
     * 日志配置
     * [
     *     'record'      => true,                                                // 日志保存开关，是否保存日志
     *     'recordLevel' => ['emergency', 'alert', 'critical', 'error', 'info'], // 允许记录的日志级别，level名称详见Psr\Log\LogLevel
     * ]
     *
     * @var array
     */
    private $_defaultConfig = [
        'record'      => true,
        'recordLevel' => ['emergency', 'alert', 'critical', 'error', 'info'],
    ];

    /**
     * 等级名称和数字代码映射关系
     *
     * @var array
     */
    private static $_levelCode = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG,
    ];

    /**
     * 日志写入实体类
     *
     * @var WriterInterface
     */
    private $_writer = null;


    /**
     * 初始化
     *
     * @param WriterInterface $writer
     */
    public function __construct(WriterInterface $writer = null)
    {
        if (!is_null($writer)) {
            $this->setWriter($writer);
        }
    }

    /**
     * 设置格式化类
     *
     * @param  WriterInterface $writer
     * @return Logger
     */
    public function setWriter(WriterInterface $writer)
    {
        $this->_writer = $writer;
        return $this;
    }

    /**
     * 记录日志信息
     *
     * @param  mixed  $level
     * @param  string $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->getConfig('record') || !in_array($level, $this->getConfig('recordLevel')) || is_null($this->_writer)) {
            return;
        }
        $logInfo = [
            'datetime' => time(),
            'level' => $level,
            'levelCode' => self::$_levelCode[$level],
            'message' => $message,
            'context' => $context,
        ];
        $this->_writer->write($logInfo);
    }
}
