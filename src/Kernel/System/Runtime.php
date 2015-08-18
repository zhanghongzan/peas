<?php
namespace Peas\Kernel;

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
     * 系统初始化，加载文件，检查目录，根据生产环境生成缓存
     *
     * @return void
     */
    public static function build()
    {
        define('_RUNTIME_VERSION', _MODE == 'work' ? mt_rand(10001, 99999) : 0); // 当前运行版本号

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
        Configure::setSeveral($setting);

        self::_initBasicComponent();
    }

    private static function _initBasicComponent()
    {
        \Peas\Routing\Router::setConfig(Configure::get('_url'));
        \Peas\Http\Cookie::setConfig(Configure::get('_cookie'));
        \Peas\Http\Session::setConfig(Configure::get('_session'));
        \Peas\Cache\CacheCenter::init(Configure::get('_cache'));
        \Peas\Log\LogCenter::init(Configure::get('_log'));
        if (Configure::get('_exception.log.open')) {
            if (empty(Configure::get('_exception.log.loggers'))) {
                \Peas\Support\Exception::setLogger(\Peas\Log\LogCenter::getLogger());
            } else {
                \Peas\Support\Exception::setLogger(new \Peas\Log\Logger(Configure::get('_exception.log')));
            }
        }
        if (Configure::get('_error.log.open')) {
            if (empty(Configure::get('_error.log.loggers'))) {
                \Peas\Kernel\ErrorHandler::setLogger(\Peas\Log\LogCenter::getLogger());
            } else {
                \Peas\Kernel\ErrorHandler::setLogger(new \Peas\Log\Logger(Configure::get('_error.log')));
            }
        }
        if (Configure::get('_error.callback')) {
            \Peas\Kernel\ErrorHandler::setCallback(Configure::get('_error.callback'));
        }
    }
}
