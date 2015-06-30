<?php
namespace Peas\Captcha;

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
    /**
     * 宽度
     *
     * @var int
     */
    public $width = 120;

    /**
     * 高度
     *
     * @var int
     */
    public $height = 36;

    /**
     * 指定的字体文件地址
     *
     * @var string
     */
    public $font;

    /**
     * 指定字体大小
     *
     * @var int
     */
    public $fontSize = 36;


    /**
     * 图形资源句柄
     * @var source
     */
    private $_img;


    /**
     * 初始化参数
     *
     * @param array $config 参数名=>参数值，参数名与属性名相同
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
        if (empty($this->font) || !is_file($this->font)) {
            $this->font = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/resources/fonts/StrungPiano.ttf';
        }
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
        $this->_img = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->_img, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        imagefilledrectangle($this->_img, 0, $this->height, $this->width, 0, $color);
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
        $_x = $this->width / $codeLen;
        for ($i = 0; $i < $codeLen; $i ++) {
            $fontColor = imagecolorallocate($this->_img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext($this->_img, $this->fontSize, mt_rand(- 30, 30), $_x * $i + mt_rand(2, 6), $this->height / 1.2, $fontColor, $this->font, $code[$i]);
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
            imageline($this->_img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        // 雪花
        for ($i = 0; $i < 100; $i ++) {
            $color = imagecolorallocate($this->_img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->_img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
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
