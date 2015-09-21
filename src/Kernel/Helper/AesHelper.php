<?php
namespace Peas\Kernel\Helper;

use Peas\Crypt\Aes;
use Peas\Config\Configure;

class AesHelper
{
    /**
     * cbc模式
     *
     * @var Aes
     */
    private static $_cbc = null;

    /**
     * ecb模式
     *
     * @var Aes
     */
    private static $_ecb = null;


    public static function ecbEncode()
    {}

    public static function ecbDecode()
    {}

    public static function cbcEncode()
    {}

    public static function cbcDecode()
    {}

    /**
     * 生成IV
     *
     * @return string
     */
    public static function createIv()
    {
        return substr(md5(microtime() . mt_rand()), mt_rand(0, 15), 16);
    }

    /**
     * 生成带时间戳的IV，6位随机字符串+10位当前时间戳
     *
     * @return string
     */
    public static function createTimeIv()
    {
        return substr(md5(microtime() . mt_rand()), mt_rand(0, 15), 6) . time();
    }


    /**
     * 获取默认cbc模式aes加密类实体
     *
     * @return \Peas\Crypt\Aes
     */
    private static function _getDefaultCbc()
    {
        if (!empty(self::$_cbc)) {
            return self::$_cbc;
        }
        self::$_cbc = new Aes(['mode' => 'cbc', 'defaultKey' => Configure::get('aes.pwd.df')]);
        return self::$_cbc;
    }

    /**
     * 获取默认ecb模式aes加密类实体
     *
     * @return \Peas\Crypt\Aes
     */
    private static function _getDefaultEcb()
    {
        if (!empty(self::$_ecb)) {
            return self::$_ecb;
        }
        self::$_ecb = new Aes(['defaultKey' => Configure::get('aes.pwd.df')]);
        return self::$_ecb;
    }
}
