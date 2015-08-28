<?php
namespace Peas\View\Compiler;

use Peas\View\CornTemplate;
use Peas\View\CornException;

/**
 * Peas Framework
 *
 * 模板引擎编译类
 *
 * 注：以下{、}分别表示左右定界符<br>
 *
 * 定界符:<br>
 * {_L_}表示输出左定界符，{_R_}表示输出右定界符<br>
 *
 * 注释:<br>
 * 1. #号之后的所有内容，如：{这里可以写代码 #注释内容...}<br>
 * 2. 一对*号之间的内容，如：{这里可以写代码 *注释内容* 这里可以写代码}<br>
 * 说明：字符#和*用\#和\*表示<br>
 *
 * 标签属性：<br>
 * 标签名="标签值" || 标签名='标签值'<br>
 * 用双引号包裹时标签值里的双引号须转义\"<br>
 * 用单引号包裹时标签值里的单引号须转义\'<br>
 *
 * 输出变量：<br>
 * 等号开头表示输出<br>
 * 以'$'开头默认为变量输出，可以设置default属性表示该变量!isset时的默认值<br>
 * 如：{$test} {$test['name'] default="zhz"}<br>
 *
 * p标签：<br>
 * 标签内的内容作为php原生代码<br>
 * 如：{p 不含定界符的php代码}<br>
 *
 * php标签：<br>
 * 开始标签与结束标签之前的内容作为php原生代码<br>
 * {php}这里是php代码{/php}<br>
 *
 * literal标签：<br>
 * 标签区域内的数据将被当作文本处理，不进行任何解析<br>
 * 如：{literal}任意字符，包括{}定界符{/literal}<br>
 *
 * if、elseif、else标签：<br>
 * 语法同php，逻辑语句外围括号可写也可省略<br>
 * 如：<br>
 * {if($a > 0)}...{elseif $a<0}...{else}...{/if}<br>
 *
 * foreach、foreachelse标签<br>
 * 语法同php，语句外围括号可写也可省略，foreachelse标签处理遍历的数组empty时的情况<br>
 * 如：<br>
 * {foreach $test as $key=>$val}...{foreachelse}...{/foreach}<br>
 *
 * for标签：<br>
 * 语法同php，语句外围括号可写也可省略<br>
 * 如：<br>
 * {for (expr1; expr2; expr3)}...{/for} 或者 {for expr1; expr2; expr3}...{/for}<br>
 *
 * while标签：<br>
 * 语法同php，语句外围括号可写也可省略<br>
 * 如：<br>
 * {while (expr)}...{/while} 或者  {while expr}...{/while}<br>
 *
 * break、continue标签<br>
 * {break}、{continue}<br>
 *
 *
 *
 * 以上全部可以使用原生php替代，模板标签仅仅提供了另外一种形式而已，以下标签不同，每个标签都包含自己特殊的业务逻辑<br>
 *
 *
 * nocache标签<br>
 * 标签区域内的数据不进行缓存<br>
 *
 * include标签<br>
 * 载入模板文件，参数：file必填，模板文件地址，可以使用变量，其它参数将作为要载入的模板文件变量传入<br>
 * 如{include file="User/Index.test.php" test='test'}<br>
 *
 *
 *
 * 模板布局<br>
 * layout标签：<br>
 * 指定当前模板的布局模板<br>
 * 参数：name 布局文件模板地址（相对于模板根目录）<br>
 * 如：<br>
 * {layout name="layout.php"}<br>
 *
 * layoutHolder标签：<br>
 * 用于模板布局文件，表示布局空间<br>
 * 参数：name 布局空间名称<br>
 * 如：<br>
 * {layoutHolder name="test"}<br>
 *
 * layoutContent标签：<br>
 * 指定替换模板布局文件中指定布局空间的内容<br>
 * 参数：name 布局空间名称<br>
 * 如：<br>
 * {layoutContent name="test"}这里的内容将替换模板文件中的名为test的布局空间{/layoutContent}<br>
 *
 *
 *
 *
 * 标签扩展：<br>
 * 命名规范：<br>
 *     类名为标签名首字母大写 . 'Tag'，如：TestTag<br>
 * 需要实现的方法：<br>
 *     实现标签类接口：CornTagInterface<br>
 *
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Compiler
{
    /**
     * 左定界符
     *
     * @var string
     */
    public $lDelimiter = '{';

    /**
     * 右定界符
     *
     * @var string
     */
    public $rDelimiter = '}';

    /**
     * 是否压缩代码
     *
     * @var boolean
     */
    public $stripSpace = true;

    /**
     * 扩展包名，支持多个，但是单个也要以数组形式配置
     *
     * @var array
     */
    public $pluginPackage = [];


    /**
     * 当前运行的CornTemplate实例
     *
     * @var CornTemplate
     */
    public $template = null;


    /**
     * 当前解析的模板文件地址
     *
     * @var string
     */
    public $templatePath = '';

    /**
     * 当前编译内容所在行
     *
     * @var int
     */
    public $currentLine = 1;


    /**
     * 布局模板名称
     *
     * @var string
     */
    public $layout = '';

    /**
     * 模板块名称
     *
     * @var array
     */
    public $layoutBlock = [];

    /**
     * 记录已编译的开始标签
     *
     * @var array
     */
    public $symbolStack = [];


    /**
     * 初始化
     *
     * @param CornTemplate $template 创建该编译器的模板引擎实体
     * @param array        $config   参数名=>参数值，参数名与属性名相同
     */
    public function __construct(&$template, array $config = [])
    {
        $this->template = $template;
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }


    /**
     * 模板解析入口
     *
     * @param  string  $templatePath 模板文件地址
     * @return string  解析后的代码
     * @throws CornException 解析出现问题时抛出
     */
    public function compile($templatePath)
    {
        $this->layout = '';
        $this->layoutBlock = [];

        $this->templatePath = $templatePath;
        $resource = file_get_contents($templatePath);

        $this->_replacePhpBraces($resource, '/(' . $this->lDelimiter . '[\s]*php[\s]*'     . $this->rDelimiter . ')(.*?)(' . $this->lDelimiter . '[\s]*\/[\s]*php[\s]*'     . $this->rDelimiter . ')/s');
        $this->_replacePhpBraces($resource, '/(' . $this->lDelimiter . '[\s]*literal[\s]*' . $this->rDelimiter . ')(.*?)(' . $this->lDelimiter . '[\s]*\/[\s]*literal[\s]*' . $this->rDelimiter . ')/s');
        $this->_replacePhpBraces($resource, '/(<\?php)(.*?)(\?>)/s');

        $match = [];
        preg_match_all("/$this->lDelimiter(.*?)$this->rDelimiter/s", $resource, $match);

        $tags = $match[1];
        $text = preg_split("/$this->lDelimiter(.*?)$this->rDelimiter/s", $resource);
        $resource = '';

        // 解析标签
        $resultTags = [];
        for ($i = 0, $tagsCount = count($tags); $i < $tagsCount; $i ++) {
            $this->currentLine += substr_count($text[$i], "\n");
            $resultTags[$i] = $this->_parse($tags[$i], $text[$i]);
            $this->currentLine += substr_count($tags[$i], "\n");
        }
        // 连接结果
        for ($i = 0, $tagsCount = count($tags); $i < $tagsCount; $i ++) {
            $resource .= $text[$i] . $resultTags[$i];
        }
        if (array_key_exists($i, $text)) {
            $resource .= $text[$i];
        }

        // 处理布局模板
        if (!empty($this->layout)) {
            $resource = $this->_bulidLayout($resource);
        }
        $resource = $this->stripSpace ? str_replace([';?><?php ', '?><?php '], ';', trim(preg_replace(['~>\s+<~', '~>(\s+\n|\r)~'], ['><', '>'], $resource))) : $resource;
        return str_replace('__CORN_SYSTEM_TEMPLATE_RDELIMITER__', $this->rDelimiter, str_replace('__CORN_SYSTEM_TEMPLATE_LDELIMITER__', $this->lDelimiter, $resource));
    }

    /**
     * 将指定范围内的定界符转为占位符
     *
     * @param string $preg
     */
    private function _replacePhpBraces(&$content, $preg)
    {
        $content = preg_replace_callback(
            $preg,
            create_function('$matches', 'return $matches[1] . str_replace(\'' . $this->lDelimiter . '\', "__CORN_SYSTEM_TEMPLATE_LDELIMITER__", str_replace(\'' . $this->rDelimiter . '\', "__CORN_SYSTEM_TEMPLATE_RDELIMITER__", $matches[2])) . $matches[3];'),
            $content
        );
    }

    /**
     * 解析单个标签内容
     *
     * @param  string $tag  标签
     * @param  string $text $tag之前的一个块内容
     * @return string 标签解析后的代码
     */
    private function _parse($tag, &$text)
    {
        $tag = $this->_parseNotes($tag); // 清除注释并删除两端空白字符
        if (empty($tag)) {
            return '';
        } elseif ($tag{0} == '$') {     // 处理'$'开头部分，以'$'开头默认为变量输出
            return $this->_parseVar($tag);
        } elseif ($tag{0} == '=') {     // 处理'='开头部分，以'='开头为输出
            return $this->_parseVar(trim(substr($tag, 1)));
        } elseif ($tag{0} == '/') {     // 处理'/'开头的结束标签
            return $this->_parseEnd(trim(substr($tag, 1)), $text);
        } else {                         // 其它，作为起始标签处理
            return $this->_parseBegin($tag);
        }
    }

    /**
     * 清除注释以及两边的空白，注释形式:<br>
     * 1. #注释内容...<br>
     * 2. *注释内容*<br>
     * 使用\*和\#表示*和#字符<br>
     *
     * @param  string $tag 需要处理的字符串
     * @return string 处理完成的字符串
     */
    private function _parseNotes($tag)
    {
        $tag = preg_replace('/([^\\\\])\*((.*)[^\\\\]\*)|\*/i', '$1', ' ' . $tag);
        return trim(preg_replace('/([^\\\\])#(.*)/i', '$1', ' ' . $tag));
    }

    /**
     * 变量输出
     *
     * @param  string $tag
     * @return string 变量输出代码
     */
    private function _parseVar($tag)
    {
        $attrs = $this->getParams($tag);
        if (array_key_exists('default', $attrs)) {
            $tagArr = explode(' default', $tag);
            $tag = trim($tagArr[0]);
            return '<?php echo isset(' . $tag . ')?' . $tag . ':"' . $attrs['default'] . '";?>';
        }
        return '<?php echo ' . $tag . ';?>';
    }


    /**
     * 解析开始标签
     *
     * @param  string $tag 完整的标签内容
     * @return string 解析完的标签
     * @throws CornException
     */
    private function _parseBegin($tag)
    {
        $tagArr   = token_get_all('<?php ' . $tag);
        $beginTag = $tagArr[1][1];
        $tagStr   = trim(substr($tag, strlen($beginTag)));

        $libMethodName = $beginTag . 'Tag';
        if (method_exists('Peas\View\Compiler\Tags', $libMethodName)) {
            return Tags::$libMethodName($this, $tagStr);
        }
        $plugin = $this->_loadPlugin($libMethodName);
        if ($plugin !== false) {
            return $plugin->begin($this, $tagStr);
        }
        throw new CornException('[Template Error]出现无法识别的开始标签' . $beginTag . $this->getTemplateInfo(), 1021);
    }

    /**
     * 解析结束标签
     *
     * @param  string $closeTag  结束标签
     * @param  string $blockText 结束标签之前的内容
     * @return string 解析之后的内容
     * @throws CornException 解析失败时抛出
     */
    private function _parseEnd($closeTag, &$blockText)
    {
        $libMethodName = $closeTag . 'EndTag';
        if (method_exists('Peas\View\Compiler\Tags', $libMethodName)) {
            return Tags::$libMethodName($this, $blockText);
        }
        $plugin = $this->_loadPlugin($closeTag . 'Tag');
        if ($plugin !== false) {
            return $plugin->end($this, $blockText);
        }
        throw new CornException('[Template Error]出现无法识别的结束标签' . $closeTag . $this->getTemplateInfo(), 1022);
    }

    /**
     * 加载插件
     *
     * @param  string       $pluginName 插件名称
     * @return object|false 插件实例，加载失败返回false
     */
    private function _loadPlugin($pluginName)
    {
        $pluginName = ucfirst($pluginName);
        foreach ($this->pluginPackage as $item) {
            $pluginClassName = $item . '\\' . $pluginName;
            if (class_exists($pluginClassName, true)) {
                return new $pluginClassName();
            }
        }
        return false;
    }

    /**
     * 处理模板布局
     *
     * @param  string $source 解析完成的模板代码
     * @return string 嵌入布局之后的完整代码
     * @throws CornException 模板文件不存在时抛出
     */
    private function _bulidLayout($source) {
        if (!$this->template->templateExists($this->layout)) {
            throw new CornException('[Template Error]布局模板文件[' . $this->layout . ']未找到！', 101);
        }
        $layoutFilePath = $this->template->_compile($this->layout);
        $layoutContext  = file_get_contents($layoutFilePath);

        if (empty($this->layoutBlock)) {
            return str_replace('<!-- __CORN_LAYOUT_HOLDER_BASE__ -->', $source, $layoutContext);
        }
        foreach ($this->layoutBlock as $name) {
            $lDelimiter = '<!-- __CORN_LAYOUT_CONTENT_BEGIN_' . $name . '__ -->';
            $rDelimiter = '<!-- __CORN_LAYOUT_CONTENT_END__ -->';

            $match = [];
            preg_match_all("/$lDelimiter(.*?)$rDelimiter/s", $source, $match);
            if (isset($match[1][0])) {
                $layoutContext = str_replace('<!-- __CORN_LAYOUT_HOLDER_' . $name . '__ -->', trim($match[1][0]), $layoutContext);
            }
        }
        return $layoutContext;
    }


    /**
     * 在堆中添加开始标签
     *
     * @param  string $openTag 开始标签
     * @return void
     */
    public function pushStack($openTag)
    {
        array_push($this->symbolStack, [$openTag, $this->currentLine]);
    }

    /**
     * 根据结束标签删除开始标记
     *
     * @param  string $closeTag 结束标签
     * @return string 删除的标记,删除失败返回false
     */
    public function popStack($closeTag)
    {
        if (count($this->symbolStack) > 0) {
            list($openTag, $lineNo) = end($this->symbolStack);
            if ($closeTag == $openTag) {
                return array_pop($this->symbolStack);
            }
        }
        return false;
    }

    /**
     * 获取左右定界符
     *
     * return array ['左定界符', '右定界符']
     */
    public function getDelimiter()
    {
        return [$this->lDelimiter, $this->rDelimiter];
    }

    /**
     * 根据参数字符串提取参数
     *
     * @param  string $paramStr 参数字符串
     * @return array  已分离的参数，参数名为键名，参数值为值(已去掉外围双引号)
     */
    public function getParams($paramStr)
    {
        $matches = [];
        preg_match_all('/([^=^\s]+)[\s]*=[\s]*((\"((\\\\\")|[^\"])*\")|(\'((\\\\\')|[^\'])*\'))?\s/i', $paramStr . ' ', $matches);
        $attrs = [];
        foreach ($matches[1] as $id => $paramName) {
            $paramVal = trim($matches[2][$id]);
            $len = strlen($paramVal);
            if ($paramVal{0} == '"' && $paramVal{$len - 1} == '"') {
                $paramVal = substr($paramVal, 1, $len - 2);
                $attrs[trim($paramName)] = str_replace('\"', '"', $paramVal);
            } elseif ($paramVal{0} == "'" && $paramVal{$len - 1} == "'") {
                $paramVal = substr($paramVal, 1, $len - 2);
                $attrs[trim($paramName)] = str_replace("\\'", "'", $paramVal);
            }
        }
        return $attrs;
    }

    /**
     * 返回当前模板解析信息
     *
     * @return string
     */
    public function getTemplateInfo()
    {
        return '(' . $this->templatePath . ', Line ' . $this->currentLine . ')';
    }
}
