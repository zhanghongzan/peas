<?php
namespace Peas\View\Compiler;

use Peas\View\CornException;

/**
 * Peas Framework
 *
 * 模板引擎标签库
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Tags
{
    /**
     * 输出左定界符
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string
     */
    public static function _L_Tag(&$compiler)
    {
        $delimiters = $compiler->getDelimiter();
        return array_shift($delimiters);
    }

    /**
     * 输出右定界符
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string
     */
    public static function _R_Tag(&$compiler)
    {
        $delimiters = $compiler->getDelimiter();
        return array_pop($delimiters);
    }



    /**
     * 文本标签
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function literalTag(&$compiler)
    {
        $compiler->pushStack('literal');
        return '';
    }

    /**
     * 文本标签
     *
     * @param  Compiler $compiler
     * @return string   固定返回空字符串''
     * @throws CornException
     */
    public static function literalEndTag(&$compiler)
    {
        if ($compiler->popStack('literal') == false) {
            throw new CornException('[Template Error]出现无法匹配的结束标签php' . $compiler->getTemplateInfo(), 1025);
        }
        return '';
    }



    /**
     * php原生代码块开始标签
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function phpTag(&$compiler)
    {
        $compiler->pushStack('php');
        return '<?php ';
    }

    /**
     * php原生代码块结束标签
     *
     * @param  Compiler $compiler
     * @param  string   $blockText
     * @return string
     * @throws CornException
     */
    public static function phpEndTag(&$compiler, &$blockText)
    {
        if ($compiler->popStack('php') == false) {
            throw new CornException('[Template Error]出现无法匹配的结束标签php' . $compiler->getTemplateInfo(), 1025);
        }
        if ($compiler->stripSpace) {
            $blockText = self::stripWhiteSpace($blockText);
        }
        return ' ?>';
    }



    /**
     * php原生代码
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string
     */
    public static function pTag(&$compiler, $tagStr)
    {
        return '<?php ' . $tagStr . ' ?>';
    }



    /**
     * if标签处理器
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string
     */
    public static function ifTag(&$compiler, $tagStr)
    {
        $compiler->pushStack('if');
        return '<?php ' . self::stripWhiteSpace('if' . self::checkTagIntegrity($tagStr)) . ' ?>';
    }

    /**
     * elseif标签处理器
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string
     * @throws CornException
     */
    public static function elseifTag(&$compiler, $tagStr)
    {
        list($openTag) = end($compiler->symbolStack);
        if ($openTag != 'if') {
            throw new CornException('[Template Error]出现无法匹配的标签elseif' . $compiler->getTemplateInfo(), 1025);
        }
        return '<?php ' . self::stripWhiteSpace('elseif' . self::checkTagIntegrity($tagStr)) . ' ?>';
    }

    /**
     * else标签处理器
     *
     * @param  Compiler $compiler
     * @return string
     * @throws CornException
     */
    public static function elseTag(&$compiler)
    {
        list($openTag) = end($compiler->symbolStack);
        if ($openTag != 'if') {
            throw new CornException('[Template Error]出现无法匹配的标签else' . $compiler->getTemplateInfo(), 1025);
        }
        return '<?php else: ?>';
    }

    /**
     * if结束标签处理器
     *
     * @param  Compiler $compiler
     * @param  string   $blockText
     * @return string
     * @throws CornException
     */
    public static function ifEndTag(&$compiler, &$blockText)
    {
        if ($compiler->popStack('if') == false) {
            throw new CornException('[Template Error]出现无法匹配的结束标签if' . $compiler->getTemplateInfo(), 1025);
        }
        return '<?php endif ?>';
    }



    /**
     * foreach标签处理器
     *
     * @param  Compiler $compiler
     * @return string
     * @throws CornException
     */
    public static function foreachTag(&$compiler, $tagStr)
    {
        $compiler->pushStack('foreach');
        $tagStr = self::checkTagIntegrity($tagStr);
        $tagStrArr = explode(' as ', $tagStr, 2);

        if (!array_key_exists(0, $tagStrArr)) {
            throw new CornException('[Template Error]foreach标签语法错误' . $compiler->getTemplateInfo(), 1026);
        }
        $variable = trim(substr($tagStrArr[0], 1));
        return '<?php ' . self::stripWhiteSpace('if(!empty(' . $variable . ')):;foreach' . $tagStr) . ' ?>';
    }

    /**
     * foreachelse标签处理器
     *
     * @param  Compiler $compiler
     * @return string
     * @throws CornException
     */
    public static function foreachelseTag(&$compiler)
    {
        list($openTag) = end($compiler->symbolStack);
        if ($openTag != 'foreach') {
            throw new CornException('[Template Error]出现无法匹配的标签foreachelse' . $compiler->getTemplateInfo(), 1025);
        } else {
            $compiler->pushStack('foreachelse');
        }
        return '<?php endforeach; else: ?>';
    }

    /**
     * foreach结束标签处理器
     *
     * @param  Compiler $compiler
     * @param  string   $blockText
     * @return string
     * @throws CornException
     */
    public static function foreachEndTag(&$compiler, &$blockText)
    {
        list($openTag) = end($compiler->symbolStack);
        if ($openTag == 'foreach') {
            $compiler->popStack('foreach');
            return '<?php endforeach; endif ?>';
        } else if ($openTag == 'foreachelse') {
            $compiler->popStack('foreachelse');
            $compiler->popStack('foreach');
            return '<?php endif ?>';
        }
        throw new CornException('[Template Error]出现无法匹配的结束标签foreach' . $compiler->getTemplateInfo(), 1025);
    }



    /**
     * for循环
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function forTag(&$compiler, $tagStr)
    {
        $compiler->pushStack('for');
        return '<?php ' . self::stripWhiteSpace('for' . self::checkTagIntegrity($tagStr)) . ' ?>';
    }

    /**
     * for结束标签
     *
     * @param  Compiler $compiler
     * @return string
     * @throws CornException
     */
    public static function forEndTag(&$compiler)
    {
        if ($compiler->popStack('for') == false) {
            throw new CornException('[Template Error]出现无法匹配的结束标签for' . $compiler->getTemplateInfo(), 1025);
        }
        return '<?php endfor; ?>';
    }



    /**
     * while循环
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function whileTag(&$compiler, $tagStr)
    {
        $compiler->pushStack('while');
        return '<?php ' . self::stripWhiteSpace('while' . self::checkTagIntegrity($tagStr)) . ' ?>';
    }

    /**
     * while结束标签
     *
     * @param  Compiler $compiler
     * @return string
     * @throws CornException
     */
    public static function whileEndTag(&$compiler)
    {
        if ($compiler->popStack('while') == false) {
            throw new CornException('[Template Error]出现无法匹配的结束标签for' . $compiler->getTemplateInfo(), 1025);
        }
        return '<?php endwhile; ?>';
    }



    /**
     * break
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function breakTag(&$compiler)
    {
        return '<?php break;?>';
    }

    /**
     * continue
     *
     * @param  Compiler $compiler
     * @return string
     */
    public static function continueTag(&$compiler)
    {
        return '<?php continue;?>';
    }



    /**
     * 不缓存部分开始标签
     */
    public static function nocacheTag()
    {
        return '<!-- Peas: noCache begin -->';
    }

    /**
     * 不缓存部分结束标签
     */
    public static function nocacheEndTag()
    {
        return '<!-- Peas: noCache end -->';
    }



    /**
     * include标签，载入模板文件
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr 参数：file必填，模板文件地址，可以使用变量，其它参数将作为要载入的模板文件变量传入
     * @return string
     * @throws CornException
     */
    public static function includeTag(&$compiler, $tagStr)
    {
        $attrs = $compiler->getParams($tagStr);
        if (!array_key_exists('file', $attrs) || $attrs['file'] == '') {
            throw new CornException('[Template Error]include标签缺少file参数' . $compiler->getTemplateInfo(), 1026);
        }
        $file = $attrs['file'];
        unset($attrs['file']);
        $oldParamStr = '';
        $newParamStr = '';
        foreach ($attrs as $key => $val) {
            $oldParamStr .= '$peas[\'includeParam\'][\'' . $key . '\'] = isset($' . $key . ') ? $' . $key . ' : \'\';$' . $key . '="' . $val . '";';
            $newParamStr .= '$' . $key . '=$peas[\'includeParam\'][\'' . $key . '\'];';
        }
        if (!empty($newParamStr)) {
            $newParamStr .= 'unset($peas[\'includeParam\']);';
        }
        return '<?php ' . $oldParamStr . 'include $peas[\'template\']->_compile(\'' . $file . '\');' . $newParamStr . ' ?>';
    }



    /**
     * layout标签
     * 参数：name 布局文件模板地址
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return ''
     */
    public static function layoutTag(&$compiler, $tagStr)
    {
        $attrs = $compiler->getParams($tagStr);
        if (!empty($attrs['name'])) {
            $compiler->layout = $attrs['name'];
        }
        return '';
    }

    /**
     * layoutHolder标签，布局模块
     * 参数：name 布局模块名称
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string   如<!-- __CORN_LAYOUT_HOLDER_BASE__ -->
     */
    public static function layoutHolderTag(&$compiler, $tagStr)
    {
        $attrs = $compiler->getParams($tagStr);
        $name = empty($attrs['name']) ? 'BASE' : strtoupper($attrs['name']);
        return '<!-- __CORN_LAYOUT_HOLDER_' . $name . '__ -->';
    }

    /**
     * layoutContent标签，布局内容
     * 参数：name 布局模块名称
     *
     * @param  Compiler $compiler
     * @param  string   $tagStr
     * @return string   如<!-- __CORN_LAYOUT_CONTENT_BEGIN_BASE__ -->
     */
    public static function layoutContentTag(&$compiler, $tagStr)
    {
        $attrs = $compiler->getParams($tagStr);
        $name = empty($attrs['name']) ? 'BASE' : strtoupper($attrs['name']);
        array_push($compiler->layoutBlock, $name);
        return '<!-- __CORN_LAYOUT_CONTENT_BEGIN_' . $name . '__ -->';
    }

    /**
     * layoutContent结束标签
     *
     * @return string '<!-- __CORN_LAYOUT_CONTENT_END__ -->'
     */
    public static function layoutContentEndTag()
    {
        return '<!-- __CORN_LAYOUT_CONTENT_END__ -->';
    }



    /**
     * 检查语句是否为'(语句):'的形式，不是则处理为此形式
     *
     * @param  string $tagStr
     * @return string '(语句):'形式的语句
     */
    public static function checkTagIntegrity($tagStr)
    {
        $tagStr = trim($tagStr);
        if ($tagStr{strlen($tagStr) - 1} == ':') {
            $tagStr = trim(substr($tagStr, 0, -1));
        }
        if ($tagStr{0} != '(') {
            $tagStr = '(' . $tagStr;
        }
        if ($tagStr{strlen($tagStr) - 1} != ')') {
            $tagStr = $tagStr . ')';
        }
        return $tagStr . ':';
    }

    /**
     * 去除php代码中的空白和注释
     *
     * @param  string $content 需要处理的代码
     * @return string 处理后的代码
     */
    public static function stripWhiteSpace($content)
    {
        $stripStr  = '';
        $tokens    = token_get_all('<?php ' . trim($content));
        $lastSpace = false;
        for ($i = 0, $j = count($tokens); $i < $j; $i ++) {
            if (is_string($tokens[$i])) {
                $lastSpace = true;
                $stripStr  = rtrim($stripStr) . $tokens[$i];
            } else if ($tokens[$i][0] == T_WHITESPACE && !$lastSpace) {
                $stripStr .= ' ';
                $lastSpace = true;
            } else if (!($tokens[$i][0] == T_COMMENT || $tokens[$i][0] == T_DOC_COMMENT || $tokens[$i][0] == T_WHITESPACE)) {
                $lastSpace = false;
                $stripStr .= $tokens[$i][1];
            }
        }
        return substr($stripStr, 6);
    }
}
