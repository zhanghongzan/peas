<?php
namespace Peas\Log;

use Peas\Support\Traits\ConfigTrait;
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
     * 日志写入实体类
     *
     * @var unknown
     */
    private $_writers = [];

    private $_levelCode = [
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
     * 存储的需要写入的日志信息
     *
     * @var array
     */
    public $storage = [];


    /**
     * 记录日志信息
     *
     * @param  mixed $level
     * @param  string $message
     * @param  array $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        // noop
    }

    public function setWriter()
    {}
}
