<?php
namespace Peas\Support\Traits;

/**
 * Peas Framework
 *
 * 静态属性$_config管理trait
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
trait ConfigStaticTrait
{
    /**
     * 配置参数
     *
     * @var array
     */
    protected static $_config = [];


    /**
     * 设置配置参数
     *
     * @param  string|array $name 参数名|含有多个参数的数组
     * @param  mixed $value 参数值，$name为参数名时有效
     * @return void
     */
    public static function setConfig($name, $value = null)
    {
        if (is_null($name)) {
            self::$_config = $value;
        } elseif (is_array($name)) {
            self::$_config = array_merge(self::$_config, $name);
        } else {
            self::$_config[$name] = $value;
        }
    }

    /**
     * 获取配置参数
     *
     * @param  string $name 参数名
     * @return mixed $name为空时返回所有参数，无此参数时返回null
     */
    public static function getConfig($name = '')
    {
        return empty($name) ? self::$_config : (array_key_exists($name, self::$_config) ? self::$_config[$name] : null);
    }
}
