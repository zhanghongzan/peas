<?php
namespace Peas\Cache\Store;

/**
 * Peas Framework
 *
 * Apc缓存管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class ApcStore implements StoreInterface
{
    /**
     * @see StoreInterface::clear()
     */
    public function clear()
    {
        return apc_clear_cache('user');
    }

    /**
     * @see StoreInterface::get()
     */
    public function get($id)
    {
        $tmp = apc_fetch($id);
        return is_array($tmp) ? $tmp[0] : false;
    }

    /**
     * @see StoreInterface::remove()
     */
    public function remove($id)
    {
        return apc_delete($id);
    }

    /**
     * @see StoreInterface::set()
     */
    public function set($id, $value, $lifetime)
    {
        return apc_store($id, [$value, time()], $lifetime);
    }

    /**
     * @see StoreInterface::test()
     */
    public function test($id)
    {
        $tmp = apc_fetch($id);
        return is_array($tmp) ? $tmp[1] : false;
    }
}
