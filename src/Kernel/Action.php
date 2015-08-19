<?php
namespace Peas\Kernel;

/**
 * Peas Framework
 *
 * 控制器基类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Action
{
    /**
     * 模板引擎实例
     *
     * @var CornTemplate
     */
    private static $_templateInstance = NULL;


    /**
     * 模板引擎实例
     *
     * @var Peas_System_Template_Interface
     */
    protected $_t = NULL;

    /**
     * 当前Action名，不包含Action前缀的
     *
     * @var string
     */
    protected $_actionName = '';

    /**
     * 当前正在访问的方法名
     *
     * @var string
     */
    protected $_methodName = '';

    /**
     * 当前正在访问的方法的默认模板文件地址
     *
     * @var string
     */
    protected $_templatePath = '';

    /**
     * 默认缓存标记
     *
     * @var string
     */
    protected $_cacheId = '';

    /**
     * 缓存有效期设置
     *
     * @var array 格式：'方法名=>缓存时间'
     */
    protected $_cacheLife = array();

    /**
     * $_GET + 私有GET参数，通过Peas_Function::runUrl执行的可能产生私有参数
     *
     * @var array
     */
    private $_get = array();


    /**
     * Warning：系统自动调用方法，应用中请勿使用，请使用Peas_Function::runUrl替代
     * 访问当前action的方法，设置了缓存且有效时直接读取缓存，缓存无效时，该方法存在则执行请求的方法
     *
     * @param  string  $actionName 控制器名称
     * @param  string  $methodName 方法名称
     * @param  bollean $isMethod   是否为方法调用
     * @return void
     */
    public function _run($actionName, $methodName, $isMethod)
    {
        $this->_actionName = $actionName;
        $this->_methodName = $methodName;

        // GET和私有参数合并
        $this->_get = $_GET;
        if (!empty($_GET[$actionName . '.' . $methodName])) {
            $privateGet = $_GET[$actionName . '.' . $methodName];
            $this->_get = array_merge($_GET, $privateGet);
        }

        // 初始化模板引擎
        $this->_t = self::getTemplateInstance();
        $this->_cacheId = substr(md5($_SERVER['REQUEST_URI']), 8, 16);
        $this->_templatePath = str_replace('_', '/', $this->_actionName) . '.' . $this->_methodName . '.php';

        // 调用初始化方法
        if (method_exists($this, '_init')) {
        	$this->_init();
        }

        // 检查是否可以直接读取缓存
        if (!empty($this->_t)) {
            if (isset($this->_cacheLife[$this->_methodName])) {
                if ($this->_t->isCached($this->_templatePath, $this->_cacheId, $this->_cacheLife[$this->_methodName])) {
                    return $this->_t->display($this->_templatePath, $this->_cacheId, $this->_cacheLife[$this->_methodName]);
                }
            } else if ($this->_t->isCached($this->_templatePath, $this->_cacheId)) {
                return $this->_t->display($this->_templatePath, $this->_cacheId);
            }
        }
        // 执行方法
        if ($isMethod) {
            $this->$methodName();

        // 方法不存在则直接加载模板，GET参数、私有参数、POST参数作为模板变量
        } else {
            $this->_assign($this->_get);
            $this->_assign($_POST);
            $this->_display();
        }
        // debug状态获取定义的模板变量
        if (_MODE == 'debug') {
            Peas_Debug::$vars[$actionName . ' : ' . $methodName] = $this->_getAssign();
        }
    }

    /**
     * 加载模板
     *
     * @param  string $template 模板地址，为空表示加载当前方法默认模板
     * @param  string $cacheId  cacheID，不指定则为系统默认
     * @param  int    $cacheLife 缓存有效期（秒），不指定则使用系统默认配置，如果模板地址和本参数同时未设置，则自动检查默认模板配置的缓存有效期
     * @return void
     */
    protected function _display($template = '', $cacheId = NULL, $cacheLife = NULL)
    {
        $this->_assign('_g', Peas_System_Application::_globalVar());
        if (empty($template)) {
            $template = $this->_templatePath;
            $cacheLife = (isset($this->_cacheLife[$this->_methodName]) && $cacheLife === NULL) ? $this->_cacheLife[$this->_methodName] : $cacheLife;
        }
        $cacheId = $cacheId === NULL ? $this->_cacheId : $cacheId;
        $cacheLife === NULL ? $this->_t->display($template, $cacheId) : $this->_t->display($template, $cacheId, $cacheLife);
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
        $this->_t->assign($varName, $varValue);
    }

    /**
     * 清空所有模板变量
     *
     * @return void
     */
    protected function _clearAllAssign()
    {
        $this->_t->clearAllAssign();
    }

    /**
     * 获取模板变量
     *
     * @param  string $varName 变量名，为空时表示获取所有
     * @return mixed 当前模板变量数组或者单个变量的值
     */
    protected function _getAssign($varName = '')
    {
        return $this->_t->getAssign($varName);
    }

    /**
     * 清空模板变量
     *
     * @param  string|array $varName 传入数组表示批量删除
     * @return void
     */
    protected function _clearAssign($varName)
    {
        $this->_t->clearAssign($varName);
    }

    /**
     * 获取$_GET、$_POST参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为GP，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return mixed
     */
    protected function _getParam($paramName, $defaultVal = NULL, $type = 'GP')
    {
        $type = strtoupper($type);
        if (array_key_exists($paramName, $this->_get)) {
            if ($type == 'G' || $type == 'GP') {
                return $this->_get[$paramName];
            } else if ($type == 'PG') {
                $defaultVal = $this->_get[$paramName];
            }
        }
        return (array_key_exists($paramName, $_POST) && $type != 'G') ? $_POST[$paramName] : $defaultVal;
    }

    /**
     * 获取int类型参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为GP，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return int 使用intval处理过的结果
     */
    protected function _getInt($paramName, $defaultVal = NULL, $type = 'GP')
    {
    	return intval($this->_getParam($paramName, $defaultVal, $type));
    }

    /**
     * 获取float类型参数
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为GP，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return float 使用floatval处理过的结果
     */
    protected function _getFloat($paramName, $defaultVal = NULL, $type = 'GP')
    {
    	return floatval($this->_getParam($paramName, $defaultVal, $type));
    }

    /**
     * 获取字符串类型参数
     * 返回值使用htmlspecialchars处理，处理的字符含：
     * & （和号） 成为 &amp;
     * " （双引号） 成为 &quot;
     * ' （单引号） 成为 &#039;
     * < （小于） 成为 &lt;
     * > （大于） 成为 &gt;
     *
     * @param  string $paramName  参数名
     * @param  mixed  $defaultVal 默认值，即没有设定时返回的值
     * @param  string $type       获取类型，默认为GP，'P' or 'G' or 'PG' or 'GP'，P代表$_POST，G代表$_GET，'PG'、'GP'后面的优先级高
     * @return string
     */
    protected function _getString($paramName, $defaultVal = NULL, $type = 'GP')
    {
        return htmlspecialchars($this->_getParam($paramName, $defaultVal, $type), ENT_QUOTES);
    }


    /**
     * 获取模板引擎实例
     *
     * @return Peas_System_Template_Interface
     */
    public static function getTemplateInstance()
    {
        if (self::$_templateInstance !== NULL) {
            return self::$_templateInstance;
        }
        defined('TEMPLATE_THEME') or define('TEMPLATE_THEME', NULL);

        $templateClassName = Peas_System_Application::_getConfig('_template.class');
        $templateClassName = empty($templateClassName) ? 'Peas_Grass' : $templateClassName;

        if (!class_exists($templateClassName)) {
            return NULL;
        }
        $template = new $templateClassName();
        if (TEMPLATE_THEME != NULL) {
            $template->setTheme(TEMPLATE_THEME);
        }
        self::$_templateInstance = $template;
        return $template;
    }
}