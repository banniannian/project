<?php

namespace app\common\validate\Subject;

// tp验证器
use think\Validate;

/**
 * 购买订单验证
*/

class Order extends Validate {
  // 1、验证规则
    protected $rule = [
      'subid' => 'require',
      'busid' => 'require',
      'total' => 'require|egt:0', // egt 大于等于 0
      'code' => ['require', 'unique:subject_order'],
    ];

    // 1-2、提示的文案
    protected $message = [
      'subid.require' => '购买课程信息未知',
      'busid.require' => '客户信息未知',
      'total.require' => '订单价格未知',
      'total.egt' => '订单价格必须大有0',
      'code.require' => '订单号未知',
      'code.unique' => '订单号已存在',
    ];
}
