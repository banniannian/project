<?php

namespace app\common\validate\Business;

// 引入TP的验证器
use think\Validate;

/**
 * 用户消费记录验证器
*/

class Record extends Validate {
    //验证规则
    protected $rule = [
        'total' => 'require',
        'busid' => 'require',
    ];

    //提示文案
    protected $message = [
        'total.require' => '消费余额信息未知',
        'busid.require' => '客户信息未知',
    ];
}

?>