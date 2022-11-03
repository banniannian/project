<?php

namespace app\common\model\Admin;

use think\Model;

/**
 * 分组模型
 */
class AuthGroup extends Model
{
    // 表名
    protected $name = 'auth_group';


    // 定义时间戳字段名 在插入语句的时候 会自动写入时间戳
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

}
