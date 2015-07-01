<?php
namespace Peas\View;

use Peas\View\Compiler\Compiler;

/**
 * Peas Framework
 *
 * Corn模板引擎
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class CornTemplate
{
    /**
     * 缓存有效期: 永不过期
     *
     * @var int
     */
    const CACHELIFE_FOREVER = -1;

    /**
     * 缓存有效期：使用默认配置
     *
     * @var int
     */
    const CACHELIFE_DEFAULT = 0;


    /**
     * 是否开启编译，不开启编译时须使用php标签
     *
     * @var boolean
     */
    private $_compiling = true;

    /**
     * 默认编译ID
     *
     * @var string
     */
    private $_compileId = null;

    /**
     * 编译器配置
     *
     * @var array
     */
    private $_compilerConfig = [];


    /**
     * 默认是否开启缓存
     *
     * @var boolean
     */
    private $_caching = false;

    /**
     * 默认缓存有效期（秒），-1表示永不过期
     *
     * @var int
     */
    private $_cacheLife = 3600;


    /**
     * 模板文件目录
     *
     * @var string
     */
    private $_templateDir = '';

    /**
     * 编译文件目录
     *
     * @var string
     */
    private $_compileDir = '';

    /**
     * 缓存文件目录
     *
     * @var string
     */
    private $_cacheDir = '';


    /**
     * 新建文件夹许可权限
     *
     * @var int
     */
    private $_dirMode = 0775;

    /**
     * 新建文件许可权限
     *
     * @var int
     */
    private $_fileMode = 0664;


    /**
     * 模板变量
     *
     * @var array
     */
    private $_vars = [];


    /**
     * 构造函数，初始化配置
     *
     * @param array $config 参数名=>参数值，参数名与属性名相同
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->_{$key} = $val;
            }
        }
        $baseDir = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $baseDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/peas-2.0';
        if (empty($this->_templateDir)) {
            $this->_templateDir = $baseDir . '/resources/views';
        }
        if (empty($this->_compileDir)) {
            $this->_compileDir = $baseDir . '/storage/framework/views/compile';
        }
        if (empty($this->_cacheDir)) {
            $this->_cacheDir = $baseDir . '/storage/framework/views/cache';
        }
    }


    /**
     * 设置模板主题
     *
     * @param  string $themeName 模板主题名称
     * @return void
     */
    public function setTheme($themeName = '')
    {
        if (!empty($themeName)) {
            $this->_compileId = $themeName;
            $this->_templateDir = $this->_templateDir . '/' . $themeName;
        }
    }

    /**
     * 模板变量赋值，只有使用此函数赋值的变量才能够在模板中访问
     *
     * @param  string|array $varName  变量名称或者包含多个变量的数组，peas为系统保留名，不允许赋值
     * @param  string       $varValue 单个变量的值
     * @return void
     */
    public function assign($varName, $varValue = '')
    {
        if (is_array($varName)) {
            foreach ($varName as $name => $value) {
                if ($name != '' && $varName != 'peas') {
                    $this->_vars[$name] = $value;
                }
            }
        } elseif ($varName != '' && $varName != 'peas') {
            $this->_vars[$varName] = $varValue;
        }
    }

    /**
     * 获取模板变量
     *
     * @param  string $varName 变量名，为空时表示获取所有
     * @return mixed 当前模板变量数组或者单个变量的值，不存在返回null
     */
    public function getAssign($varName = '')
    {
        return $varName == '' ? $this->_vars : (array_key_exists($varName, $this->_vars) ? $this->_vars[$varName] : null);
    }

    /**
     * 清除单个模板变量
     *
     * @param  string|array $varName 传入数组表示批量删除
     * @return void
     */
    public function clearAssign($varName)
    {
        if (is_array($varName)) {
            foreach ($varName as $currVar) {
                unset($this->_vars[$currVar]);
            }
        } else {
            unset($this->_vars[$varName]);
        }
    }

    /**
     * 清空所有模板变量
     *
     * @return void
     */
    public function clearAllAssign()
    {
        $peasVar = $this->_vars['peas'];
        $this->_vars = [];
        $this->_vars['peas'] = $peasVar;
    }


    /**
     * 检查模板文件是否存在
     *
     * @param  string $template 模板文件相对地址，不包括模板文件根目录地址
     * @return boolean 存在返回true，不存在返回false
     */
    public function templateExists($template)
    {
        return file_exists($this->_getFilePath($template, 'template'));
    }

    /**
     * 检查缓存文件是否存在
     *
     * @param  string $template  模板文件相对地址，不包括模板文件根目录地址
     * @param  string $cacheId   缓存标记，默认为空
     * @param  int    $cacheLife 缓存有效期（秒），默认为CornTemplate::CACHELIFE_DEFAULT，参考常量：CornTemplate::CACHELIFE_FOREVER(永不过期)、CornTemplate::CACHELIFE_DEFAULT(使用默认配置)
     * @return boolean 存在返回true，不存在返回false
     */
    public function isCached($template, $cacheId = null, $cacheLife = CornTemplate::CACHELIFE_DEFAULT)
    {
        if ($cacheLife == CornTemplate::CACHELIFE_DEFAULT && !$this->_caching) {
            return false;
        }
        return $this->_cacheExists($this->_getFilePath($template, 'cache', $cacheId), $cacheLife);
    }

    /**
     * 模板输出
     *
     * @param  string $template  模板文件相对地址，不包括模板文件根目录地址
     * @param  string $cacheId   缓存标记，默认为空
     * @param  int    $cacheLife 缓存有效期（秒），默认为CornTemplate::CACHELIFE_DEFAULT，参考常量：CornTemplate::CACHELIFE_FOREVER(永不过期)、CornTemplate::CACHELIFE_DEFAULT(使用默认配置)
     * @return void
     * @throws CornException 失败时抛出
     */
    public function display($template, $cacheId = null, $cacheLife = CornTemplate::CACHELIFE_DEFAULT)
    {
        $pathInfo = $this->_build($template, $cacheId, $cacheLife);
        if (!$pathInfo[0]) {
            extract($this->_vars, EXTR_OVERWRITE);
        }
        include $pathInfo[1];
    }

    /**
     * 获取模板渲染后的内容
     *
     * @param  string $template  模板文件相对地址，不包括模板文件根目录地址
     * @param  string $cacheId   缓存标记，默认为空
     * @param  int    $cacheLife 缓存有效期（秒），默认为CornTemplate::CACHELIFE_DEFAULT，参考常量：CornTemplate::CACHELIFE_FOREVER(永不过期)、CornTemplate::CACHELIFE_DEFAULT(使用默认配置)
     * @return string 模板渲染后的内容
     * @throws CornException 失败时抛出
     */
    public function fetch($template, $cacheId = null, $cacheLife = CornTemplate::CACHELIFE_DEFAULT)
    {
        $pathInfo = $this->_build($template, $cacheId, $cacheLife);
        if ($pathInfo[0]) {
            return file_get_contents($pathInfo[1]);
        }
        ob_start();
        extract($this->_vars, EXTR_OVERWRITE);
        include $pathInfo[1];
        return ob_get_clean();
    }

    /**
     * 检查更新编译文件，返回编译文件地址
     *
     * @param  string $template 模板文件相对地址
     * @return string 成功返回编译文件地址
     * @throws CornException 失败时抛出
     */
    public function compile($template)
    {
        $templatePath = $this->_getFilePath($template, 'template');
        if (!$this->_compiling) {
            return $templatePath;
        }
        $compilePath = $this->_getFilePath($template, 'compile');
        if (!file_exists($compilePath) || (filemtime($compilePath) < filemtime($templatePath))) {
            $compiler = new Compiler($this, $this->_compilerConfig);
            $out = $compiler->compile($templatePath);
            $makeDir = $this->_makeDir(dirname($compilePath));
            if (!$makeDir || file_put_contents($compilePath, $out) === false) {
                throw new CornException('[Template Error]编译文件[' . $compilePath . ']写入失败！', 102);
            }
        }
        return $compilePath;
    }



    /**
     * 检查创建文件目录，目录不存在时创建
     *
     * @param  string $dir 目录完整路径
     * @return boolean 成功返回true，失败返回false
     */
    private function _makeDir($dir)
    {
        if (is_dir($dir)) {
            return true;
        }
        $parentDir = $this->_makeDir(dirname($dir));
        if ($parentDir && @mkdir($dir, $this->_dirMode)) {
            @file_put_contents($dir . '/index.html', '<!-- Created by Corn Template, Created on ' . date('F Y h:i:s A') . ' -->');
            @chmod($dir . '/index.html', $this->_fileMode);
            return true;
        }
        return false;
    }

    /**
     * 获取文件地址
     *
     * @param  string $template  模板文件相对地址，不包括模板文件根目录地址
     * @param  string $fileType  文件类型，'template':模板文件， 'compile':编译文件， 'cache':缓存文件
     * @param  string $cacheId   缓存标记，默认为null
     * @return string 完整地址
     */
    private function _getFilePath($template, $fileType = 'template', $cacheId = null)
    {
        $template = trim($template, '/');
        if ($fileType == 'template') {
            return $this->_templateDir . '/' . $template;
        }
        $pathStr = (empty($this->_compileId) ? '' : $this->_compileId . '/') . substr($template, 0, strrpos($template, '.'));
        if ($fileType == 'compile') {
            return $this->_compileDir . '/' . $pathStr . '.cmp.php';
        } else {
            return $this->_cacheDir . '/' . $pathStr . (empty($cacheId) ? '' : '-' . $cacheId) . '.cac.php';
        }
    }

    /**
     * 检查指定缓存是否存在且有效
     *
     * @param  string $cachePath 缓存文件完整地址
     * @param  int    $cacheLife 缓存有效期（秒），参考常量：CornTemplate::CACHELIFE_FOREVER(永不过期)、CornTemplate::CACHELIFE_DEFAULT(使用默认配置)
     * @return boolean 存在返回true，不存在返回false
     */
    private function _cacheExists($cachePath, $cacheLife)
    {
        $cacheLifeInfo = $this->_getCacheLifeInfo($cacheLife);
        if (!$cacheLifeInfo[0] || !file_exists($cachePath)) {
            return false;
        }
        return ($cacheLifeInfo[1] == CornTemplate::CACHELIFE_FOREVER) || (filemtime($cachePath) + $cacheLifeInfo[1] > time());
    }

    /**
     * 写入缓存
     *
     * @param  string $cachePath   缓存文件地址
     * @param  string $content     写入的内容
     * @param  string $compilePath 编译文件地址
     * @return void
     * @throws CornException 失败时抛出
     */
    private function _bulidCache($cachePath, $content, $compilePath)
    {
        // 处理不缓存部分
        if (strpos($content, '<!-- Corn: noCache begin -->')) {
            $lDelimiter = '<!-- Corn: noCache begin -->';
            $rDelimiter = '<!-- Corn: noCache end -->';

            $match = [];
            preg_match_all("/$lDelimiter(.*?)$rDelimiter/s", file_get_contents($compilePath), $match);

            $tags = $match[1];
            $text = preg_split("/$lDelimiter(.*?)$rDelimiter/s", $content);

            $content = '';
            for ($i = 0, $tagsCount = count($tags); $i < $tagsCount; $i ++) {
                $content.= $text[$i] . $tags[$i];
            }
            $content.= $text[$i];
        }
        if (!$this->_makeDir(dirname($cachePath)) || file_put_contents($cachePath, $content) === false) {
            throw new CornException('[Template Error]缓存文件[' . $cachePath . ']写入失败！', 103);
        }
    }

    /**
     * 检查更新编译文件和缓存，不存在以及过期时更新
     *
     * @param  string $template  模板文件相对地址
     * @param  string $cacheId   缓存标记
     * @param  int    $cacheLife 缓存有效期（秒），参考常量：CornTemplate::CACHELIFE_FOREVER(永不过期)、CornTemplate::CACHELIFE_DEFAULT(使用默认配置)
     * @return array  array[0]:boolean（是否是缓存地址），array[1]:缓存地址或者编译文件地址
     * @throws CornException 失败时抛出
     */
    private function _build($template, $cacheId, $cacheLife)
    {
        // 首先检查缓存是否存在
        $cachePath = '';
        $cacheLifeInfo = $this->_getCacheLifeInfo($cacheLife);
        if ($cacheLifeInfo[0]) {
            $cachePath = $this->_getFilePath($template, 'cache', $cacheId);
            if ($this->_cacheExists($cachePath, $cacheLifeInfo[1])) {
                return [true, $cachePath];
            }
        }
        $this->_vars['peas']['template'] = $this; // 将当前模板引擎实例存入模板变量

        // 检查模板是否存在
        $templatePath = $this->_getFilePath($template, 'template');
        if (!file_exists($templatePath)) {
            throw new CornException('[Template Error]模板文件[' . $templatePath . ']未找到！', 101);
        }

        // 检查更新编译文件
        $compilePath = $this->compile($template);

        // 检查更新缓存
        if ($cacheLifeInfo[0]) {
            ob_start();
            extract($this->_vars, EXTR_OVERWRITE);
            include $compilePath;
            $this->_bulidCache($cachePath, ob_get_clean(), $compilePath);
            return [true, $cachePath];
        }
        return [false, $compilePath];
    }

    /**
     * 根据缓存有效期参数获取实际缓存有效期信息
     *
     * @param  int $cacheLife
     * @return array [是否启用缓存，缓存实际有效期秒数]
     */
    private function _getCacheLifeInfo($cacheLife)
    {
        if ($cacheLife == CornTemplate::CACHELIFE_DEFAULT) {
            $cacheLife = $this->_caching ? $this->_cacheLife : 0;
        }
        return [($cacheLife == CornTemplate::CACHELIFE_FOREVER || $cacheLife > 0) ? true : false, $cacheLife];
    }
}
