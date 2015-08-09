<?php
namespace Peas\Config;

/**
 * Peas Framework
 *
 * 配置管理类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Configure
{
    /**
     * 配置数据
     *
     * @var array
     */
    protected static $_config = [];


    /**
     * 获取配置
     *
     * @param  string $key 配置名，不传此参数则表示获取所有配置
     * @return mixed  配置值，不存在时返回null
     */
    public static function get($key = null)
    {
        if (is_null($key)) {
            return self::$_config;
        }
        $parts = explode('.', $key);
        $data  = self::$_config;
        foreach ($parts as $item) {
            if (!is_array($data) || !array_key_exists($item, $data)) {
                return null;
            }
            $data = $data[$item];
        }
        return $data;
    }

    /**
     * 清除配置
     *
     * @param  string $key 配置名
     * @return void
     */
    public static function remove($key)
    {
        if (is_null($key)) {
            return;
        }
        $parts = explode('.', $key);
        $data  = &self::$_config;
        while (count($parts) > 1) {
            $item = array_shift($parts);
            if (!array_key_exists($item, $data) || !is_array($data[$item])) {
                return;
            }
            $data = &$data[$item];
        }
        unset($data[array_shift($parts)]);
    }

    /**
     * 设置配置
     *
     * @param  string $key 配置名，为null表示初始化配置
     * @param  mixed  $value 值
     * @return void
     */
    public static function set($key, $value)
    {
        if (is_null($key)) {
            self::$_config = $value;
            return;
        }
        $parts = explode('.', $key);
        $data  = &self::$_config;
        while (count($parts) > 1) {
            $item = array_shift($parts);
            if (!array_key_exists($item, $data) || !is_array($data[$item])) {
                $data[$item] = [];
            }
            $data = &$data[$item];
        }
        $data[array_shift($parts)] = $value;
    }

    /**
     * 清空所有配置
     *
     * @return void
     */
    public static function clear()
    {
        self::$_config = [];
    }

    /**
     * 检查配置是否存在
     *
     * @param  string  $key 配置名
     * @return boolean 存在返回true，不存在返回false
     */
    public static function isExists($key)
    {
        if (is_null($key)) {
            return false;
        }
        return self::get($key) !== null;
    }
}
