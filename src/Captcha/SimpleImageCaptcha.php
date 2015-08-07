<?php
namespace Peas\Captcha;

use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * 验证码生成类，需要GD库支持
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class SimpleImageCaptcha
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_config = [
        'width'    => 120, // 宽度
        'height'   => 36,  // 高度
        'font'     => '',  // 指定的字体文件地址
        'fontSize' => 36,  // 指定字体大小
    ];

    /**
     * 图形资源句柄
     *
     * @var source
     */
    private $_img;


    /**
     * 初始化参数
     *
     * @param array $config 配置参数，可覆盖默认值中的一个或者多个，默认值：[<br>
     *     'width'    => 120, // 宽度<br>
     *     'height'   => 36,  // 高度<br>
     *     'font'     => '',  // 指定的字体文件地址<br>
     *     'fontSize' => 36,  // 指定字体大小<br>
     * ]
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 生成验证码
     *
     * @param  string $str 验证码内容
     * @return void
     */
    public function show($str = 'Peas')
    {
        $this->_createBg();
        $this->_createLine();
        $this->_createFont($str);
        $this->_outPut();
    }


    /**
     * 生成背景
     *
     * @return void
     */
    private function _createBg()
    {
        $this->_img = imagecreatetruecolor($this->getConfig('width'), $this->getConfig('height'));
        $color = imagecolorallocate($this->_img, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        imagefilledrectangle($this->_img, 0, $this->getConfig('height'), $this->getConfig('width'), 0, $color);
    }

    /**
     * 生成文字
     *
     * @param  string $code 文字
     * @return void
     */
    private function _createFont($code)
    {
        $codeLen = strlen($code);
        $_x = $this->getConfig('width') / $codeLen;
        for ($i = 0; $i < $codeLen; $i ++) {
            $fontColor = imagecolorallocate($this->_img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext($this->_img, $this->getConfig('fontSize'), mt_rand(- 30, 30), $_x * $i + mt_rand(2, 6), $this->getConfig('height') / 1.2, $fontColor, $this->getConfig('font'), $code[$i]);
        }
    }

    /**
     * 生成线条、雪花
     *
     * @return void
     */
    private function _createLine()
    {
        // 线条
        for ($i = 0; $i < 6; $i ++) {
            $color = imagecolorallocate($this->_img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->_img, mt_rand(0, $this->getConfig('width')), mt_rand(0, $this->getConfig('height')), mt_rand(0, $this->getConfig('width')), mt_rand(0, $this->getConfig('height')), $color);
        }
        // 雪花
        for ($i = 0; $i < 100; $i ++) {
            $color = imagecolorallocate($this->_img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->_img, mt_rand(1, 5), mt_rand(0, $this->getConfig('width')), mt_rand(0, $this->getConfig('height')), '*', $color);
        }
    }

    /**
     * 输出图片
     *
     * @return void
     */
    private function _outPut()
    {
        header('Content-type:image/png');
        imagepng($this->_img);
        imagedestroy($this->_img);
    }
}
