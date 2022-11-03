<?php

namespace app\common\validate\subject;

//引入TP的验证器
use think\Validate;

/**
 * 课程验证器
 */
class Subject extends Validate
{
    //验证规则
    protected $rule = [
        // 课程名称, 且唯一, subject表
        'title' => ['require', 'unique:subject'],
        // 价格必须大于等于0
        'price' => 'require|egt:0', //egt 大于等于0
        'cateid' => 'require',
    ];

    //提示文案
    protected $message = [
        'title.require' => '请输入课程名称',
        'title.unique' => '课程已存在',
        'price.require' => '请输入课程价格',
        'price.egt' => '课程价格不能小于0元',
        'cateid.require' => '请选择课程分类',
    ];
}
