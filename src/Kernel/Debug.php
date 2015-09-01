<?php
namespace Peas\Kernel;

use Peas\Config\Configure;
use Peas\Kernel\System\ErrorHandler;
use Peas\Support\Exception;
use Peas\Database\DbDebug;
use Peas\Kernel\System\Application;

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

    public static function show()
    {
        $errors = ErrorHandler::$errors;
        $exceptions = Exception::$exceptions;

        $sqls = DbDebug::$sqls;
        $dbQueryNum = DbDebug::$queryNum;
        $dbWriteNum = DbDebug::$writeNum;

        $vars = Application::getTemplateInstance()->getAssign();
        unset($vars['peas']);
        unset($vars['_c']);
        $varsNum = count($vars);

        $debugMark = array();
        $markNames = array_flip(self::$_marker['mark']);
        ksort($markNames);
        $prevMark  = '';
        foreach ($markNames as $key => $val) {
            $curMark = array();
            if (!empty($prevMark)) {
                $curMark['timeUsed'] = self::useTime($prevMark, $val);
                $curMark['memUsed']  = self::useMemory($prevMark, $val);
                $curMark['peakUsed'] = self::getMemPeak($prevMark, $val);
            } else {
                $curMark['timeUsed'] = $curMark['memUsed'] = $curMark['peakUsed'] = '';
            }
            $prevMark = $curMark['mark'] = $val;
            $debugMark[] = $curMark;
        }
        $allTimeUsed = self::useTime('_peas_debug_begin',   '_peas_debug_end');
        $allMemUsed  = self::useMemory('_peas_debug_begin', '_peas_debug_end');

        // 所有加载的文件
        $allLoad = get_included_files();

        $openWindow = Configure::get('_debug.showWindow');
        include __DIR__ . '/include/debug.tpl.php';
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
        $userDebugFunction = Configure::get('_debug.userFunction');
        if (!empty($userDebugFunction)) {
            if (is_string($userDebugFunction)) {
                $userDebugFunction();
            } elseif (is_array($userDebugFunction)) {
                $userDebugClass = new $userDebugFunction[0]();
                $userDebugClass->$userDebugFunction[1]();
            }
        }
        self::show();
    }

    /**
     * 区间使用时间查看, 秒
     *
     * @param  string $start    开始标记的名称
     * @param  string $end      结束标记的名称
     * @param  int    $decimals 时间的小数位
     * @return string 使用时间字符串
     */
    public static function useTime($start, $end, $decimals = 6)
    {
        if (!array_key_exists($start, self::$_marker['time']) || !array_key_exists($end, self::$_marker['time'])) {
            return '';
        }
        return number_format(self::$_marker['time'][$end] - self::$_marker['time'][$start], $decimals);
    }

    /**
     * 区间使用内存查看, KB
     *
     * @param  string $start 开始标记的名称
     * @param  string $end   结束标记的名称
     * @return string
     */
    public static function useMemory($start, $end)
    {
        if (!array_key_exists($start, self::$_marker['mem']) || !array_key_exists($end, self::$_marker['mem'])) {
            return '';
        }
        return number_format((self::$_marker['mem'][$end] - self::$_marker['mem'][$start]) / 1024);
    }

    /**
     * 区间使用内存峰值查看, KB
     *
     * @param  string $start 开始标记的名称
     * @param  string $end   结束标记的名称
     * @return string
     */
    public static function getMemPeak($start, $end)
    {
        if (!array_key_exists($start, self::$_marker['peak']) || !array_key_exists($end, self::$_marker['peak'])) {
            return '';
        }
        return number_format(max(self::$_marker['peak'][$start], self::$_marker['peak'][$end]) / 1024);
    }

    /**
     * 输出html格式的数组
     *
     * @param  array $arr
     * @param  int   $level
     * @return string
     */
    public static function showArrayToHtml($arr, $level = 0)
    {
        if (is_object($arr)) {
            return var_dump($arr);
        }
        if (!is_array($arr)) {
            return is_string($arr) ? "'" . htmlspecialchars($arr, ENT_QUOTES) . "'" : $arr;
        }
        $spacing = '';
        for ($i = 0; $i < $level; $i ++) {
            $spacing .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        }
        $result = 'array (<br />';
        foreach ($arr as $key => $val) {
            $result .= $spacing . "&nbsp;&nbsp;&nbsp;&nbsp;" . (is_string($key) ? "'" . htmlspecialchars($key, ENT_QUOTES) . "'" : $key) . " => " . self::showArrayToHtml($val, $level + 1) . '<br />';
        }
        return $result . $spacing . ')';
    }
}
