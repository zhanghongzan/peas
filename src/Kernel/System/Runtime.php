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
        Configure::setSeveral(self::_initConfig());

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
}
