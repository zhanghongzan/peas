<?php
namespace Peas\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Peas Framework
 *
 * 日志读写类
 *
 * $config 配置示例：[<br>
 *     'loggers' => 'default', // 启用的日志写入器，多个用','分开<br>
 *     'default' => [          // 日志写入器配置，键名为写入器名称<br>
 *         'writer' => 'syslog',<br>
 *         'level'  => ['emergency', 'alert', 'critical', 'error', 'info'],<br>
 *     ],<br>
 * ]<br>
 *
 * 写入器配置说明：<br>
 * 'name' => [                    // name为自定义写入器名称<br>
 *     'writer'       => 'file',  // 指定写入器类型，自带file、syslog，支持自定义，但是自定义写入器需实现接口Peas\Log\Writer\WriterInterface，类命名为Xxx或者XxxWriter（xxx即为写入器类型）<br>
 *     'writerConfig' => [        // 传入写入器构造函数的参数，自带syslog无需此参数，file需要以下示例参数<br>
 *         'dir'         => '',   // file类型：Log文件目录<br>
 *         'destination' => '',   // file类型：文件名，设置为空时将按时间生成Y-m-d格式的文件名<br>
 *         'fileSize'    => 20,   // file类型：单位：M，单个日志文件大小限制，超过大小系统将自动备份<br>
 *     ],<br>
 *     'formatter'    => 'base',  // 格式化器名称，自带base，支持自定义，自定义类型需实现FormatterInterface接口，类命名为Xxx或者XxxFormatter（xxx即为格式化器类型）<br>
 *     'level'        => ['emergency', 'alert', 'critical', 'error', 'info'], // 支持写入的日志等级<br>
 * ]
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Logger extends AbstractLogger
{
    /**
     * 日志默认配置
     *
     * @var array
     */
    private $_config = [
        'loggers' => '', // 启用的日志写入器，多个用','分开
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
     * 日志写入实体类数组
     *
     * @var array ['name' => WriterInterface, ...]
     */
    private $_writers = [];

    /**
     * 等级控制数组
     *
     * @var array ['name' => [], ...]
     */
    private $_levels = [];


    /**
     * 初始化
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->_config = array_merge($this->_config, $config);
        $writerNames = explode(',', $this->_config['loggers']);
        foreach ($writerNames as $item) {
            $item = trim($item);
            $this->addWriter($item, $this->_config[$item]);
        }
    }

    /**
     * 添加日志写入器
     *
     * @param  string $writerName 写入器名称
     * @param  array  $config     写入器配置
     * @return Logger
     */
    public function addWriter($writerName, array $config)
    {
        $writerClassName = $this->_getClassName($config['writer']);
        $writer = isset($config['writerConfig']) ? new $writerClassName($config['writerConfig']) : new $writerClassName();

        $formatterClassName = isset($config['formatter']) ? $this->_getClassName($config['formatter']) : 'BaseFormatter';
        if ($formatterClassName) {
            $writer->setFormatter(new $formatterClassName());
        }
        $this->_levels[$writerName]  = isset($config['level']) ? $config['level'] : ['emergency', 'alert', 'critical', 'error', 'info'];
        $this->_writers[$writerName] = $writer;
        return $this;
    }

    /**
     * 检查类是否存在
     *
     * @param  string       $className    类名
     * @param  string       $classAddName 类名后缀
     * @return string|false 存在则返回存在的类名，不存在则返回false
     */
    private function _getClassName($className, $classAddName)
    {
        $className = ucfirst($className);
        if (!class_exists($className)) {
            $className .= $classAddName;
            if (!class_exists($className)) {
                return false;
            }
        }
        return $className;
    }

    /**
     * 删除指定写入器
     *
     * @param  string $writerName 写入器名称
     * @return Logger
     */
    public function removeWriter($writerName)
    {
        unset($this->_writers[$writerName]);
        unset($this->_levels[$writerName]);
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
    public function log($level, $message, array $context = [])
    {
        if (empty($this->_writers)) {
            return;
        }
        $logInfo = [
            'datetime'  => time(),
            'level'     => $level,
            'levelCode' => self::$_levelCode[$level],
            'message'   => $message,
            'context'   => $context,
        ];
        foreach ($this->_writers as $writerName => $writerItem) {
            if (in_array($level, $this->_levels[$writerName])) {
                $writerItem->write($logInfo);
            }
        }
    }
}
