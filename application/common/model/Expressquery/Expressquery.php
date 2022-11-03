<?php

namespace app\common\model\expressquery;

use think\Model;

/**
 * 物流模型
 */
class Expressquery extends Model
{
    // 表名
    protected $name = 'expressquery';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}
