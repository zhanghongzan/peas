<?php
namespace Peas\Cache;

use Peas\Config\Configure;

/**
 * Peas Framework
 *
 * 缓存操作辅助类，默认参数（可在配置中设置名为_cache的参数覆盖默认参数）：[
 *      'prefix'             => '',     // key前缀 <br>
 *      'defaultLifetime'    => 86400,  // int -1表示永久有效 <br>
 *      'defaultStore'       => 'file', // 默认存储器类型，可以是apc,file,xCache，默认为apc，也可以是自定义存储器名称 <br>
 *      'defaultStoreConfig' => [       // 默认存储器参数，没有可不传 <br>
 *          'directory' => _PATH . '/storage/app/cache', <br>
 *      ], <br>
 *  ]
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class CacheHelper
{
    /**
     * 缓存管理类
     *
     * @var Cache
     */
    private static $_cache = null;


    /**
     * 删除缓存
     *
     * @param  string  $id 缓存ID
     * @return boolean 成功返回true，失败返回false
     */
    public static function remove($id)
    {
        return self::_getInstance()->remove($id);
    }

    /**
     * 清空所有缓存记录
     *
     * @return boolean 成功返回true，失败返回false
     */
    public static function clear()
    {
        return self::_getInstance()->clear();
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
        return self::_getInstance()->set($id, $value, $lifetime);
    }

    /**
     * 获取缓存
     *
     * @param  string      $id 缓存ID
     * @return mixed|false 成功返回缓存数据，失败返回false
     */
    public static function get($id)
    {
        return self::_getInstance()->get($id);
    }

    /**
     * 验证缓存有效性
     *
     * @param  string    $id 缓存ID
     * @return int|false 有效时返回最后更新时间的时间戳，无效返回false
     */
    public static function test($id)
    {
        return self::_getInstance()->test($id);
    }


    /**
     * 获取缓存操作实例
     *
     * @return \Peas\Cache\Cache
     */
    private static function _getInstance()
    {
        if (self::$_cache != null) {
            return self::$_cache;
        }
        $_config = [
            'prefix'             => '',     // 默认key前缀为空
            'defaultLifetime'    => 86400,  // 默认有效期为一天
            'defaultStore'       => 'file', // 默认存储器类型为file
            'defaultStoreConfig' => [       // 默认存储器参数
                'directory' => _PATH . '/storage/app/cache',
            ],
        ];
        self::$_cache = new Cache(array_merge($_config, Configure::get('_cache')));
        return self::$_cache;
    }
}
