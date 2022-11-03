<?php

namespace app\common\validate\business;

use think\Validate;

/**
 * 客户回访记录
 */
class Visit extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'content' => 'require',
        'busid' => 'require',
        'adminid' => 'require',
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'content.require' => '回访内容必填',
        'busid.require' => '回访客户未知',
        'adminid.require' => '管理员未知',
    ];
    
    /**
     * 验证场景
     */
    protected $scene = [
    ];    
}
