<?php

namespace app\common\validate\Order;

//引入底层的验证器类
use think\Validate;

/**
 * 购物车验证器
 */
class Cart extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'busid' => 'require', //必填
        'proid' => 'require', //必填
        'pronum' => 'require', //必填
        'price' => 'require', //必填
        'total' => 'require', //必填
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'busid.require'  => '用户ID信息未知',
        'proid.require'  => '商品ID信息未知',
        'pronum.require'  => '请选择商品数量',
        'price.require'  => '请输入商品的单价',
        'total.require'  => '请输入商品总价',
    ];
    
    /**
     * 验证场景
     */
    protected $scene = [
    ];
}
