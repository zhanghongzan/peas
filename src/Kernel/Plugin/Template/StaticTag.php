<?php
namespace Peas\Kernel\Plugin\Template;

/**
 * Peas Framework
 *
 * 模板引擎标签扩展：静态文件目录输出
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class StaticTag
{
    public function begin()
    {
        return '<?php echo _STATIC;?>';
    }
}
