<?php
namespace Peas\Support\Traits;

/**
 * Peas Framework
 *
 * 静态属性$_config管理trait
 * 如果需要设置默认值，可以定义静态$_defaultConfig属性，示例：private static $_defaultConfig = [];
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
trait StaticConfigTrait
{
    /**
     * 配置参数
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * 参数是否已经初始化
     *
     * @var boolean
     */
    protected static $_configInitialized = false;


    /**
     * 设置配置参数
     *
     * @param  string|array $name 参数名|含有多个参数的数组
     * @param  mixed $value 参数值，$name为参数名时有效
     * @return void
     */
    public static function setConfig($name, $value = null)
    {
        self::_initializedCheck();
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
        self::_initializedCheck();
        return empty($name) ? self::$_config : (array_key_exists($name, self::$_config) ? self::$_config[$name] : null);
    }

    /**
     * 初始化检测
     *
     * @return void
     */
    private static function _initializedCheck()
    {
        if (!self::$_configInitialized) {
            if (isset(self::$_defaultConfig)) {
                self::$_config = self::$_defaultConfig;
            }
            self::$_configInitialized = true;
        }
    }
}
