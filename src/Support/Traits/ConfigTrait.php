<?php
namespace Peas\Support\Traits;

/**
 * Peas Framework
 *
 * 属性$_config管理trait
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
