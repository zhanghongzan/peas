<?php
namespace Peas\Http;

use Peas\Support\Traits\StaticConfigTrait;

/**
 * Peas Framework
 *
 * Cookie管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Cookie
{
    use StaticConfigTrait;

    /**
     * 默认配置参数
     * [
     *     'expire'   => 3600,  // cookie默认有效期
     *     'prefix'   => 'pf_', // cookie名称前缀
     *     'path'     => '/',   // cookie路径
     *     'domain'   => '',    // cookie域名
     *     'httpOnly' => false, // 是否使用HttpOnly
     * ];
     *
     * @var array
     */
    private static $_defaultConfig = [
        'expire'   => 3600,
        'prefix'   => 'pf_',
        'path'     => '/',
        'domain'   => '',
        'httpOnly' => false,
    ];


    /**
     * 获取cookie
     *
     * @param  string $name cookie名
     * @return mixed cookie值，不存在时返回NULL
     */
    public static function get($name)
    {
        if (empty($_COOKIE) || !array_key_exists(self::getConfig('prefix') . $name, $_COOKIE)) {
            return null;
        }
        return unserialize(base64_decode($_COOKIE[self::getConfig('prefix') . $name]));
    }

    /**
     * 清除单个cookie
     *
     * @param  string $name cookie名
     * @return void
     */
    public static function remove($name)
    {
        self::set($name, '', -100);
        unset($_COOKIE[self::getConfig('prefix') . $name]);
    }

    /**
     * 设置Cookie
     *
     * @param  string $name   cookie名
     * @param  mixed  $value  值
     * @param  int    $expire 有效时长：秒，不传则默认为默认有效期，0或者NULL表示有效期为浏览器进程
     * @return void
     */
    public static function set($name, $value, $expire = 'default')
    {
        $expire = $expire == 'default' ? self::getConfig('expire') : $expire;
        $expire = empty($expire) ? 0 : time() + $expire;
        $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;

        $name  = self::getConfig('prefix') . $name;
        $value = base64_encode(serialize($value));

        setcookie($name, $value, $expire, self::getConfig('path'), self::getConfig('domain'), $secure, self::getConfig('httpOnly'));
        $_COOKIE[$name] = $value;
    }

    /**
     * 清空所有cookie
     *
     * @return void
     */
    public static function clear()
    {
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $key => $value) {
                setcookie($key, '', -100, self::getConfig('path'), self::getConfig('domain'));
                unset($_COOKIE[$key]);
            }
        }
    }

    /**
     * 检查cookie是否存在
     *
     * @param  string $name cookie名
     * @return boolean 存在返回TRUE，不存在返回FALSE
     */
    public static function isExists($name)
    {
        return array_key_exists(self::getConfig('prefix') . $name, $_COOKIE);
    }
}
