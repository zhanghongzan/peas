<?php
namespace Peas\Database;

use Peas\Database\Driver\DriverInterface;

/**
 * Peas Framework
 *
 * 数据库连接管理
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class DbLink
{
    /**
     * 配置参数
     *
     * @var array
     */
    private $_config = [];



    /**
     * 读取连接ID
     *
     * @var string
     */
    private $_rId = '';

    /**
     * 写入连接ID
     *
     * @var string
     */
    private $_wId = '';

    /**
     * 读取连接
     *
     * @var DriverInterface
     */
    private $_rLink = null;

    /**
     * 写入连接
     *
     * @var DriverInterface
     */
    private $_wLink = null;



    /**
     * 缓存操作实例，若当前连接未启用缓存，则为null
     *
     * @var Peas\Cache\Cache
     */
    private $_cache = null;



    /**
     * 分页：页码
     *
     * @var false|int 不开启分页|页码
     */
    private $_page = false;

    /**
     * 开启分页时每页数量
     *
     * @var int
     */
    private $_pageSize = 10;

    /**
     * 当前查询是否开启缓存
     *
     * @var boolean
     */
    private $_ifCache = false;

    /**
     * 当前查询缓存有效期
     *
     * @var false|int 默认有效期|缓存有效期：秒（-1为永久）
     */
    private $_cacheLife = false;

    /**
     * 上一Query是否通过只读连接操作
     *
     * @var boolean
     */
    private $_isPrevQueryRead = true;



    /**
     * 初始化，设置连接配置
     *
     * @param array $config 数据库连接配置，如：[<br>
     *     'driver' => 'pdo',<br>
     *     'read'   => [[0, 40], [1, 60]],<br>
     *     'write'  => [[0, 50], [1, 50]],<br>
     *     'cache'  => [false, []],
     *     'db_0'   => []<br>
     * ]
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
        $this->_rId = self::_getDbid($config['read']);
        $this->_wId = self::_getDbid($config['write']);
    }


    /**
     * 缓存设置
     *
     * @param  false|int 默认有效期|缓存有效期：秒（-1为永久）
     * @return DbLink
     */
    public function cache($cacheLife = false)
    {
        $this->_ifCache   = true;
        $this->_cacheLife = $cacheLife;
        return $this;
    }

    /**
     * 分页设置，返回结果将为：[<br>
     *     'numRows'  => 数据总条数,<br>
     *     'page'     => 当前页码,<br>
     *     'pageSize' => 每页显示条数,<br>
     *     'result'   => 结果数据集<br>
     * ]
     *
     * @param  false|int $page     不开启分页|页码
     * @param  int       $pageSize 开启分页时每页数量
     * @return DbLink
     */
    public function page($page = false, $pageSize = 10)
    {
        $this->_page     = $page;
        $this->_pageSize = $pageSize;
        return $this;
    }


    /**
     * 获取结果数量
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 结果数量，执行失败返回false
     */
    public function count($sql, array $param = [])
    {
        return $this->_getNumAndCatchException($this->parseSql($sql, $param));
    }

    /**
     * 执行查询语句，获取数组形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为false）
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为false），true返回对象形式的结果，false返回数组形式的结果
     * @param  array   $resultMap   键名映射关系, ['字段名'=>'结果键名',...], 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @return mixed   成功返回单个结果或者结果集数组，失败返回false
     */
    public function selectForArray($sql, array $param = [], $isGetTop0 = false, array $resultMap = [])
    {
        return $this->_selectFactory($sql, $param, $isGetTop0, false, $resultMap);
    }

    /**
     * 执行查询语句，获取对象形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为false）
     * @param  string  $className   要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap 数据库字段名与object属性名映射关系，['字段名' => '属性名',...]
     * @return mixed   成功返回单个结果或者结果集数组，失败返回false
     */
    public function selectForObject($sql, array $param = [], $isGetTop0 = false, $className = '', array $classParam = [], array $propertyMap = [])
    {
        return $this->_selectFactory($sql, $param, $isGetTop0, true, [], $className, $classParam, $propertyMap);
    }

    /**
     * 执行插入语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 产生的AUTO_INCREMENT值，没有则为0，执行失败返回false
     */
    public function insert($sql, array $param = [])
    {
        try {
            $this->_isPrevQueryRead = false;
            return $this->_getWriteLink()->insert($this->parseSql($sql, $param));
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 执行更新语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public function update($sql, array $param = [])
    {
        try {
            $this->_isPrevQueryRead = false;
            return $this->_getWriteLink()->update($this->parseSql($sql, $param));
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 执行删除语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public function delete($sql, array $param = [])
    {
        try {
            $this->_isPrevQueryRead = false;
            return $this->_getWriteLink()->delete($this->parseSql($sql, $param));
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }


    /**
     * 执行SQL语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public function execute($sql, array $param = [])
    {
        try {
            $this->_isPrevQueryRead = false;
            return $this->_getWriteLink()->execute($this->parseSql($sql, $param));
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 启动事务
     *
     * @return boolean 是否启动成功
     */
    public function startTrans()
    {
        return $this->_getWriteLink()->startTrans();
    }

    /**
     * 事务回滚
     *
     * @return boolean 是否执行成功
     */
    public function rollback()
    {
        try {
            return $this->_getWriteLink()->rollback();
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 提交事务
     *
     * @return boolean 是否提交成功
     */
    public function commit()
    {
        try {
            return $this->_getWriteLink()->commit();
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 获取最近一次执行的SQL语句
     *
     * @return string 最近一次执行的SQL语句
     */
    public function getSql()
    {
        return $this->_isPrevQueryRead ? $this->_getReadLink()->getSql() : $this->_getWriteLink()->getSql();
    }

    /**
     * 获取最近的错误信息
     *
     * @return string 最近的错误信息
     */
    public function getError()
    {
        return $this->_isPrevQueryRead ? $this->_getReadLink()->getError() : $this->_getWriteLink()->getError();
    }

    /**
     * 释放查询结果
     *
     * @return void
     */
    public function free()
    {
        if ($this->_rLink != null) {
            $this->_rLink->free();
        }
        if ($this->_rId != $this->_wId && $this->_wLink != null) {
            $this->_wLink->free();
        }
    }

    /**
     * 关闭连接
     *
     * @return boolean 是否关闭成功
     */
    public function close()
    {
        try {
            if ($this->_rLink != null) {
                $this->_rLink->close();
                $this->_rLink = null;

                if ($this->_rId == $this->_wId) {
                    $this->_wLink = null;
                }
            }
            if ($this->_wLink != null) {
                $this->_wLink->close();
                $this->_wLink = null;
            }
            return true;
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * SQL安全过滤
     *
     * @param  string $str 需要过滤的字符串
     * @return string
     */
    public function escapeString($str)
    {
        return $this->_getReadLink()->escapeString($str);
    }

    /**
     * 解析SQL语句，用参数替换SQL语句中的参数位<br>
     * 表名格式：##tableName##<br>
     * 参数格式：#paramName:type#<br>
     * #号字符串为\#<br>
     *
     * @param  string $sql   SQL语句
     * @param  mixed  $param 参数
     * @return string 解析后的Sql语句
     */
    public function parseSql($sql, $param)
    {
        return self::_doParseSql($sql, $param, $this->_config['prefix'], $this->_getReadLink());
    }



    /**
     * 获取当前缓存管理实例
     *
     * @return Peas\Cache\Cache
     */
    public function getCacheInstance()
    {
        return $this->_cache;
    }



    /**
     * 初始化临时查询设置
     *
     * @return void
     */
    private function _selectStatusInit()
    {
        $this->_ifCache   = false;
        $this->_cacheLife = false;
        $this->_page      = false;
        $this->_pageSize  = 10;
    }

    /**
     * 执行查询语句
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为false）
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为false），true返回对象形式的结果，false返回数组形式的结果
     * @param  array   $resultMap   $isGetObject为false时有效，键名映射关系, ['字段名'=>'结果键名',...], 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @param  string  $className   $isGetObject为true时有效，要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  $isGetObject为true时有效，可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap $isGetObject为true时有效，数据库字段名与object属性名映射关系，['字段名' => '属性名',...]
     * @return mixed   成功返回单个结果或者结果集数组，失败返回false
     */
    private function _selectFactory($sql, array $param = [], $isGetTop0 = false, $isGetObject = false, array $resultMap = [], $className = '', array $classParam = [], array $propertyMap = [])
    {
        // 启用了缓存
        if ($this->_ifCache && $this->_cache != null) {
            $idKey = $isGetObject ? array($sql, $param, $isGetTop0, $className, $classParam, $propertyMap, $this->_page, $this->_pageSize) : array($sql, $param, $isGetTop0, $resultMap, $this->_page, $this->_pageSize);
            $cacheId = md5($idKey);
            $result = $this->_cache->get($cacheId);
            if ($result !== false) {
                $this->_selectStatusInit();
                return $result;
            }
        }
        $sql = $this->_parseSql($sql, $param);
        if ($isGetTop0) {
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
            $result = $selectResult === false ? false : (isset($selectResult[0]) ? $selectResult[0] : null);
        } else if ($this->_page !== false) {
            $numRows = $this->_getNumAndCatchException($sql);
            if ($numRows > 0 && $this->_pageSize * ($this->_page - 1) >= $numRows) {
                $this->_page = ceil($numRows / $this->_pageSize);
            }
            $sql .= ' limit ' . ($this->_pageSize * ($this->_page - 1)) . ',' . $this->_pageSize;
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
            $result = $selectResult === false ? false : [
                'numRows'  => $numRows,
                'page'     => $this->_page,
                'pageSize' => $this->_pageSize,
                'result'   => $selectResult
            ];
        } else {
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
        }

        // 记录缓存
        if ($this->_ifCache && $this->_cache != null) {
            $this->_cache->set($cacheId, $result, $this->_cacheLife);
        }
        $this->_selectStatusInit();
        return $result;
    }

    /**
     * 执行获取结果数量
     *
     * @param  string    $sql 已经经过解析的SQL语句
     * @return int|false 结果数量，执行失败返回false
     */
    private function _getNumAndCatchException($sql)
    {
        try {
            $this->_isPrevQueryRead = true;
            return $this->_getReadLink()->getNumRows($sql);
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }

    /**
     * 执行查询
     *
     * @param  string        $sql         已经经过解析的SQL语句
     * @param  boolean       $isGetObject 是否获取对象形式的结果（默认为false），true返回对象形式的结果，false返回数组形式的结果
     * @param  array         $resultMap   $isGetObject为false时有效，键名映射关系, ['字段名'=>'结果键名',...], 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @param  string        $className   $isGetObject为true时有效，要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array         $classParam  $isGetObject为true时有效，可选的传递给$className对象的构造函数参数数组
     * @param  array         $propertyMap $isGetObject为true时有效，数据库字段名与object属性名映射关系，['字段名' => '属性名',...]
     * @return array|boolean 成功返回结果数组，失败返回false
     */
    private function _selectAndCatchException($sql, $isGetObject = false, array $resultMap = [], $className = '', array $classParam = [], array $propertyMap = [])
    {
        $this->_isPrevQueryRead = true;
        try {
            return $isGetObject ? self::_doSelectForObject($this->_getReadLink(), $sql, $className, $classParam, $propertyMap) : self::_doSelect($this->_getReadLink(), $sql, $resultMap);
        } catch (DbException $e) {
            $e->printTraceToLog();
            return false;
        }
    }



    /**
     * 获取读取连接
     *
     * @return DriverInterface
     */
    private function _getReadLink()
    {
        if ($this->_rLink) {
            return $this->_rLink;
        }
        $this->_rLink = self::_createLink($this->_config['driver'], $this->_config['db_' . $this->_rId]);
        if ($this->_rId == $this->_wId) {
            $this->_wLink = $this->_rLink;
        }
        return $this->_rLink;
    }

    /**
     * 获取写入连接
     *
     * @return DriverInterface
     */
    private function _getWriteLink()
    {
        if ($this->_wLink) {
            return $this->_wLink;
        }
        $this->_wLink = self::_createLink($this->_config['driver'], $this->_config['db_' . $this->_wId]);
        if ($this->_rId == $this->_wId) {
            $this->_rLink = $this->_wLink;
        }
        return $this->_wLink;
    }



    /**
     * 创建数据库连接
     *
     * @param  string $driver 驱动名称，系统支持'pdo'、'mysqli'、'sqlite'三种，支持自定义，类名需设置为Xxx或者XxxDriver，需要实现DriverInterface接口，并确保已经加载或者可以自动加载
     * @param  array  $config 连接配置参数
     * @return DriverInterface
     * @throws DbException 201
     */
    private static function _createLink($driver, array $config)
    {
        $className = ucfirst($driver);
        if (class_exists($className)) {
            return new $className($config);
        }
        throw new DbException('[DB]不支持' . $driver . '类型的数据库', 201);
    }

    /**
     * 获取数据库ID
     *
     * @param mixed $config 分布式数据库配置，如：[[数据库ID, 权重百分比], ...]，权重为1~100的整数，表示百分之几
     */
    private static function _getDbid($config)
    {
        if (!is_array($config)) {
            return $config;
        }
        $randNum = mt_rand(1, 100);
        $total = 0;
        foreach ($config as $item) {
            if ($randNum <= $item[1] + $total) {
                return $item[0];
            }
            $total += $item[1];
        }
        return $item[0];
    }


    /**
     * 执行查询语句，获取数组组成的结果集
     *
     * @param  DriverInterface $link      数据库连接
     * @param  string          $sql       SQL语句
     * @param  array           $resultMap 键名映射关系, ['字段名'=>'结果键名',...], 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @return array           数组形式的结果集数组
     * @throws DbException     204
     */
    private static function _doSelectForArray($link, $sql, array $resultMap = [])
    {
        $result = $link->select($sql);
        // 处理映射
        if (!empty($resultMap) && is_array($resultMap)) {
            foreach ($result as $key => $val) {
                if (is_array($val)) {
                    foreach ($resultMap as $mapKey => $mapVal) {
                        if (array_key_exists($mapKey, $val)) {
                            $val[$mapVal] = $val[$mapKey];
                            unset($val[$mapKey]);
                        }
                    }
                    $result[$key] = $val;
                }
            }
        }
        return $result;
    }

    /**
     * 执行查询语句，获取对象组成的结果集
     *
     * @param  DriverInterface $link        数据库连接
     * @param  string          $sql         已经解析过的SQL语句
     * @param  string          $className   要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array           $classParam  可选的传递给$className对象的构造函数参数数组
     * @param  array           $propertyMap 数据库字段名与object属性名映射关系，['字段名' => '属性名',...]
     * @return array           对象形式的结果集数组
     * @throws DbException     204
     */
    private static function _doSelectForObject($link, $sql, $className = '', array $classParam = [], array $propertyMap = [])
    {
        // 处理映射
        if (!empty($propertyMap)) {
            if (empty($className)) {
                $result = $link->selectForObject($sql, $className, $classParam);
                foreach ($result as $item) {
                    foreach ($propertyMap as $mapKey => $mapVal) {
                        if (property_exists($item, $mapKey)) {
                            $item->$mapVal = $item->$mapKey;
                            unset($item->$mapKey);
                        }
                    }
                }
            } else {
                $result = $link->selectForObject($sql);
                foreach ($result as $key => $val) {
                    $resultClassRef = new \ReflectionClass($className);
                    $resultClass = $resultClassRef->newInstanceArgs($classParam);
                    foreach ($propertyMap as $mapKey => $mapVal) {
                        if (property_exists($val, $mapKey)) {
                            $resultClass->$mapVal = $val->$mapKey;
                        }
                    }
                    $result[$key] = $resultClass;
                }
            }
            return $result;
        }
        return $link->selectForObject($sql, $className, $classParam);
    }

    /**
     * 解析SQL语句，用参数替换SQL语句中的参数位<br>
     * 表名格式：##tableName##<br>
     * 参数格式：#paramName:type#, type:'int'、'float'、'string'、'real'<br>
     * #号字符串为\#<br>
     *
     * @param  string          $sql    SQL语句
     * @param  mixed           $param  参数
     * @param  string          $prefix 表前缀
     * @param  DriverInterface $link   数据库连接
     * @return string          解析后的Sql语句
     */
    private static function _doParseSql($sql, $param, $prefix, $link)
    {
        //替换表名中得表前缀和表后缀占位符
        $sql = str_replace('\#', '_PEAS_SYSTEM_DB_WELL_NO_', $sql);
        $sql = preg_replace("/##([^#]*)##/i", $prefix . "\${1}", $sql);

        if (!is_array($param)) {
            $param = is_object($param) ? ((array) $param) : array('value' => $param);
        }
        $matches = array();
        preg_match_all("/#([^#^:]*)(:([^#^:]*))?#/i", $sql, $matches);
        if (!array_key_exists(0, $matches) || count($matches[0]) < 1) {
            return str_replace('_PEAS_SYSTEM_DB_WELL_NO_', '#', $sql);
        }
        $replace = array();
        foreach ($matches[0] as $key => $str) {
            $realType = strtolower($matches[3][$key]);
            $realVal  = array_key_exists($matches[1][$key], $param) ? $param[$matches[1][$key]] : $param['value'];

            if ($realType == 'int' || $realType == 'integer') {
                $replace[$str] = intval($realVal);
            } else if ($realType == 'float' || $realType == 'double') {
                $replace[$str] = floatval($realVal);
            } else if ($realType == 'string') {
                $replace[$str] = '\'' . $link->escapeString($realVal) . '\'';
            } else if ($realType == 'real') {
                $replace[$str] = $realVal;
            } else {
                $replace[$str] = is_string($realVal) ? '\'' . $link->escapeString($realVal) . '\'' : $realVal;
            }
        }
        foreach ($replace as $key => $val) {
            $sql = str_replace($key, $val, $sql);
        }
        return str_replace('_PEAS_SYSTEM_DB_WELL_NO_', '#', $sql);
    }
}
