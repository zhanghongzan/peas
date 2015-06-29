<?php
namespace Peas\Http;

use Peas\Support\Traits\StaticConfigTrait;

/**
 * Peas Framework
 *
 * Session管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Session
{
    use StaticConfigTrait;

    /**
     * Session默认配置
     * [
     *     'prefix' => 'pf_', // session名称前缀
     *     'config' => [      // 配置参数，参数名=>参数值，参数名为php默认配置名，这里的设置将覆盖默认设置
     *         'session.use_cookies'    => 1,
     *         'session.gc_maxlifetime' => 1440,
     *     ],
     * ];
     *
     * @var array
     */
    private static $_defaultConfig = [
        'prefix' => 'pf_',
        'config' => [
            'session.use_cookies'    => 1,
            'session.gc_maxlifetime' => 1440,
        ],
    ];


    /**
     * 启动session
     *
     * @return boolean 启动成功返回true，失败返回false
     */
    public static function start()
    {
        if (isset($_SESSION) && session_id()) {
            return true;
        }
        foreach (self::getConfig('config') as $name => $value) {
            ini_set($name, $value);
        }
        return session_start();
    }

    /**
     * 获取session
     *
     * @param  string $name session名
     * @return mixed session值，不存在时返回null
     */
    public static function get($name)
    {
        if (empty($_SESSION) || !array_key_exists(self::getConfig('prefix') . $name, $_SESSION)) {
            return null;
        }
        return unserialize(base64_decode($_SESSION[self::getConfig('prefix') . $name]));
    }

    /**
     * 设置session
     *
     * @param  string $name  session名
     * @param  mixed  $value 值
     * @return void
     */
    public static function set($name, $value)
    {
        $_SESSION[self::getConfig('prefix') . $name] = base64_encode(serialize($value));
    }

    /**
     * 清除单个session
     *
     * @param string $name session名
     * @return void
     */
    public static function remove($name)
    {
        unset($_SESSION[self::getConfig('prefix') . $name]);
    }

    /**
     * 清空所有session
     *
     * @return void
     */
    public static function clear()
    {
        $_SESSION = array();
    }

    /**
     * 销毁session
     *
     * @return void
     */
    public static function destroy()
    {
        unset($_SESSION);
        session_destroy();
    }

    /**
     * 检查session是否存在
     *
     * @param  string $name session名
     * @return boolean 存在返回true，不存在返回false
     */
    public static function isExists($name)
    {
        return array_key_exists(self::getConfig('prefix') . $name, $_SESSION);
    }
}
