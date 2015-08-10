<?php
namespace Peas\Database;

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
     * 初始化，设置连接配置
     *
     * @param array $config 数据库连接配置，如：[<br>
     *     'driver' => 'pdo',<br>
     *     'read'  => [[0, 40], [1, 60]],<br>
     *     'write' => [[0, 50], [1, 50]],<br>
     *     'db_0'  => [<br>
     *         'dsn'       => 'mysql:dbname=peas;host=localhost',<br>
     *         'username'  => 'root',  // 用户名<br>
     *         'password'  => 'admin', // 密码<br>
     *         'charset'   => 'utf8',  // 数据库编码默认采用utf8<br>
     *         'pcconnect' => FALSE,   // 持久连接<br>
     *     ]<br>
     * ]
     */
    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    private function _getReadLink()
    {
        if ($this->_rLink) {
            return $this->_rLink;
        }
        $rId = self::_getDbid($this->_config['read']);
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
        throw new DbException('[DB]不支持' . $dbType . '类型的数据库', 201);
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
}
