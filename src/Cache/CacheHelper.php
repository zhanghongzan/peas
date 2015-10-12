<?php
namespace Peas\Cache;

/**
 * Peas Framework
 *
 * 缓存操作辅助类，全局静态方法
 *
 * 自定义存储器说明：<br>
 * 自定义存储器是指使用非peas自带存储器，需实现Peas\Cache\Store\StoreInterface接口，且确保能够自动加载<br>
 * 在传入参数时，自定义存储器名称需要为完整的带命名空间的类名，如：Peas\Cache\Store\ApcStore<br>
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class CacheHelper
{
    /**
     * 默认配置
     *
     * @var array
     */
    private static $_config = [
        'prefix'             => '',    // key前缀
        'defaultLifetime'    => 86400, // int -1表示永久有效
        'defaultStore'       => 'apc', // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称
        'defaultStoreConfig' => [],    // 默认存储器参数，没有可不传
    ];

    /**
     * 缓存管理类
     *
     * @var Cache
     */
    private static $_cache = null;


    /**
     * 初始化，设置缓存管理类
     *
     * @param array $config 配置参数，默认值：[<br>
     *     'prefix'             => '',    // key前缀<br>
     *     'defaultLifetime'    => 86400, // int -1表示永久有效<br>
     *     'defaultStore'       => 'apc', // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称<br>
     *     'defaultStoreConfig' => [],    // 默认存储器参数，没有可不传<br>
     * ]
     */
    public static function init(array $config = [])
    {
        self::$_config = array_merge(self::$_config, $config);
        self::$_cache  = new Cache(self::$_config);
    }

    /**
     * 删除缓存
     *
     * @param  string  $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public static function remove($id)
    {
        return self::$_cache->remove($id);
    }

    /**
     * 清空所有缓存记录
     *
     * @return boolean 成功返回true，失败返回false
     */
    public static function clear()
    {
        return self::$_cache->clear();
    }

    /**
     * 设置缓存
     *
     * @param  string  $id       缓存ID
     * @param  mixed   $value    缓存值
     * @param  int     $lifetime 缓存有效期（秒），0表示使用默认，-1表示永久有效
     * @return boolean 成功返回true，失败返回false
     */
    public static function set($id, $value, $lifetime = 0)
    {
        return self::$_cache->set($id, $value, $lifetime);
    }

    /**
     * 获取缓存
     *
     * @param  string      $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
     */
    public static function get($id)
    {
        return self::$_cache->get($id);
    }

    /**
     * 验证缓存有效性
     *
     * @param  string    $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
     */
    public static function test($id)
    {
        return self::$_cache->test($id);
    }
}
