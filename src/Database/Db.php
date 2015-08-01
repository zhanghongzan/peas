<?php
namespace Peas\Database;

/**
 * Peas Framework
 *
 * 数据库操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Db
{
    /**
     * $dbConfig = array(
     *     'link_0' => array(
     *
     *     ),
     *     'link_1' => array()
     * );
     *
     * @var unknown
     */
    public $a;

    public function __construct(array $config)
    {
    }

    public function getWriteLink()
    {}

    public function getReadLink()
    {
    }
}
