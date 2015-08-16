<?php
namespace Peas\Kernel;

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
     * @param  string $basePath 系统根目录
     */
    public function __construct($basePath = '')
    {
        define('_PATH', $basePath);
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
    }
}
