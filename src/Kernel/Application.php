<?php
/**
 * Peas Framework
 *
 *
 *
 * @package   Peas_
 * @link      http://peas.ukeer.com/guide/
 * @copyright Copyright (c) 2013 uKeer.com
 * @license   http://peas.ukeer.com/license/new-bsd.html New BSD License
 * @author    Hongzan Zhang <kevin@ukeer.com>
 * @version   $Id$
 */

namespace Peas\Kernel;

class Application
{
    /**
     * 创建应用实例
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        date_default_timezone_set('PRC');
    }

    /**
     * 启动应用
     *
     * @return void
     */
    public function run()
    {
        $contr = new \App\Controller\Test\TestController();
        $contr->test();
    }
}