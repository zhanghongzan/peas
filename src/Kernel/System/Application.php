<?php
namespace Peas\Kernel\System;

use Peas\Config\Configure;
use Peas\Http\Session;
use Peas\Routing\Router;
use Peas\View\CornTemplate;

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
     * 模板引擎实例
     *
     * @var CornTemplate
     */
    private static $_templateInstance = NULL;


    /**
     * 创建应用实例
     */
    public function __construct()
    {
        $runtimePath = _PATH . '/storage/framework/cache/~runtime.php';
        (_MODE == 'work' && is_file($runtimePath)) ? require $runtimePath : Runtime::build();
    }

    /**
     * 启动应用
     *
     * @return void
     */
    public function run()
    {
        date_default_timezone_set(Configure::get('_default.timezone'));    // 设置系统时区
        set_exception_handler(['Peas\Support\Exception', 'handler']);      // 设置异常处理
        set_error_handler(['Peas\Kernel\System\ErrorHandler', 'handler']); // 设置错误处理

        Configure::get('_session.autoStart') or Session::start();
        self::_execute(Router::dispatch());
    }

    /**
     * 执行URL
     *
     * @param  string $url 要执行的URL
     * @return void
     */
    private static function _execute($url)
    {
        $cache = false;
        $cachePath = _PATH . '/storage/framework/cache/~' . urlencode($url) . '.php';
        if (is_file($cachePath)) {
            $cache = include $cachePath;
        }

        if (!empty($cache)) {
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
     * 从URL表达式中解析出控制器类和方法信息
     *
     * @param  string $url URL表达式，格式：'[分组/模块/操作]
     * @return array|boolean 如果指定类和方法存在返回['类名', '方法名', true], 不存在但是模板存在则返回['模板路径', '方法名', false], 都不存在返回false
     */
    private static function _getClassInfoFromUrl($url)
    {
        $url = trim($url, '/');
        $pieces = empty($url) ? [] : explode('/', $url);

        array_walk($pieces, function(&$value, $key) {
            $value = ucfirst($value);
        });

        $defaultClass  = 'Index';
        $defaultMethod = 'main';

        // 优先级1：***/***/***/index/main
        $firstLevelArr = $pieces;
        array_push($firstLevelArr, $defaultClass);
        $checkResult = self::_checkClassInfo($firstLevelArr, $defaultMethod);
        if ($checkResult !== false) {
            return $checkResult;
        }

        // 访问主页且主页不存在...
        if (empty($pieces)) {
            return false;
        }

        // 优先级2：***/***/***/main
        $checkResult = self::_checkClassInfo($pieces, $defaultMethod);
        if ($checkResult !== false) {
            return $checkResult;
        }

        // 优先级3：***/***/***
        $methodName = array_pop($pieces);
        return empty($pieces) ? false : self::_checkClassInfo($pieces, $methodName);
    }

    /**
     * 检查指定的类和方法是否存在
     *
     * @param  array  $classPathArr 类路径
     * @param  string $methodName   方法名
     * @return array|boolean 如果指定类和方法存在返回['类名', '方法名', true], 不存在但是模板存在则返回['模板路径', '方法名', false], 都不存在返回false
     */
    private static function _checkClassInfo(array $classPathArr, $methodName)
    {
        $classPath = 'App\\Controller\\' . implode('\\', $classPathArr) . 'Controller';
        if (method_exists($classPath, $methodName)) {
            return [$classPath, $methodName, true];
        }
        $template = self::getTemplateInstance();
        $templatePath = implode('/', $classPath) . '.' . $methodName . '.php';
        if ($template != null && $template->templateExists($templatePath)) {
            return [$templatePath, $methodName, false];
        }
        return false;
    }


    /**
     * 获取模板引擎实例
     *
     * @return CornTemplate
     */
    public static function getTemplateInstance()
    {
        if (self::$_templateInstance !== null) {
            return self::$_templateInstance;
        }
        $template = new CornTemplate(Configure::get('_template'));
        if (defined('TEMPLATE_THEME')) {
            $template->setTheme(TEMPLATE_THEME);
        }
        return self::$_templateInstance = $template;
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
        if (!headers_sent()) {
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
        }
        if (!empty(Configure::get('_404.page'))) {
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
        if (!headers_sent()) {
            header("HTTP/1.x 500 Internal Server Error");
            header('Status: 500 Internal Server Error');
        }
        if (!empty(Configure::get('_500.page'))) {
            include Configure::get('_500.page');
        }
        exit;
    }
}
