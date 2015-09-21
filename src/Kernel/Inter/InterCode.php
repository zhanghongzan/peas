<?php
namespace Peas\Kernel\Inter;

class InterCode
{
    /**
     * 状态：成功
     *
     * @var string
     */
    const STATUS_SUCCESS = 'success';

    /**
     * 状态：失败
     *
     * @var string
     */
    const STATUS_FAILURE = 'failure';


    /**
     * 构造函数，初始化
     *
     * @param string $status
     * @param int    $code
     * @param string $desc
     * @param array  $data
     */
    public function __construct($status, $code, $desc = '', $data = [])
    {
        $this->code   = $code;
        $this->desc   = $desc;
        $this->data   = $data;
        $this->status = $status;
    }

    /**
     * 状态
     *
     * @var string
     */
    public $status;

    /**
     * 状态代码
     *
     * @var int
     */
    public $code;

    /**
     * 状态信息
     *
     * @var string
     */
    public $desc;

    /**
     * 结果数据
     *
     * @var array
     */
    public $data;
}
