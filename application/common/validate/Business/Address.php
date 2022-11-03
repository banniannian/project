<?php

namespace app\common\validate\Business;

//引入底层的验证器类
use think\Validate;

/**
 * 用户收货地址验证器
 */
class Address extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'busid' => 'require', //必填
        'consignee' => 'require', //必填
        'mobile' => 'require', //必填
        'province' => 'require', //必填
        'city' => 'require', //必填
        'address' => 'require', //必填
        'status' => 'number|in:0,1',  //给字段设置范围
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'consignee.require' => '请输入收货人名称',
        'mobile.require' => '请输入手机号码',
        'province.require' => '请选择省份',
        'city.require' => '请选择城市',
        'address.require' => '请输入详细地址',
        'busid.require' => '用户信息未知',
    ];
    
    /**
     * 验证场景
     */
    protected $scene = [
    ];
}
