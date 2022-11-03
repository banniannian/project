<?php

namespace app\common\validate\business;

use think\Validate;

class Highsea extends Validate
{
    // /**
    //  * 验证规则
    //  */
    // protected $rule = [
    // ];
    // /**
    //  * 提示消息
    //  */
    // protected $message = [
    // ];
    // /**
    //  * 验证场景
    //  */
    // protected $scene = [
    //     'add'  => [],
    //     // 'edit' => [],
    //     'info' => [],
    //     'apply' => [],
    // ];
    
    // protected $rule = [
    //     // 1、设置手机号验证规则
    //     // mobile这个字段在business表中必须是唯一的，否则就是重复
    //     'mobile' => ['require', 'regex:/^1[3456789]{1}\d{9}$/', 'unique:business'], // 手机号
    //     'password' => 'require', // 密码
    //     'salt' => 'require', // 密码盐
    //     'genders' => 'number|in:0,1,2', // 性别
    //     'deal' => 'number|in:0,1', // 成交状态
        
    //   ];
  
    //   // 1-2、提示的文案
    //   protected $message = [
    //     // 当触发require必填规则
    //     'mobile.require' => "手机号必填",
    //     // 当触发unique唯一规则
    //     'mobile.unique' => "手机号存在",
    //     // 当触发unique唯一规则
    //     'mobile.regex' => "手机号格式有误",
  
    //     // 当触发require必填规则
    //     'password:require' => "密码必填",
    //     // 当触发异常时
    //     'salt.require' => "生成密码盐异常",
  
    //     'gender.number' => "性别必须是个数字",
    //     // 当触发性别异常时
    //     'gender.in' => "性别选择有误",
        
    //   ];

    protected $scene = [
        'add'  => [],
        // 'edit' => [],
        'info' => [],
        'apply' => [],
        'recovery' => [],
    ];
}
