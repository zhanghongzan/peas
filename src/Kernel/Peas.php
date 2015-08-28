<?php
namespace Peas\Kernel;

use Peas\Kernel\System\Application;

class Peas
{
    /**
     * 框架版本号
     *
     * @var string
     */
    const VERSION = '1.0 beta';


    /**
     * 初始化
     *
     * @param string $basePath 系统根目录
     */
    public function __construct($basePath = '')
    {
        defined('_MODE') or define('_MODE', 'develop');
        define('_PATH', $basePath);
        (_MODE != 'debug') or Debug::begin();
    }

    /**
     * 启动应用
     *
     * @return void
     */
    public function run()
    {
        $application = new Application();
        $application->run();
        (_MODE != 'debug') or Debug::end();
    }
}
