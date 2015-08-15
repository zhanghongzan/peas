<?php
namespace Peas\Cache;

use Peas\Cache\Store\StoreInterface;
use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * 缓存操作类
 *
 * 自定义存储器说明：<br>
 * 自定义存储器是指使用非peas自带存储器，需实现Peas\Cache\Store\StoreInterface接口，且确保能够自动加载<br>
 * 在传入参数时，自定义存储器名称需要为完整的带命名空间的类名，如：Peas\Cache\Store\ApcStore<br>
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Cache
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_config = [
        'prefix'             => '',    // key前缀
        'defaultLifetime'    => 86400, // int -1表示永久有效
        'defaultStore'       => 'apc', // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称
        'defaultStoreConfig' => [],    // 默认存储器参数，没有可不传
    ];

    /**
     * 具体缓存管理类
     *
     * @var StoreInterface
     */
    private $_store = null;


    /**
     * 初始化，设置默认缓存管理类
     *
     * @param array $config 配置参数，默认值：[<br>
     *     'prefix'             => '',    // key前缀<br>
     *     'defaultLifetime'    => 86400, // int -1表示永久有效<br>
     *     'defaultStore'       => 'apc', // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称<br>
     *     'defaultStoreConfig' => [],    // 默认存储器参数，没有可不传<br>
     * ]
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->setStore($this->getConfig('defaultStore'), $this->getConfig('defaultStoreConfig'));
    }


    /**
     * 设置缓存管理类
     *
     * @param  string $storeName   缓存类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称
     * @param  array  $storeConfig 需要传入的参数，没有可不传
     * @return void
     */
    public function setStore($storeName = 'apc', array $storeConfig = [])
    {
        if (!class_exists($storeName)) {
            $storeName = 'Peas\Cache\Store\\' . ucfirst($storeName) . 'Store';
        }
        $this->_store = new $storeName($storeConfig);
    }

    /**
     * 删除缓存
     *
     * @param  string  $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public function remove($id)
    {
        return $this->_store->remove($this->getConfig('prefix') . $id);
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
     * @param  string  $id       缓存ID
     * @param  mixed   $value    缓存值
     * @param  int     $lifetime 缓存有效期（秒），0表示使用默认，-1表示永久有效
     * @return boolean 成功返回true，失败返回false
     */
    public function set($id, $value, $lifetime = 0)
    {
        if ($lifetime == 0) {
            $lifetime = $this->getConfig('defaultLifetime');
        } elseif ($lifetime == -1) {
            $lifetime = 8640000000;
        }
        return $this->_store->set($this->getConfig('prefix') . $id, $value, $lifetime);
    }

    /**
     * 获取缓存
     *
     * @param  string      $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
     */
    public function get($id)
    {
        return $this->_store->get($this->getConfig('prefix') . $id);
    }

    /**
     * 验证缓存有效性
     *
     * @param  string    $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
     */
    public function test($id)
    {
        return $this->_store->test($this->getConfig('prefix') . $id);
    }
}
