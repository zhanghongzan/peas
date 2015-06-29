<?php
namespace Peas\Crypt;

/**
 * Peas Framework
 *
 * 填充模式
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Padding
{
    /**
     * 去除填充内容，适用于最后一位记录的是填充长度的情况
     *
     * @param  string $text 待处理的内容
     * @return string 去除填充后的内容
     */
    private static function _trimPadding($text)
    {
        $len = strlen($text);
        $c = $text[$len - 1];
        return substr($text, 0, $len - ord($c));
    }


    /**
     * PKCS5Padding填充模式
     *
     * @param  string $data 待填充的内容
     * @return string 填充完成的内容
     */
    public static function PKCS5Padding($data)
    {
        $padlen = 8 - (strlen($data) % 8);
        for ($i = 0; $i < $padlen; $i ++) {
            $data .= chr($padlen);
        }
        return $data;
    }

    /**
     * 去除PKCS5Padding填充的内容
     *
     * @param  string $text 待处理的内容
     * @return string 去除填充后的内容
     */
    public static function trimPKCS5Padding($text)
    {
        return self::_trimPadding($text);
    }


    /**
     * PKCS7Padding填充模式
     *
     * @param  string $data      待填充的内容
     * @param  int    $blockSize 数据块长度，默认为8
     * @return string 填充完成的内容
     */
    public static function PKCS7Padding($data, $blockSize = 8)
    {
        $padlen = $blockSize - (strlen($data) % $blockSize);
        for ($i = 0; $i < $padlen; $i ++) {
            $data .= chr($padlen);
        }
        return $data;
    }

    /**
     * 去除PKCS7Padding填充的内容
     *
     * @param  string $text 待处理的内容
     * @return string 去除填充后的内容
     */
    public static function trimPKCS7Padding($text)
    {
        return self::_trimPadding($text);
    }


    /**
     * NoPadding填充模式，无填充，不做任何处理，直接返回
     *
     * @param  string $data
     * @return string
     */
    public static function NoPadding($data)
    {
        return $data;
    }

    /**
     * 去除NoPadding填充的内容，因为本身无任何填充，所以不做任何处理，直接返回
     *
     * @param  string $text
     * @return string
     */
    public static function trimNoPadding($text)
    {
        return $text;
    }


    /**
     * ISO10126Padding填充模式
     *
     * @param  string $data      待填充的内容
     * @param  int    $blockSize 数据块长度，默认为8
     * @return string 填充完成的内容
     */
    public static function ISO10126Padding($data, $blockSize = 8)
    {
        $padlen = $blockSize - (strlen($data) % $blockSize);
        for ($i = 0, $j = $padlen - 1; $i < $j; $i ++) {
            $data .= chr(mt_rand(0, 255));
        }
        $data .= chr($padlen);
        return $data;
    }

    /**
     * 去除trimISO10126Padding填充的内容
     *
     * @param  string $text 待处理的内容
     * @return string 去除填充后的内容
     */
    public static function trimISO10126Padding($text)
    {
        return self::_trimPadding($text);
    }
}
