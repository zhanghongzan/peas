<?php
namespace Peas\View;

use Peas\View\Compiler\Compiler;

/**
 * Peas Framework
 *
 * 模板引擎标签扩展类接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
interface CornTagInterface
{
    /**
     * 起始标签处理
     *
     * @param  Compiler $compiler 当前编译器
     * @param  string   $tagStr   标签内容
     * @return string   解析之后的内容
     */
    public function begin(&$compiler, $tagStr);

    /**
     * 结束标签处理
     *
     * @param  Compiler $compiler  当前编译器
     * @param  string   $blockText 结束标签与开始标签之间的内容
     * @return string   解析之后的内容
     */
    public function end(&$compiler, &$blockText);
}
