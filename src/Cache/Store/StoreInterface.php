<?php
namespace Peas\Cache\Store;

/**
 * Peas Framework
 *
 * 缓存管理类接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
Interface StoreInterface
{
    /**
     * 删除缓存
     *
     * @param  string $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public function remove($id);

    /**
     * 清空所有缓存记录
     *
     * @return boolean 成功返回true，失败返回false
    */
    public function clear();

    /**
     * 设置缓存
     *
     * @param  string $id 缓存ID
     * @param  mixed  $value 缓存值
     * @param  int    $specificLifetime 缓存有效期（秒），false时表示使用默认
     * @return boolean 成功返回true，失败返回false
    */
    public function set($id, $value, $specificLifetime = false);

    /**
     * 获取缓存
     *
     * @param  string $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
    */
    public function get($id);

    /**
     * 验证缓存有效性
     *
     * @param  string $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
    */
    public function test($id);
}
