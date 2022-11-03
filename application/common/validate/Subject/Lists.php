<?php

namespace app\common\validate\subject;

//引入TP的验证器
use think\Validate;

/**
 * 课程章节验证器
 */
class Lists extends Validate
{
    //验证规则
    protected $rule = [
        'subid' => 'require',
        'title' => 'require',
        'url' => 'require',
    ];

    //提示文案
    protected $message = [
        'subid.require' => '所属的课程未知',
        'title.require' => '请输入章节名称',
        'url.require' => '请选择课程附件',
    ];
}
