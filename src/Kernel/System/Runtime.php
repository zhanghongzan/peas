<?php
namespace Peas\Kernel\System;

use Peas\Config\Configure;

/**
 * Peas Framework
 *
 * 系统运行初始化类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Runtime
{
    /**
     * 系统初始化，检查目录，根据生产环境生成缓存
     *
     * @return void
     */
    public static function build()
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        define('_ROOT', $scriptDir == '/' || $scriptDir == "\\" ? '' : $scriptDir);

        // 初始化配置参数
        Configure::setSeveral(self::_getInitConfig());

        // 初始化系统组件
        $initCode = self::_getInitCode();
        eval($initCode);

        // 生产环境生成缓存文件
        (_MODE == 'work') or self::_createRuntimeFile($initCode);
    }

    /**
     * 创建缓存文件
     *
     * @param string $initCode
     */
    private static function _createRuntimeFile($initCode = '')
    {
        $fullCode  = '<?php define(\'_ROOT\', ' . _ROOT . ');';
        $fullCode .= 'Configure::set(null, ' . var_export(Configure::get(), true) . ');' . $initCode;
        file_put_contents(_PATH . '/storage/framework/cache/~runtime.php', $fullCode);
    }

    /**
     * 获取配置
     *
     * @return void
     */
    private static function _getInitConfig()
    {
        // 读取默认配置
        $setting = [];
        $configPath = _PATH . '/config/peas.conf.php';
        if (is_file($configPath)) {
            $setting = include $configPath;
        }
        $setting = array_merge($setting, DefaultConfig::get());

        // 读取应用配置
        if (!empty($setting['_config.app'])) {
            if (is_array($setting['_config.app'])) {
                foreach ($setting['_config.app'] as $oneConf) {
                    if (!empty($oneConf) && is_file($oneConf)) {
                        $setting = array_merge($setting, include $oneConf);
                    }
                }
            } else if (is_file($setting['_config.app'])) {
                $setting = array_merge($setting, include $setting['_config.app']);
            }
        }
        // 获取路由配置
        if (!empty($setting['_config.route']) && is_file($setting['_config.route'])) {
            $setting['_url.rules'] = include $setting['_config.route'];
        }
        return $setting;
    }


    /**
     * 获取初始化代码
     *
     * @return string
     */
    private static function _getInitCode()
    {
        $code  = 'define(\'_RUNTIME_VERSION\', ' . (_MODE == 'work' ? mt_rand(10001, 99999) : 0) . ');';
        $code .= 'define(\'_STATIC\', ' . (empty(Configure::get('_default.static')) ? _ROOT : Configure::get('_default.static')) . ');';
        $code .= self::_getBasicComponentCode();
        $code .= self::_getErrorInitCode();
        $code .= self::_getExceptionInitCode();
        return $code;
    }


    /**
     * 获取常用组件初始化代码
     *
     * @return string
     */
    private static function _getBasicComponentCode()
    {
        $code = '';
        if (!empty(Configure::get('_url'))) {
            $code .= '\Peas\Routing\Router::setConfig(Configure::get(\'_url\'));';
        }
        if (!empty(Configure::get('_cookie'))) {
            $code .= '\Peas\Http\Cookie::setConfig(Configure::get(\'_cookie\'));';
        }
        if (!empty(Configure::get('_session'))) {
            $code .= '\Peas\Http\Session::setConfig(Configure::get(\'_session\'));';
        }
        $code .= '\Peas\Cache\CacheCenter::init(Configure::get(\'_cache\'));';
        $code .= '\Peas\Log\LogCenter::init(Configure::get(\'_log\'));';
        return $code;
    }

    /**
     * 异常处理初始化代码
     *
     * @return string
     */
    private static function _getExceptionInitCode()
    {
        $code = '';
        if (Configure::get('_exception.log.open')) {
            $exceptionLog = empty(Configure::get('_exception.log.loggers')) ? '\Peas\Log\LogCenter::getLogger()' : 'new \Peas\Log\Logger(Configure::get(\'_exception.log\'))';
            $code = '\Peas\Support\Exception::setLogger(' . $exceptionLog . ');';
        }
        return $code;
    }

    /**
     * 错误处理初始化代码
     *
     * @return string
     */
    private static function _getErrorInitCode()
    {
        $code = '';
        if (Configure::get('_error.log.open')) {
            $errorLog = empty(Configure::get('_error.log.loggers')) ? '\Peas\Log\LogCenter::getLogger()' : 'new \Peas\Log\Logger(Configure::get(\'_error.log\'))';
            $code .= '\Peas\Kernel\System\ErrorHandler::setLogger(' . $errorLog . ');';
        }
        if (Configure::get('_error.callback')) {
            $code .= '\Peas\Kernel\System\ErrorHandler::setCallback(Configure::get(\'_error.callback\'));';
        }
        return $code;
    }


    /**
     * 生成Action缓存
     *
     * @param  string $cachePath 缓存文件路径
     * @return void
     */
    public static function buildAction($cachePath)
    {
        $cacheArray = [
            'path'       => ActionContext::$path,
            'controller' => ActionContext::$controller,
            'method'     => ActionContext::$method,
            'view'       => ActionContext::$view,
        ];
        file_put_contents($cachePath, '<?php ' . 'return ' . var_export($cacheArray, true) . ";");
    }

    /**
     * 从URL表达式匹配对应控制器
     *
     * @param  string $url URL表达式，格式：'[分组/模块/操作]
     * @return boolean 如果指定类和方法存在返回['类名', '方法名', true], 不存在但是模板存在则返回['模板路径', '方法名', false], 都不存在返回false
     */
    public static function matchActionFromUrl($url)
    {
        $url = trim($url, '/');
        $pieces = empty($url) ? [] : explode('/', $url);
        array_walk($pieces, function(&$value, $key) {
            $value = ucfirst($value);
        });

        // 优先级1：***/***/***/index/main
        $firstLevelPieces   = $pieces;
        $firstLevelPieces[] = 'Index';
        if (self::_matchAction($firstLevelPieces, 'main')) {
            return true;
        }
        // 访问主页且主页不存在...
        if (empty($pieces)) {
            return false;
        }
        // 优先级2：***/***/***/main
        if (self::_matchAction($pieces, 'main')) {
            return true;
        }
        // 优先级3：***/***/index/***
        $method = array_pop($pieces);
        $thirdLevelPieces   = $pieces;
        $thirdLevelPieces[] = 'Index';
        if (self::_matchAction($thirdLevelPieces, $method)) {
            return true;
        }
        // 优先级4：***/***/***
        return empty($pieces) ? false : self::_matchAction($pieces, lcfirst($method));
    }

    /**
     * 匹配控制器，匹配成功时初始化会话信息
     *
     * @param  array   $classPath 控制器类路径
     * @param  string  $method    方法名
     * @return boolean 匹配成功返回true，匹配失败返回false
     */
    private static function _matchAction(array $classPath, $method)
    {
        $controller = 'App\\Controller\\' . implode('\\', $classPath) . 'Controller';
        array_walk($classPath, function(&$value, $key) {
            $value = lcfirst($value);
        });
        $classPathStr = implode('/', $classPath);
        $view = $classPathStr . '.' . $method . '.php';
        if (method_exists($controller, $method)) {
            Application::initActionContext($classPathStr . '/' . $method, $controller, $method, $view);
            return true;
        }
        $template = Application::getTemplateInstance();
        if ($template != null && $template->templateExists($view)) {
            Application::initActionContext($classPathStr . '/' . $method, '', $method, $view);
            return true;
        }
        return false;
    }
}
