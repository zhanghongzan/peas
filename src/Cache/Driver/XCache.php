<?php
namespace Peas\Cache\Driver;

use Peas\Cache\CacheInterface;

/**
 * Peas Framework
 *
 * Xcache缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class XCache implements CacheInterface
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
        if (!function_exists('xcache_info')) {
            return false;
        }
        if (isset($config['defaultLifetime'])) {
            $this->defaultLifetime = $config['defaultLifetime'];
        }
        return true;
    }


    /**
     * @see CacheInterface::remove()
     */
    public function remove($id)
    {
        return xcache_unset($id);
    }

    /**
     * @see CacheInterface::clear()
     */
    public function clear()
    {
        $cnt = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $cnt; $i ++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        return true;
    }

    /**
     * @see CacheInterface::set()
     */
    public function set($id, $value, $specificLifetime = false)
    {
        $lifetime = $specificLifetime === false ? $this->defaultLifetime : $specificLifetime;
        return xcache_set($id, array($value, time()), $lifetime);
    }

    /**
     * @see CacheInterface::get()
     */
    public function get($id)
    {
        $tmp = xcache_get($id);
        return is_array($tmp) ? $tmp[0] : false;
    }

    /**
     * @see CacheInterface::test()
     */
    public function test($id)
    {
        if (xcache_isset($id)) {
            $tmp = xcache_get($id);
            return is_array($tmp) ? $tmp[1] : false;
        }
        return false;
    }
}