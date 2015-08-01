<?php
namespace Peas\Cache;

use Peas\Cache\Store\StoreInterface;

/**
 * Peas Framework
 *
 * 缓存操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Cache
{
    /**
     * 具体缓存管理类
     *
     * @var StoreInterface
     */
    private $_store = null;

    /**
     * key前缀
     *
     * @var string
     */
    public $prefix = '';

    /**
     * 默认缓存有效期：秒
     *
     * @var int -1表示永久有效
     */
    public $defaultLifetime = 86400;


    /**
     * 初始化，设置默认缓存管理类
     *
     * @param string $prefix          key前缀，默认为''
     * @param string $defaultLifetime 默认缓存有效期（秒），默认为86400
     * @param string $storeType       缓存类型，可以是apc,file,xCache，默认为apc
     * @param array  $storeConfig     需要传入的参数，没有可不传
     */
    public function __construct($prefix = '', $defaultLifetime = 86400, $storeType = 'apc', array $storeConfig = [])
    {
        $this->prefix = $prefix;
        $this->defaultLifetime = $defaultLifetime;
        $this->setStore($storeType, $config);
    }


    /**
     * 设置缓存管理类
     *
     * @param string $storeType   缓存类型，可以是apc,file,xCache
     * @param array  $storeConfig 需要传入的参数，没有可不传
     * @return void
     */
    public function setStore($storeType = 'apc', array $storeConfig = [])
    {
        $storeName = 'Peas\\Cache\\Store\\' . ucfirst($storeType);
        $this->_store = new $storeName($storeConfig);
    }

    /**
     * 删除缓存
     *
     * @param  string $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public function remove($id)
    {
        return $this->_store->remove($this->prefix . $id);
    }

    /**
     * 清空所有缓存记录
     *
     * @return boolean 成功返回true，失败返回false
     */
    public function clear()
    {
        return $this->_store->clear();
    }

    /**
     * 设置缓存
     *
     * @param  string $id       缓存ID
     * @param  mixed  $value    缓存值
     * @param  int    $lifetime 缓存有效期（秒），0表示使用默认，-1表示永久有效
     * @return boolean 成功返回true，失败返回false
     */
    public function set($id, $value, $lifetime = 0)
    {
        if ($lifetime == 0) {
            $lifetime = $this->defaultLifetime;
        } else if ($lifetime == -1) {
            $lifetime = 8640000000;
        }
        return $this->_store->set($this->prefix . $id, $value, $lifetime);
    }

    /**
     * 获取缓存
     *
     * @param  string $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
     */
    public function get($id)
    {
        return $this->_store->get($this->prefix . $id);
    }

    /**
     * 验证缓存有效性
     *
     * @param  string $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
     */
    public function test($id)
    {
        return $this->_store->test($this->prefix . $id);
    }
}
