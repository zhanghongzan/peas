<?php
namespace Peas\Kernel\System;

use Peas\View\CornTemplate;

/**
 * Peas Framework
 *
 * 会话信息
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class ActionContext
{
    /**
     * URL路径
     *
     * @var string
     */
    public static $path = '';

    /**
     * 控制器带包名的完整路径
     *
     * @var string
     */
    public static $controller = '';

    /**
     * 方法名
     *
     * @var string
     */
    public static $method = '';

    /**
     * 默认匹配视图路径
     *
     * @var string
     */
    public static $view = '';

    /**
     * 模板引擎
     *
     * @var CornTemplate
     */
    public static $template = null;

    /**
     * 默认缓存ID
     *
     * @var string
     */
    public static $cacheId = '';
}
