<?php
namespace Peas\Cache\Store;

/**
 * Peas Framework
 *
 * Xcache缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class XCacheStore implements StoreInterface
{
    /**
     * @see StoreInterface::remove()
     */
    public function remove($id)
    {
        return xcache_unset($id);
    }

    /**
     * @see StoreInterface::clear()
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
     * @see StoreInterface::set()
     */
    public function set($id, $value, $lifetime)
    {
        return xcache_set($id, [$value, time()], $lifetime);
    }

    /**
     * @see StoreInterface::get()
     */
    public function get($id)
    {
        $tmp = xcache_get($id);
        return is_array($tmp) ? $tmp[0] : false;
    }

    /**
     * @see StoreInterface::test()
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
