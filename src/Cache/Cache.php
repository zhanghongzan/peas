<?php
namespace Peas\Cache;

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
     * @var array
     */
    private $_drivers = [];


    /**
     * 初始化，设置默认缓存管理类
     *
     * @param CacheInterface $defaultDriver
     */
    public function __construct(CacheInterface $defaultDriver = null)
    {
    	if ($defaultDriver) {
    	    $this->setDriver('default', $defaultDriver);
    	}
    }


    /**
     * 设置缓存管理类
     *
     * @param  string $name 名称，default为默认缓存管理类名称
     * @param  CacheInterface $driver
     * @return void
     */
    public function setDriver($name, CacheInterface $driver)
    {
    	$this->_drivers[$name] = $driver;
    }

    /**
     * 获取缓存管理类
     *
     * @param  string $name，default为默认缓存管理类名称
     * @return CacheInterface $driver
     */
    public function getDriver($name)
    {
    	return isset($this->_drivers[$name]) ? $this->_drivers[$name] : null;
    }


    /**
     * 删除缓存
     *
     * @param  string $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public function remove($id)
    {
    	return $this->getDriver('default')->remove($id);
    }

    /**
     * 清空所有缓存记录
     *
     * @return boolean 成功返回true，失败返回false
    */
    public function clear()
    {
        return $this->getDriver('default')->clear();
    }

    /**
     * 设置缓存
     *
     * @param  string $id 缓存ID
     * @param  mixed  $value 缓存值
     * @param  int    $specificLifetime 缓存有效期（秒），false时表示使用默认
     * @return boolean 成功返回true，失败返回false
    */
    public function set($id, $value, $specificLifetime = false)
    {
        return $this->getDriver('default')->set($id, $value, $specificLifetime);
    }

    /**
     * 获取缓存
     *
     * @param  string $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
    */
    public function get($id)
    {
    	return $this->getDriver('default')->get($id);
    }

    /**
     * 验证缓存有效性
     *
     * @param  string $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
    */
    public function test($id)
    {
    	return $this->getDriver('default')->test($id);
    }
}
