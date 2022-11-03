<?php

namespace app\common\validate\Order;

//引入底层的验证器类
use think\Validate;

/**
 * 订单验证器
 */
class Order extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'busid' => 'require', //必填
        'businessaddrid' => 'require', //必填
        'code' => 'require|unique:order', //必填
        'expcode' => 'unique:order', //必填
        'status' => 'number|in:-1,0,1,2,3,4', //必填
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'busid.require'  => '用户信息未知',
        'businessaddrid.require'  => '收货地址未知',
        'code.require'  => '订单号必填',
        'code.unique'  => '订单号已存在, 请重新输入',
        'expcode.unique'  => '配送单号已存在, 请重新输入',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
    ];
}
