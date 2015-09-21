<?php
namespace Peas\Kernel;

use Peas\Kernel\System\ActionContext;
use Peas\Config\Configure;

/**
 * Peas Framework
 *
 * 控制器基类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Controller
{
    /**
     * 缓存有效期设置
     *
     * @var array 格式：'方法名=>缓存时间'
     */
    public $cacheLife = [];


    /**
     * 加载模板
     *
     * @param  string $template  模板地址，为空表示加载当前方法默认模板
     * @param  string $cacheId   cacheID，不指定则为系统默认
     * @param  int    $cacheLife 缓存有效期（秒），不指定则使用系统默认配置，如果模板地址和本参数同时未设置，则自动检查默认模板配置的缓存有效期
     * @return void
     */
    protected function _display($template = '', $cacheId = null, $cacheLife = null)
    {
        $this->_assign('_c', Configure::get());
        if (empty($template)) {
            $template = ActionContext::$view;
            $cacheLife = (isset($this->cacheLife[ActionContext::$method]) && $cacheLife === null) ? $this->cacheLife[ActionContext::$method] : $cacheLife;
        }
        $cacheId = $cacheId === null ? ActionContext::$cacheId : $cacheId;
        $cacheLife === null ? ActionContext::$template->display($template, $cacheId) : ActionContext::$template->display($template, $cacheId, $cacheLife);
    }

    /**
     * 输出响应字符串，在echo之前会设置header编码
     *
     * @param string $text
     */
    protected function _echo($text)
    {
        header('Content-Type: text/html; charset=' . Configure::get('_default.charset'));
        echo $text;
    }

    /**
     * 模板变量赋值，只有使用此函数赋值的变量才能够在模板中访问
     *
     * @param  string|array $varName  变量名称或者包含多个变量的数组，peas为系统保留名，不允许赋值
     * @param  string       $varValue 单个变量的值
     * @return void
     */
    protected function _assign($varName, $varValue = '')
    {
        ActionContext::$template->assign($varName, $varValue);
    }

    /**
     * 清空所有模板变量
     *
     * @return void
     */
    protected function _clearAllAssign()
    {
        ActionContext::$template->clearAllAssign();
    }

    /**
     * 获取模板变量
     *
     * @param  string $varName 变量名，为空时表示获取所有
     * @return mixed  当前模板变量数组或者单个变量的值
     */
    protected function _getAssign($varName = '')
    {
        return ActionContext::$template->getAssign($varName);
    }

    /**
     * 清空模板变量
     *
     * @param  string|array $varName 传入数组表示批量删除
     * @return void
     */
    protected function _clearAssign($varName)
    {
        ActionContext::$template->clearAssign($varName);
    }

    /**
     * 获取$_GET、$_POST参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为PG，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return mixed
     */
    protected function _getParam($paramName, $defaultVal = null, $type = 'PG')
    {
        $type = strtoupper($type);
        if (array_key_exists($paramName, $_GET)) {
            if ($type == 'G' || $type == 'GP') {
                return $_GET[$paramName];
            } else if ($type == 'PG') {
                $defaultVal = $_GET[$paramName];
            }
        }
        return (array_key_exists($paramName, $_POST) && $type != 'G') ? $_POST[$paramName] : $defaultVal;
    }

    /**
     * 获取int类型参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为PG，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return int    使用intval处理过的结果
     */
    protected function _getInt($paramName, $defaultVal = null, $type = 'PG')
    {
    	return intval($this->_getParam($paramName, $defaultVal, $type));
    }

    /**
     * 获取float类型参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为PG，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return float  使用floatval处理过的结果
     */
    protected function _getFloat($paramName, $defaultVal = null, $type = 'PG')
    {
    	return floatval($this->_getParam($paramName, $defaultVal, $type));
    }

    /**
     * 获取字符串类型参数，返回值使用htmlspecialchars处理，处理的字符含：<br>
     * & （和号） 成为 &amp;<br>
     * " （双引号） 成为 &quot;<br>
     * ' （单引号） 成为 &#039;<br>
     * < （小于） 成为 &lt;<br>
     * > （大于） 成为 &gt;<br>
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为PG，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return string
     */
    protected function _getString($paramName, $defaultVal = null, $type = 'PG')
    {
        return htmlspecialchars($this->_getParam($paramName, $defaultVal, $type), ENT_QUOTES);
    }
}
