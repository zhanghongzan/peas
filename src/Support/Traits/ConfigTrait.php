<?php
namespace Peas\Support\Traits;

/**
 * Peas Framework
 *
 * 属性$_config管理trait<br>
 * 如果需要设置默认值，可以定义$_defaultConfig属性，但是一定要调用initConfig方法初始化使默认参数能够生效，示例：<br>
 * private static $_defaultConfig = [];<br>
 * $this->initConfig($userConfig);<br>
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
trait ConfigTrait
{
    /**
     * 配置参数
     *
     * @var array
     */
    protected $_config = [];


    /**
     * 初始化配置参数，此方法将传入的参数与默认参数合并，可以定义$_defaultConfig属性作为默认参数，但是一定要调用此初始化方法，不然默认参数不会生效
     *
     * @param  array $config 自定义参数，覆盖默认参数
     * @return void
     */
    public function initConfig(array $config = [])
    {
        if (isset($this->_defaultConfig)) {
            $config = array_merge($this->_defaultConfig, $config);
        }
        $this->setConfig($config);
    }

    /**
     * 设置配置参数
     *
     * @param  string|array $name 参数名|含有多个参数的数组
     * @param  mixed $value 参数值，$name为参数名时有效
     * @return self
     */
    public function setConfig($name, $value = null)
    {
        if (is_null($name)) {
            $this->_config = $value;
        } elseif (is_array($name)) {
            $this->_config = array_merge($this->_config, $name);
        } else {
            $this->_config[$name] = $value;
        }
        return $this;
    }

    /**
     * 获取配置参数
     *
     * @param  string $name 参数名
     * @return mixed $name为空时返回所有参数，无此参数时返回null
     */
    public function getConfig($name = '')
    {
        return empty($name) ? $this->_config : (array_key_exists($name, $this->_config) ? $this->_config[$name] : null);
    }
}
