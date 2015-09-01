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

        (!Configure::get('_session.autoStart')) or Session::start();
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
            self::initActionContext($cache['path'], $cache['controller'], $cache['method'], $cache['view']);
        } elseif (!Runtime::matchActionFromUrl($url)) {
            self::to404($url);
        }

        $controller = ActionContext::$controller;
        if (empty($controller)) {
            $template = self::getTemplateInstance();
            $template->assign($_POST);
            $template->assign($_GET);
            $template->display(ActionContext::$view);
        } else {
            $method = ActionContext::$method;
            $controllerClass = new $controller();
            if (!(method_exists($controllerClass, 'peasInit') && $controllerClass->peasInit())) {
                $controllerClass->{$method}();
            }
        }

        // 记录缓存数据
        if (_MODE == 'work' && empty($cache)) {
            Runtime::buildAction($url, $cachePath);
        }
    }



    /**
     * 初始化会话信息
     *
     * @param  string $path       URL完整路径
     * @param  string $controller 控制器带包名的完整路径
     * @param  string $method     方法名
     * @param  string $view       默认匹配视图路径
     * @return void
     */
    public static function initActionContext($path, $controller, $method, $view)
    {
        ActionContext::$controller = $controller;
        ActionContext::$path       = $path;
        ActionContext::$method     = $method;
        ActionContext::$template   = self::getTemplateInstance();
        ActionContext::$view       = $view;
        ActionContext::$cacheId    = substr(md5($_SERVER['REQUEST_URI']), 8, 16);
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
        if (empty(Configure::get('_404.page'))) {
            echo '404';
        } else {
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
        if (empty(Configure::get('_500.page'))) {
            echo '500';
        } else {
            include Configure::get('_500.page');
        }
        exit;
    }
}
