<?php
namespace Peas\Cache\Driver;

use Peas\Cache\CacheInterface;

/**
 * Peas Framework
 *
 * Apc缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class ApcCache implements CacheInterface
{
    /**
     * 默认缓存有效期：秒
     *
     * @var int -1表示永久有效
     */
    public $defaultLifetime = -1;


    /**
     * 初始化
     *
     * @param  array $config 配置参数，键名为public属性名，设置对应属性的值
     * @return boolean
     */
    public function init(array $config = [])
    {
        if (!function_exists('apc_cache_info')) {
            return false;
        }
        if (isset($config['defaultLifetime'])) {
            $this->defaultLifetime = $config['defaultLifetime'];
        }
        return true;
    }

    /**
     * @see CacheInterface::clear()
     */
    public function clear()
    {
        return apc_clear_cache();
    }

    /**
     * @see CacheInterface::get()
     */
    public function get($id)
    {
        $tmp = apc_fetch($id);
        return is_array($tmp) ? $tmp[0] : false;
    }

    /**
     * @see CacheInterface::remove()
     */
    public function remove($id)
    {
        return apc_delete($id);
    }

    /**
     * @see CacheInterface::set()
     */
    public function set($id, $value, $specificLifetime = false)
    {
        $lifetime = $specificLifetime === false ? $this->defaultLifetime : $specificLifetime;
        return apc_store($id, array($value, time()), $lifetime);
    }

    /**
     * @see CacheInterface::test()
     */
    public function test($id)
    {
        $tmp = apc_fetch($id);
        return is_array($tmp) ? $tmp[1] : false;
    }
}
