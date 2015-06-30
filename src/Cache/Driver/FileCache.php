<?php
namespace Peas\Cache\Driver;

use Peas\Cache\CacheInterface;

/**
 * Peas Framework
 *
 * File类型缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class FileCache implements CacheInterface
{
    /**
     * 缓存有效期：秒
     *
     * @var int -1表示永久有效
     */
    public $defaultLifetime = -1;

    /**
     * 文件缓存存放目录
     *
     * @var string
     */
    public $fileDir = '';

    /**
     * 是否压缩缓存
     *
     * @var boolean
     */
    public $compress = false;


    /**
     * 初始化
     *
     * @param  array $config 配置参数，键名为public属性名，设置对应属性的值
     * @return boolean
     */
    public function init(array $config = [])
    {
        foreach ($config as $key => $val) {
        	$this->{$key} = $val;
        }
        if (empty($this->fileDir)) {
            $this->fileDir = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/storage/framework/cache';
        }
        return true;
    }


    /**
     * @see CacheInterface::remove()
     */
    public function remove($id)
    {
        return unlink($this->_getFilePath($id));
    }

    /**
     * @see CacheInterface::clear()
     */
    public function clear()
    {
        $fileDir = $this->fileDir;
        $dir = opendir($fileDir);
        if ($dir) {
            $file = readdir($dir);
            while ($file) {
                if (pathinfo($file, PATHINFO_EXTENSION) == 'cache' && is_file($fileDir . '/' . $file)) {
                    unlink($fileDir . '/' . $file);
                }
                $file = readdir($dir);
            }
            closedir($dir);
            return true;
        }
        return false;
    }

    /**
     * @see CacheInterface::set()
     */
    public function set($id, $value, $specificLifetime = false)
    {
        $lifetime  = $specificLifetime === false ? $this->defaultLifetime : $specificLifetime;
        $validTime = $lifetime == -1 ? -1 : time() + $lifetime;

        $data = array('valid' => $validTime, 'data' => $value);
        $writeData = $this->compress && function_exists('gzcompress') ? gzcompress(serialize($data), 3) : serialize($data);

        if (file_put_contents($this->_getFilePath($id), $writeData)) {
            clearstatcache();
            return true;
        }
        return false;
    }

    /**
     * @see CacheInterface::get()
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
     * @see CacheInterface::test()
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
        $data = unserialize($this->compress && function_exists('gzuncompress') ? gzuncompress($content) : $content);
        if ($data['valid'] != -1 && time() > $data['valid']) {
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
        return $this->fileDir . '/' . md5($id) . '.cache';
    }
}
