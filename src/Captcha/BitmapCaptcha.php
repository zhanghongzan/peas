<?php
namespace Peas\Captcha;

use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * 验证码生成类，BMP图片，不需要GD库支持
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class BitmapCaptcha
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_config = [
        'width'            => 72,        // 图片宽度(像素)，含边框，需要为4的倍数
        'height'           => 24,        // 图片高度(像素)，含边框
        'fontColors'       => '',        // 字符颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000
        'borderWidth'      => 1,         // 边框宽度
        'borderColor'      => '#D8F8AC', // 边框颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000
        'backgroundColor'  => '#FFFFFF', // 背景颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000
        'deformLevel'      => 3,         // 变形程度，数值越大可产生的最大变形程度越大
        'deformComplexity' => false,     // 是否采用复杂形变规则
        'disturb'          => 0.05,      // 杂点产生概率，1为100%
        'disturbMaxLine'   => 5,         // 最多产生干扰线数量
    ];

    /**
     * 1 / $this->disturb
     *
     * @var int
     */
    private $_disturbNum = 0;

    /**
     * 每个字符点阵宽度
     *
     * @var int
     */
    private $_dotsMaxWidth  = 0;

    /**
     * 每个字符点阵高度
     *
     * @var int
     */
    private $_dotsMaxHeight = 0;

    /**
     * 点阵数据
     *
     * @var string
     */
    private $_dots = '';


    /**
     * 初始化参数
     *
     * @param array $config 配置参数，可覆盖默认值中的一个或者多个，默认值：[<br>
     *     'width'            => 72,        // 图片宽度(像素)，含边框，需要为4的倍数<br>
     *     'height'           => 24,        // 图片高度(像素)，含边框<br>
     *     'fontColors'       => '',        // 字符颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000<br>
     *     'borderWidth'      => 1,         // 边框宽度<br>
     *     'borderColor'      => '#D8F8AC', // 边框颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000<br>
     *     'backgroundColor'  => '#FFFFFF', // 背景颜色，为空时随机产生，多个用','分开，如：#FF0000,#000000,#666666,#FF0000<br>
     *     'deformLevel'      => 3,         // 变形程度，数值越大可产生的最大变形程度越大<br>
     *     'deformComplexity' => false,     // 是否采用复杂形变规则<br>
     *     'disturb'          => 0.05,      // 杂点产生概率，1为100%<br>
     *     'disturbMaxLine'   => 5,         // 最多产生干扰线数量<br>
     * ]
     * @param string $dotsFilePath 点阵文件地址，不指定则使用默认Tahoma字体24号点阵
     */
    public function __construct(array $config = [], $dotsFilePath = '')
    {
        $this->setConfig($config);
        $this->_dots = (!empty($dotsFilePath) && is_file($dotsFilePath)) ? include $dotsFilePath : self::_getDefaultDots();
    }

    /**
     * 输出图片
     *
     * @param  string $str 要输出的字符串
     * @return void
     */
    public function show($str = 'Peas')
    {
        // 保证图片总宽度为4的倍数，能够正常显示
        if ($this->getConfig('width') % 4 > 0) {
            $this->setConfig('width', $this->getConfig('width') - ($this->getConfig('width') % 4) + 4);
        }
        $this->_disturbNum = intval(1 / $this->getConfig('disturb'));

        $strLen = strlen($str);

        // 获取当前字符串的点阵信息
        $dotsInfo = $this->_getDots($str);
        $dots = $dotsInfo['dots'];

        $this->_dotsMaxHeight = $this->getConfig('height') - 2 * $this->getConfig('borderWidth');
        $this->_dotsMaxWidth  = intval(($this->getConfig('width') - 2 * $this->getConfig('borderWidth')) / $strLen);

        for ($i = 0; $i < $strLen; $i ++) {
            $dots[$i] = $this->_changeShape($dots[$i]); // 点阵补全（变形处理）
        }

        $lineNum = 0;
        for ($i = 0; $i < $this->getConfig('disturbMaxLine'); $i ++) {
            $lineNum += $this->_rand(0, 1);
        }
        $this->_writeBmp($this->_getFullDotsArray($dots, $strLen, $lineNum), $strLen, $lineNum);
        exit();
    }

    /**
     * 获取字符串的点阵数组
     *
     * @param  string $str 输出的字符串
     * @return array  当前要输出的字符串的点阵信息
     */
    private function _getDots($str)
    {
        $strDots  = [];
        $maxWidth = $maxHeight = 0;
        $strLen = strlen($str);
        for ($i = 0; $i < $strLen; $i ++) {
            $strDots[$i] = $this->_dots[$str[$i]];
            $maxWidth  = strlen($strDots[$i][0]) > $maxWidth  ? strlen($strDots[$i][0]) : $maxWidth;
            $maxHeight = count($strDots[$i])     > $maxHeight ? count($strDots[$i])     : $maxHeight;
        }
        return ['dots' => $strDots, 'maxWidth' => $maxWidth, 'maxHeight' => $maxHeight];
    }

    /**
     * 获取除边框外的绘图区域完整点阵
     *
     * @param  array $dots    字符点阵
     * @param  int   $strLen  字符数
     * @param  int   $lineNum 干扰线数
     * @return array
     */
    private function _getFullDotsArray($dots, $strLen, $lineNum)
    {
        $width  = $this->getConfig('width')  - 2 * $this->getConfig('borderWidth');
        $height = $this->getConfig('height') - 2 * $this->getConfig('borderWidth');

        /*计算需要填充的宽度*/
        $fillWidth = $width - $this->_dotsMaxWidth * $strLen;
        $fillLeft  = intval($fillWidth / 2);
        $fillRight = $fillWidth - $fillLeft;

        $result = [];
        for ($i = 0; $i < $this->_dotsMaxHeight; $i ++) {
            $result[$i] = [];
            for ($j = 0; $j < $fillLeft; $j ++) {
                $result[$i][] = 0;
            }
            for ($j = 0; $j < $strLen; $j ++) {
                for ($k = 0; $k < $this->_dotsMaxWidth; $k ++) {
                    $result[$i][] = $dots[$j][$i][$k] == 0 ? 0 : $j + 1;
                }
            }
            for ($j = 0; $j < $fillRight; $j ++) {
                $result[$i][] = 0;
            }
        }
        for ($i = 0; $i < $lineNum; $i ++) {
            $result = $this->_getLine(0 - ($i + 1), $result, $this->_rand(0, $width  - 1), $this->_rand(0, $height - 1), $this->_rand(0, $width  - 1), $this->_rand(0, $height - 1));
        }
        return $result;
    }

    /**
     * 生成干扰线
     *
     * @param  int   $num  干扰线编号
     * @param  array $dots 点阵数组
     * @param  int   $x1   起点x轴坐标
     * @param  int   $y1   起点y轴坐标
     * @param  int   $x2   终点x轴坐标
     * @param  int   $y2   终点y轴坐标
     * @return array
     */
    private function _getLine($num, $dots, $x1, $y1, $x2, $y2)
    {
        $stepLen   = (abs($y2 - $y1) + 1) / (abs($x2 - $x1) + 1); // x方向每前进1个单位y轴需要前进的单位数
        $waitLen   = $stepLen; // y轴等待绘制的单位数
        $finishLen = 0;        // y轴已经绘制的单位数

        if ($x1 > $x2) {
            $temp = $x1;
            $x1   = $x2;
            $x2   = $temp;
            $temp = $y1;
            $y1   = $y2;
            $y2   = $temp;
        }
        $way = ($y1 <= $y2) ? 1 : -1;
        for ($i = $x1; $i <= $x2; $i ++) {
            if ($waitLen < 1) {
                if (array_key_exists($i, $dots[$y1 + $finishLen]) && $dots[$y1 + $finishLen][$i] == 0) {
                    $dots[$y1 + $finishLen][$i] = $num;
                }
                $waitLen += $stepLen;
            } else {
                while ($waitLen > 1) {
                    if (array_key_exists($i, $dots[$y1 + $finishLen]) && $dots[$y1 + $finishLen][$i] == 0) {
                        $dots[$y1 + $finishLen][$i] = $num;
                    }
                    $finishLen = $finishLen + $way;
                    $waitLen --;
                }
                $waitLen += $stepLen;
            }
        }
        return $dots;
    }

    /**
     * 根据指定点阵输出图片
     *
     * @param  array $dots    完整的点阵
     * @param  int   $strLen  输出的字符串长度
     * @param  int   $lineNum 干扰线数量
     * @return void
     */
    private function _writeBmp($dots, $strLen, $lineNum)
    {
        $width = $this->getConfig('width');
        $borderWidth = $this->getConfig('borderWidth');

        /*获取使用的颜色信息*/
        $fontColors      = $this->_getColors($strLen, $this->getConfig('fontColors'), 0, 188);
        $backgroundColor = $this->_getColors(1, $this->getConfig('backgroundColor'), 236);
        $borderColor     = $this->_getColors(1, $this->getConfig('borderColor'));
        $disturbColor    = $this->_getColors($lineNum + 1, '', 66); // 多生成一个是为了保证返回的结果一定是数组，不然$lineNum为1时输出时还要判断

        /*计算需要填充的宽度*/
        $fillWidth = $width - 2 * $borderWidth - $this->_dotsMaxWidth * $strLen;
        $fillLeft  = intval($fillWidth / 2);
        $fillRight = $fillWidth - $fillLeft;

        /*BMP文件头、信息头*/
        header('Content-Type: image/bmp');
        echo "BM" . pack("V3", 54, 0, 54);
        echo pack("V3v2V*", 0x28, $width, $this->getConfig('height'), 1, 24, 0, 0, 0, 0, 255, 0);

        /*开启边框时显示下边框*/
        for ($i = 0; $i < $borderWidth; $i ++) {
            $this->_echoColorPoint($width, $borderColor);
        }
        for ($i = $this->_dotsMaxHeight - 1; $i >= 0; $i --) {
            $this->_echoColorPoint($borderWidth, $borderColor);
            for ($k = 0, $j = $width - 2 * $borderWidth; $k < $j; $k ++) {
                if ($dots[$i][$k] > 0) {
                    echo $fontColors[$dots[$i][$k] - 1];
                } elseif ($dots[$i][$k] == 0) {
                    echo ($this->_rand(0, $this->_disturbNum - 1) == 0) ? $this->_getColors(1, '', 66) : $backgroundColor;
                } else {
                    echo $disturbColor[abs($dots[$i][$k]) - 1];
                }
            }
            $this->_echoColorPoint($borderWidth, $borderColor);
        }
        /*开启边框时显示上边框*/
        for ($i = 0; $i < $borderWidth; $i ++) {
            $this->_echoColorPoint($width, $borderColor);
        }
    }

    /**
     * 输出一定数量的指定颜色的点
     *
     * @param  int    $num   要输出点的数量
     * @param  string $color 指定点的颜色
     * @return void
     */
    private function _echoColorPoint($num, $color)
    {
        for ($k = 0; $k < $num; $k ++) {
            echo $color;
        }
    }

    /**
     * 获取指定个数个颜色
     *
     * @param  int    $num       获取颜色数
     * @param  string $rgbColors 指定使用的颜色
     * @param  int    $randMin   随机生成颜色时RGB最小值
     * @param  int    $randMax   随机生成颜色时RGB最大值
     * @return array|string      包含颜色信息的数组或者单个颜色
     */
    private function _getColors($num, $rgbColors = '', $randMin = 0, $randMax = 255)
    {
        $color = [];
        if (empty($rgbColors)) {
            for ($i = 0; $i < $num; $i ++) {
                $color[$i] = chr($this->_rand($randMin, $randMax)) . chr($this->_rand($randMin, $randMax)) . chr($this->_rand($randMin, $randMax));
            }
        } else {
            $allowColor    = explode(',', $rgbColors);
            $allowColorNum = count($allowColor) - 1;
            for ($i = 0; $i < $num; $i ++) {
                $color[$i] = $this->_rgbToChr($allowColor[$this->_rand(0, $allowColorNum)]);
            }
        }
        return $num == 1 ? $color[0] : $color;
    }

    /**
     * 将RGB颜色字符串转换成可输出的颜色
     *
     * @param  string $rgb
     * @return void
     */
    private function _rgbToChr($rgb)
    {
        return chr(hexdec(substr($rgb, 5, 2))) . chr(hexdec(substr($rgb, 3, 2))) . chr(hexdec(substr($rgb, 1, 2)));
    }

    /**
     * 产生随机数
     *
     * @param  int $minNum 最小数
     * @param  int $maxNum 最大数
     * @return int 产生的随机数
     */
    private function _rand($minNum = 0, $maxNum = 9)
    {
        mt_srand((double)microtime() * 1000000);
        return mt_rand($minNum, $maxNum);
    }

    /**
     * 用于把指定字符点阵补全，通过不同的补全方式达到变形效果
     *
     * @param  array $dots 待补全的字符点阵
     * @return array 已补全的字符点阵
     */
    private function _changeShape($dots)
    {
        $dotsHeight = count($dots);
        $dotsWidth  = strlen($dots[0]);
        $newDots    = [];     // 用于存储新点阵
        $currentDotsLine = 0; // 记录新点阵当前操作的行数

        $upMoveNum   = $this->_rand(0, $this->_dotsMaxHeight - $dotsHeight); // 点阵上方补全行数
        $downMoveNum = $this->_dotsMaxHeight - $dotsHeight   - $upMoveNum;   // 点阵下方补全行数

        /*补全点阵上下方*/
        for ($i = 0; $i < $this->_dotsMaxHeight; $i ++) {
            if ($i < $upMoveNum || $i >= ($dotsHeight + $upMoveNum)) {
                $newDots[$i] = str_repeat('0', $this->_dotsMaxWidth);
            }
        }
        $maxMoveWidth = $this->_rand(0, $this->getConfig('deformLevel')); // 最大偏移量
        if ($maxMoveWidth + $dotsWidth > $this->_dotsMaxWidth) {
            $maxMoveWidth = $this->_dotsMaxWidth - $dotsWidth;
        }
        $spacingWidth = $this->_dotsMaxWidth - $dotsWidth - $maxMoveWidth;
        $frontBaseMoveNum = $this->_rand(0, $spacingWidth); // 左侧初始偏移量

        $moveXWay = $this->_rand(0, 1); // 初始X轴偏移方向，0向左，1向右
        $moveYWay = $this->_rand(0, 1); // 初始Y轴偏移方向，0向上，1向下

        // 初始偏移行
        $moveBeginLine = $this->_rand(0, $dotsHeight - 1);
        $moveBeginLine = $moveYWay == 0 ? $dotsHeight - 1 - $moveBeginLine : $moveBeginLine;

        $moveFrontNum = $moveBackNum = [];
        $currentMoveNum = 0;
        $currentMoveWay = 1;
        for ($i = 0; $i < $dotsHeight; $i ++) {
            if ($i < $moveBeginLine) {
                $moveFrontNum[$i] = 0;
            } elseif ($maxMoveWidth == 0) {
                $moveFrontNum[$i] = 0;
            } elseif ($this->getConfig('deformComplexity')) {
                $currentMoveWay = ($currentMoveNum == 0) ? 1 : (($currentMoveNum == $maxMoveWidth) ? -1 : $currentMoveWay);
                $moveFrontNum[$i] = $currentMoveNum = $currentMoveNum + $currentMoveWay;
            } else {
                $currentMoveNum = $currentMoveNum == $maxMoveWidth ? $currentMoveNum : $currentMoveNum + 1;
                $moveFrontNum[$i] = $currentMoveNum;
            }
            if ($moveXWay == 0) {
                $moveFrontNum[$i] = $maxMoveWidth - $moveFrontNum[$i];
            }
            $moveFrontNum[$i] = $moveFrontNum[$i] + $frontBaseMoveNum;
            $moveBackNum[$i]  = $this->_dotsMaxWidth - $moveFrontNum[$i] - $dotsWidth;
        }
        if ($moveYWay == 0) {
            $moveFrontNum = array_reverse($moveFrontNum);
            $moveBackNum  = array_reverse($moveBackNum);
        }
        for ($i = 0; $i < $dotsHeight; $i ++) {
            $newDots[$i + $upMoveNum] = str_repeat('0', $moveFrontNum[$i]) . $dots[$i] . str_repeat('0', $moveBackNum[$i]);
        }
        return $newDots;
    }

    /**
     * 获取默认点阵数据，Tahoma 24
     *
     * @return array
     */
    private static function _getDefaultDots()
    {
        return [
            'a' => ['0011111100','0111111110','0100000111','0000000011','0000000011','0001111111','0111111111','1111000011','1100000011','1100000011','1110001111','0111111111','0011110011'],
            'b' => ['1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100111100','1111111110','1110000110','1100000011','1100000011','1100000011','1100000011','1100000011','1100000011','1100000110','1100001110','1111111100','1101111000'],
            'c' => ['000111110','001111111','011100001','111000000','110000000','110000000','110000000','110000000','110000000','111000001','011100011','001111111','000111110'],
            'd' => ['0000000011','0000000011','0000000011','0000000011','0000000011','0000000011','0001111011','0011111111','0111000011','0110000011','1100000011','1100000011','1100000011','1100000011','1100000011','1100000011','0110000111','0111111111','0011110011'],
            'e' => ['00011111000','00111111110','01110000110','01100000011','11000000011','11111111111','11111111111','11000000000','11000000000','11100000001','01110000011','00111111111','00001111100'],
            'f' => ['00011111','00111111','00110000','01100000','01100000','01100000','11111110','11111110','01100000','01100000','01100000','01100000','01100000','01100000','01100000','01100000','01100000','01100000','01100000'],
            'g' => ['0001111011','0011111111','0111000011','0110000011','1100000011','1100000011','1100000011','1100000011','1100000011','1100000011','0110000111','0111111111','0011111011','0000000011','0000000011','0100000110','0111111110','0111111000'],
            'h' => ['110000000','110000000','110000000','110000000','110000000','110000000','110011100','111111110','111000111','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011'],
            'i' => ['11','11','00','00','00','11','11','11','11','11','11','11','11','11','11','11','11','11'],
            'j' => ['000011','000011','000000','000000','000000','011111','011111','000011','000011','000011','000011','000011','000011','000011','000011','000011','000011','000011','000011','000011','000111','111110','111100'],
            'k' => ['11000000000','11000000000','11000000000','11000000000','11000000000','11000000000','11000001110','11000011100','11000111000','11001110000','11001100000','11011000000','11111000000','11001100000','11000110000','11000111000','11000011100','11000001110','11000000111'],
            'l' => ['11','11','11','11','11','11','11','11','11','11','11','11','11','11','11','11','11','11','11'],
            'm' => ['1100111000011100','1111111101111110','1110001111000111','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011','1100000110000011'],
            'n' => ['110011100','111111110','111000111','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011'],
            'o' => ['00011111000','00111111100','01110001110','11100000111','11000000011','11000000011','11000000011','11000000011','11000000011','11100000111','01110001110','00111111100','00011111000'],
            'p' => ['1100111100','1111111110','1110000110','1100000011','1100000011','1100000011','1100000011','1100000011','1100000011','1100000110','1100001110','1111111100','1101111000','1100000000','1100000000','1100000000','1100000000','1100000000'],
            'q' => ['0001111011','0011111111','0111000011','0110000011','1100000011','1100000011','1100000011','1100000011','1100000011','1110000011','0110000111','0111111111','0011111011','0000000011','0000000011','0000000011','0000000011','0000000011'],
            'r' => ['1100111','1101111','1111000','1100000','1100000','1100000','1100000','1100000','1100000','1100000','1100000','1100000','1100000'],
            's' => ['0011111100','0111111110','1110000010','1100000000','1100000000','0111100000','0011111110','0000011111','0000000011','1000000011','1110000111','1111111110','0011111100'],
            't' => ['0110000','0110000','0110000','0110000','1111111','1111111','0110000','0110000','0110000','0110000','0110000','0110000','0110000','0110000','0110000','0011111','0001111'],
            'u' => ['110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','110000011','111000111','011111111','001110011'],
            'v' => ['110000000011','011000000110','011000000110','011000000110','001100001100','001100001100','001110001000','000110011000','000110011000','000011110000','000011110000','000011110000','000001100000'],
            'w' => ['110000001100000011','110000001110000011','011000011110000110','011000011110000110','011000010011000110','011100110011000110','001100110011001100','001100100001101100','001101100001101100','000111100001101000','000111000000111000','000111000000111000','000111000000111000'],
            'x' => ['110000000011','011000000110','001100001100','000110011000','000110011000','000011110000','000001100000','000011110000','000110011000','000110011000','001100001100','011000000110','110000000011'],
            'y' => ['110000000011','011000000110','011000000110','011000000110','001100001100','001100001100','000110011000','000110011000','000110011000','000011110000','000011110000','000011100000','000001100000','000001100000','000011000000','000011000000','000110000000','000110000000'],
            'z' => ['111111111','111111111','000000110','000000110','000001100','000011000','000111000','000110000','001100000','011000000','011000000','111111111','111111111'],
            'A' => ['00000111100000','00000111100000','00000111100000','00000100100000','00001100110000','00001100110000','00001100110000','00011000011000','00011000011000','00011000011000','00110000001100','00111111111100','00111111111100','01100000000110','01100000000110','01100000000110','11100000000111','11000000000011'],
            'B' => ['11111111000','11111111100','11000001110','11000000110','11000000110','11000000110','11000001100','11111111000','11111111100','11000000110','11000000011','11000000011','11000000011','11000000011','11000000111','11000001110','11111111100','11111111000'],
            'C' => ['000011111100','000111111111','001110000011','011100000001','011000000000','111000000000','110000000000','110000000000','110000000000','110000000000','110000000000','110000000000','111000000000','011000000000','011100000001','001110000011','000111111111','000011111100'],
            'D' => ['1111111100000','1111111111000','1100000111100','1100000001110','1100000000110','1100000000111','1100000000011','1100000000011','1100000000011','1100000000011','1100000000011','1100000000011','1100000000110','1100000000110','1100000001110','1100000111100','1111111111000','1111111100000'],
            'E' => ['1111111111','1111111111','1100000000','1100000000','1100000000','1100000000','1100000000','1111111110','1111111110','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1111111111','1111111111'],
            'F' => ['11111111111','11111111111','11000000000','11000000000','11000000000','11000000000','11000000000','11111111111','11111111111','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000'],
            'G' => ['00000111111100','00011111111111','00111000000111','01110000000001','01100000000000','11100000000000','11000000000000','11000000000000','11000000000000','11000000111111','11000000111111','11000000000011','11100000000011','01100000000011','01110000000011','00111100000011','00011111111111','00000111111000'],
            'H' => ['110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','111111111111','111111111111','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011'],
            'I' => ['111111','111111','001100','001100','001100','001100','001100','001100','001100','001100','001100','001100','001100','001100','001100','001100','111111','111111'],
            'J' => ['00111111','00111111','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','00000011','10000111','11111110','11111100'],
            'K' => ['110000000111','110000001110','110000011100','110000111000','110001110000','110001100000','110011000000','110110000000','111110000000','111111000000','110011100000','110001100000','110000110000','110000111000','110000011000','110000001100','110000001110','110000000111'],
            'L' => ['1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1100000000','1111111111','1111111111'],
            'M' => ['111000000000111','111100000001111','111100000001111','111110000011111','110110000011011','110111000011011','110011000110011','110011000110011','110001101100011','110001101100011','110000111000011','110000111000011','110000010000011','110000000000011','110000000000011','110000000000011','110000000000011','110000000000011'],
            'N' => ['111000000011','111100000011','111100000011','111110000011','110110000011','110111000011','110011000011','110011100011','110001100011','110001110011','110000110011','110000111011','110000011011','110000011111','110000001111','110000001111','110000000111','110000000111'],
            'O' => ['000001111100000','000111111111000','001110000011100','011100000001110','011000000000110','111000000000111','110000000000011','110000000000011','110000000000011','110000000000011','110000000000011','110000000000011','111000000000111','011000000000110','011100000001110','001110000011100','000111111111000','000001111100000'],
            'P' => ['11111111000','11111111100','11000001110','11000000111','11000000011','11000000011','11000000011','11000000111','11000001110','11111111100','11111110000','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000','11000000000'],
            'Q' => ['000001111100000','000111111111000','001110000011100','011100000001110','011000000000110','111000000000111','110000000000011','110000000000011','110000000000011','110000000000011','110000000000011','110000000000011','111000000000111','011000000000110','011100000001110','001110000011100','000111111111000','000001111100000','000000001100000','000000001100000','000000000110000','000000000111111','000000000011111'],
            'R' => ['1111111100000','1111111111000','1100000011000','1100000001100','1100000001100','1100000001100','1100000001100','1100000011000','1100000111000','1111111110000','1111111000000','1100001100000','1100001110000','1100000110000','1100000011000','1100000001100','1100000001110','1100000000111'],
            'S' => ['00011111100','01111111110','01100000110','11000000010','11000000000','11000000000','11100000000','01111000000','01111111000','00011111110','00000001111','00000000011','00000000011','00000000011','10000000011','11100000110','11111111100','00111111000'],
            'T' => ['11111111111111','11111111111111','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000'],
            'U' => ['110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','110000000011','111000000111','011100001110','001111111100','000111111000'],
            'V' => ['11000000000011','11100000000111','01100000000110','01100000000110','01100000000110','00110000001100','00110000001100','00110000001100','00011000011000','00011000011000','00011000011000','00001100110000','00001100110000','00001100110000','00000111100000','00000111100000','00000111100000','00000011000000'],
            'W' => ['1100000000110000000011','1100000001111000000011','0110000001111000000110','0110000001111000000110','0110000001111000000110','0110000011001100000110','0011000011001100001100','0011000011001100001100','0011000010001100001100','0011000010000110001100','0001100110000110011000','0001100110000110011000','0001100100000010011000','0000100100000010010000','0000111100000011110000','0000111100000011110000','0000111000000001110000','0000011000000001100000'],
            'X' => ['11000000000011','01100000000110','00110000001100','00110000001100','00011000011000','00001100110000','00001100110000','00000111100000','00000011000000','00000011000000','00000111100000','00001100110000','00001100110000','00011000011000','00110000001100','00110000001100','01100000000110','11000000000011'],
            'Y' => ['11100000000111','01100000000110','01110000001110','00110000001100','00011000011000','00011000111000','00001100110000','00001111110000','00000111100000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000','00000011000000'],
            'Z' => ['11111111111','11111111111','00000000011','00000000110','00000001100','00000001100','00000011000','00000110000','00000110000','00001100000','00011100000','00011000000','00110000000','00110000000','01100000000','11000000000','11111111111','11111111111'],
            '1' => ['00011000','00011000','11111000','11111000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','00011000','11111111','11111111'],
            '2' => ['00111110000','11111111100','11100001100','10000000110','00000000110','00000000110','00000000110','00000001110','00000001100','00000011000','00000111000','00001110000','00011100000','00111000000','01110000000','11100000000','11111111111','11111111111'],
            '3' => ['00111111000','11111111110','11100000111','10000000011','00000000011','00000000011','00000001110','00001111100','00001111000','00000000110','00000000011','00000000011','00000000011','00000000011','10000000111','11100001110','11111111100','00111111000'],
            '4' => ['000000001100','000000011100','000000111100','000001101100','000011001100','000110001100','001110001100','001100001100','011000001100','110000001100','111111111111','111111111111','000000001100','000000001100','000000001100','000000001100','000000001100','000000001100'],
            '5' => ['0111111111','0111111111','0110000000','0110000000','0110000000','0110000000','0110000000','0111111000','0111111110','0000000110','0000000011','0000000011','0000000011','0000000011','0000000111','1100001110','1111111100','0111111000'],
            '6' => ['00000111110','00011111110','00111000000','01110000000','01100000000','01100000000','11000000000','11011111000','11111111110','11100000110','11000000011','11000000011','11000000011','11000000011','01100000011','01110000110','00111111100','00011111000'],
            '7' => ['11111111111','11111111111','00000000011','00000000111','00000000110','00000001100','00000001100','00000011000','00000011000','00000110000','00000110000','00001100000','00001100000','00011000000','00011000000','00110000000','00110000000','01100000000'],
            '8' => ['00011111000','01111111110','01100000111','11000000011','11000000011','11000000011','11100000110','01111001100','00011111000','01100111100','01100000110','11000000011','11000000011','11000000011','11100000011','01110000110','01111111100','00011111000'],
            '9' => ['00011111000','00111111100','01100001110','11000000110','11000000011','11000000011','11000000011','11000000011','01100000111','01111111111','00011111011','00000000011','00000000110','00000000110','00000001110','00000011100','00111111000','00111110000'],
            '0' => ['00011111000','00111111100','01110001110','01100000110','11000000011','11000000011','11000000011','11000000011','11000000011','11000000011','11000000011','11000000011','11000000011','11000000011','01100000110','01110001110','00111111100','00011111000']
        ];
    }
}
