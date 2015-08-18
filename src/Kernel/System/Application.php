<?php
namespace Peas\Kernel;

use Peas\Config\Configure;
use Peas\Http\Session;
use Peas\Routing\Router;

/**
 * Peas Framework
 *
 * Framework入口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Application
{
    /**
     * 创建应用实例
     */
    public function __construct()
    {
        if (_MODE == 'debug') {
            Debug::begin(); // 标记起始调试点
        }
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        define('_ROOT', $scriptDir == '/' || $scriptDir == "\\" ? '' : $scriptDir);

        $runtimePath = self::getPath('storage') . '/framework/cache/~runtime.php';
        (_MODE == 'work' && is_file($runtimePath)) ? require $runtimePath : Runtime::build();
    }

    /**
     * 启动应用
     *
     * @return void
     */
    public function run()
    {
        date_default_timezone_set(Configure::get('_default.timezone')); // 设置系统时区
        set_error_handler(['Peas\Kernel\ErrorHandler', 'handler']);     // 设置错误处理
        set_exception_handler(['Peas\Support\Exception', 'handler']);   // 设置异常处理

        if (Configure::get('_session.autoStart')) {
            Session::start();
        }

        self::_doRunUrl(Router::dispatch());

        // 输出调试信息
        if (_MODE == 'debug') {
            Debug::end();
        }
    }

    /**
     * 执行URL
     *
     * @param  string  $url         要执行的URL
     * @param  boolean $ifAppFilter 是否执行appFilter
     * @param  boolean $ifFilter    是否执行actionFilter
     * @param  array   $param       私有Get参数
     * @return void
     */
    private static function _doRunUrl($url, $ifAppFilter = TRUE, $ifFilter = TRUE, $param = array())
    {
        $cache = FALSE;
        if (_MODE == 'work') {
            $cachePath = self::_getConfig('_runtime.buildDir') . '/~' . urlencode($url) . '.php';
            if (is_file($cachePath)) {
                $cache = include $cachePath;
            }
        }

        if ($cache !== FALSE && !empty($cache)) {
            $urlInfo      = $cache['urlInfo'];
            $startFilters = $cache['filters'][0];
            $endFilters   = $cache['filters'][1];
            $actionName   = $cache['actionInfo'][0];
            $extendPeas   = $cache['actionInfo'][1];
        } else {
            $urlInfo      = self::_getClassInfoFromUrl($url);
            $startFilters = self::_getFilters('actionStart', $urlInfo[0], $urlInfo[1]);
            $endFilters   = self::_getFilters('actionEnd',   $urlInfo[0], $urlInfo[1]);
            $actionName   = $urlInfo[2] ? self::_getConfig('_default.actionPre') . $urlInfo[0] : 'Peas_Action';
            $extendPeas   = ($actionName == 'Peas_Action' || is_subclass_of($actionName, 'Peas_Action')) ? TRUE : FALSE;
        }

        if ($urlInfo === FALSE) {
            Peas_Function::to404($url);
            return;
        }

        if (!empty($param)) {
            $_GET[$urlInfo[0] . '.' . $urlInfo[1]] = $param;
        }
        if ($ifAppFilter) {
            self::_doFilters(self::_getConfig('_filter.appStart'), 'appStart', $url, $urlInfo);
        }
        if ($ifFilter) {
            self::_doFilters($startFilters, 'actionStart', $url, $urlInfo);
        }
        $action = new $actionName();
        if ($extendPeas) {
            $action->_run($urlInfo[0], $urlInfo[1], $urlInfo[2]);
        } else {
            $methodName = $urlInfo[1];
            $action->$methodName();
        }
        if ($ifFilter) {
            self::_doFilters($startFilters, 'actionEnd', $url, $urlInfo);
        }
        if ($ifAppFilter) {
            self::_doFilters(self::_getConfig('_filter.appEnd'), 'appEnd', $url, $urlInfo);
        }

        // 记录缓存数据
        if (_MODE == 'work' && ($cache === FALSE || empty($cache))) {
            Peas_System_Runtime::_buildAction($url, $urlInfo, array($startFilters, $endFilters), array($actionName, $extendPeas));
        }
    }

    /**
     * 执行过滤器
     *
     * @param array  $filters 要执行的过滤器
     * @param string $type    过滤器类型
     * @param string $url
     * @param array  $urlInfo
     */
    private static function _doFilters($filters, $type, $url, $urlInfo)
    {
        if (!empty($filters)) {
            foreach ($filters as $filterName) {
                if (method_exists($filterName, $type)) {
                    $filter = new $filterName();
                    $filter->$type($url, $urlInfo);
                }
            }
        }
    }

    /**
     * 获取匹配的过滤器，仅获取不执行
     *
     * @param  string $type       过滤器类型
     * @param  string $actionName 控制器名称
     * @param  string $methodName 方法名
     * @return array 所有匹配的过滤器名
     */
    private static function _getFilters($type, $actionName, $methodName = '')
    {
        $filters = self::_getConfig('_filter.' . $type);
        if (empty($filters)) {
            return array();
        }
        $nameStr = $actionName . ($methodName == '' ? '' : '_' . $methodName);
        $matchFilters = array();
        foreach ($filters as $filter => $pattern) {
            if (($pattern[0] == 'exec' && preg_match($pattern[1], $nameStr)) || ($pattern[0] == 'jump' && !preg_match($pattern[1], $nameStr))) {
                array_push($matchFilters, $filter);
            }
        }
        return $matchFilters;
    }

    /**
     * 从URL表达式中解析出actionName和methodName
     *
     * @param  string $url URL表达式，格式：'[分组/模块/操作]
     * @return array|boolean 如果指定类和方法存在返回array('类名', '方法名', TRUE), 不存在但是模板存在则返回array('类名', '方法名', FALSE), 都不存在返回FALSE
     */
    private static function _getClassInfoFromUrl($url)
    {
        $url  = trim($url, '/');
        $arr0 = empty($url) ? array() : explode('/', $url);

        $arr1 = $arr2 = array();
        for ($i = 0, $size = count($arr0); $i < $size; $i ++) {
            $ucVal = ucfirst($arr0[$i]);
            array_push($arr1, $ucVal);
            if ($i < $size - 1) {
                array_push($arr2, $ucVal);
            }
        }
        $arr3 = $arr1;
        $arr4 = $arr2;

        $defaultClass  = 'Index';
        $defaultMethod = 'main';

        // 优先级1：***/***/***/index/main
        array_push($arr1, $defaultClass);
        $checkResult = self::_checkClassInfo(implode('_', $arr1), $defaultMethod);
        if ($checkResult !== FALSE) {
            return $checkResult;
        }

        // 访问主页且主页不存在...
        if (empty($arr0)) {
            return FALSE;
        }

        // 优先级2：***/***/Index/***
        array_push($arr2, $defaultClass);
        $checkResult = self::_checkClassInfo(implode('_', $arr2), $arr0[count($arr0) - 1]);
        if ($checkResult !== FALSE) {
            return $checkResult;
        }

        // 优先级3：***/***/***/main
        $checkResult = self::_checkClassInfo(implode('_', $arr3), $defaultMethod);
        if ($checkResult !== FALSE) {
            return $checkResult;
        }

        // 优先级4：***/***/***
        return (empty($arr4)) ? FALSE : self::_checkClassInfo(implode('_', $arr4), $arr0[count($arr0) - 1]);
    }

    /**
     * 检查指定的$className, $methodName是否存在
     *
     * @param  string $className  类名
     * @param  string $methodName 方法名
     * @return array|boolean 如果指定类和方法存在返回array('类名', '方法名', TRUE), 不存在但是模板存在则返回array('类名', '方法名', FALSE), 都不存在返回false
     */
    private static function _checkClassInfo($className, $methodName)
    {
        if (method_exists(self::_getConfig('_default.actionPre') . $className, $methodName)) {
            return array($className, $methodName, TRUE);
        }
        $template = Peas_Action::getTemplateInstance();
        if ($template != null && $template->templateExists(str_replace('_', '/', $className) . '.' . $methodName . '.php')) {
            return array($className, $methodName, FALSE);
        }
        return FALSE;
    }


    /**
     * 404
     *
     * @param  $url 错误访问的url
     * @return void
     */
    public static function to404($url = '')
    {
        $app404Function = Configure::get('_404.function');
        if (!empty($app404Function)) {
            $app404Function($url);
        }
        if (!empty(Configure::get('_404.page'))) {
            if (!headers_sent()) {
                header("HTTP/1.0 404 Not Found");
                header("Status: 404 Not Found");
            }
            include Configure::get('_404.page');
        }
        exit;
    }

    /**
     * 500
     *
     * @param  $errInfo 错误信息
     * @return void
     */
    public static function to500($errInfo = '')
    {
        $app500Function = Configure::get('_500.function');
        if (!empty($app500Function)) {
            $app500Function($errInfo);
        }
        if (!empty(Configure::get('_500.page'))) {
            if (!headers_sent()) {
                header("HTTP/1.x 500 Internal Server Error");
                header('Status: 500 Internal Server Error');
            }
            include Configure::get('_500.page');
        }
        exit;
    }

    /**
     * 自定义错误处理，用于捕获异常
     *
     * @param  string $errno   错误报告级别
     * @param  string $errstr  出错信息
     * @param  string $errfile 出错所在文件
     * @param  int    $errline 出错所在行
     * @param  string $errInfo 已经格式化的错误信息
     * @return void
     */
    public static function errorHandlerCallback($errno, $errstr, $errfile, $errline, $errInfo)
    {
        if ($errno == E_ERROR || $errno == E_USER_ERROR) {
            self::to500($errInfo);
        }
    }
}
