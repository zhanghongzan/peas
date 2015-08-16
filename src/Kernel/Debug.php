<?php
namespace Peas\Kernel;

/**
 * Peas Framework
 *
 * 调试类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */

class Debug
{
    /**
     * 声明的所有变量
     *
     * @var array
     */
    public static $vars = array();

    /**
     * 调试位信息
     *
     * @var array
    */
    private static $_marker = array(
        'mark' => array(),
        'time' => array(),
        'mem'  => array(),
        'peak' => array(),
    );

    /**
     * 标记调试位
     *
     * @param string $name 标记位名称
     */
    public static function mark($name)
    {
        self::$_marker['time'][$name] = microtime(TRUE);
        self::$_marker['mark'][$name] = count(self::$_marker['time']);
        self::$_marker['mem'][$name]  = memory_get_usage();
        self::$_marker['peak'][$name] = function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : self::$_marker['mem'][$name];
    }

    /**
     * 页面开始
     *
     * @return void
     */
    public static function begin()
    {
        self::mark('_peas_debug_begin');
    }

    /**
     * 页面结束
     *
     * @return void
     */
    public static function end()
    {
        self::mark('_peas_debug_end');
    }
}