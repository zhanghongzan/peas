<?php
namespace Peas\Cache\Store;

use FilesystemIterator;
use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * File类型缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class FileStore implements StoreInterface
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_defaultConfig = [
        'directory' => '', // 文件缓存存放目录
    ];


    /**
     * 构造函数，初始化
     *
     * @param array $config 配置参数，默认值：[<br>
     *     'directory' => '', // 文件缓存存放目录<br>
     * ]
     */
    public function __construct($config)
    {
        $config = array_merge($this->_defaultConfig, $config);
        $this->setConfig($config);
    }


    /**
     * @see StoreInterface::remove()
     */
    public function remove($id)
    {
        return unlink($this->_getFilePath($id));
    }

    /**
     * @see StoreInterface::clear()
     */
    public function clear()
    {
        $items = new FilesystemIterator($this->getConfig('directory'));
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->_deleteDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        return true;
    }

    /**
     * @see StoreInterface::set()
     */
    public function set($id, $value, $lifetime)
    {
        $validTime = time() + $lifetime;
        $data = ['valid' => $validTime, 'data' => $value];
        $writeData = function_exists('gzcompress') ? gzcompress(serialize($data), 3) : serialize($data);

        $filePath = $this->_getFilePath($id);
        $fileDir  = dirname($filePath);
        if (!is_dir($fileDir)) {
            @mkdir($fileDir, 0777, true);
        }

        if (file_put_contents($filePath, $writeData)) {
            clearstatcache();
            return true;
        }
        return false;
    }

    /**
     * @see StoreInterface::get()
     */
    public function get($id)
    {
        $data = $this->_getFileData($this->_getFilePath($id));
        if ($data === false) {
            return false;
        }
        return $data['data'];
    }

    /**
     * @see StoreInterface::test()
     */
    public function test($id)
    {
        $filePath = $this->_getFilePath($id);
        if ($this->_getFileData($filePath) === false) {
            return false;
        }
        return filemtime($filePath);
    }


    /**
     * 检查获取缓存数据
     *
     * @param  string $filePath
     * @return mixed|false
     */
    private function _getFileData($filePath)
    {
        if (!is_file($filePath)) {
            return false;
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }
        $data = unserialize(function_exists('gzuncompress') ? gzuncompress($content) : $content);
        if (time() > $data['valid']) {
            unlink($filePath); // 过期删除
            return false;
        }
        return $data;
    }

    /**
     * 获取缓存文件地址
     *
     * @param  string $id 缓存ID
     * @return string 缓存文件地址
     */
    private function _getFilePath($id)
    {
        $key = md5($id);
        $dir = implode('/', array_slice(str_split($key, 2), 0, 2));
        return $this->getConfig('directory') . '/' . $dir . '/' . $key . '.cache';
    }

    /**
     * 删除目录
     *
     * @param  string  $directory
     * @return boolean 成功返回true，失败返回false
     */
    private function _deleteDirectory($directory)
    {
        if (!is_dir($directory)) {
            return false;
        }
        $items = new FilesystemIterator($directory);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->_deleteDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($directory);
        return true;
    }
}
