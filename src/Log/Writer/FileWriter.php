<?php
namespace Peas\Log\Writer;

use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * 文件日志写入类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class FileWriter extends AbstractWriter
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_defaultConfig = [
        'dir'         => '', // Log文件目录
        'destination' => '', // 文件名，设置为空时将按时间生成Y-m-d格式的文件名
        'fileSize'    => 20, // 单位：M，单个日志文件大小限制，超过大小系统将自动备份
    ];

    /**
     * 需要记录的日志
     *
     * @var array
     */
    private $_logs = [];

    /**
     * 初始化
     * @param array $config [
     *     'dir'         => '',  // Log文件目录
     *     'destination' => '',  // 文件名，设置为空时将按时间生成Y-m-d格式的文件名
     *     'fileSize'    => 20,  // 单位：M，单个日志文件大小限制，超过大小系统将自动备份
     * ]
     */
    public function __construct(array $config)
    {
        if (!empty($config)) {
            $this->setConfig($config);
        }
        if (empty($this->getConfig('dir'))) {
            $this->setConfig('dir', dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/storage/logs');
        }
        if ($this->getConfig('fileSize') < 1) {
            $this->setConfig('fileSize', 1);
        }
    }

    /**
     * 记录日志信息
     *
     * @param  array $logInfo 日志信息数组
     * @return void
     */
    public function write(array $logInfo)
    {
        $this->_logs[] = $this->getFormatter()->format($logInfo);
    }

    /**
     * 在销毁时一次性写入数据
     */
    public function __destruct()
    {
        $destination = $this->getConfig('destination');
        $destination = $this->getConfig('dir') . '/' . (empty($destination) ? date('Y-m-d') . '.log' : $destination);
        if (is_file($destination) && floor($this->getConfig('fileSize') * 1048576 <= filesize($destination))) {
            rename($destination, $destination . '.bak[' . time() . ']');
        }
        error_log(implode("\r\n", $this->_logs), 3, $destination);
    }
}
