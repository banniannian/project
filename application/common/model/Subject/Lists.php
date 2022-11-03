<?php

namespace app\common\model\Subject;

use think\Model;

class Lists extends Model {
    // 2、表名
    protected $name = 'subject_lists'; // database文件中前缀拼接后是pro_business

    // 3、自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 4、定义时间戳字段名，在插入语句时自动写入时间戳
    protected $createTime = 'createtime'; // 创建字段时自动写入
    protected $updateTime = false; // 不更新
    protected $deleteTime = false; // 不删除

    // 5、忽略数据表不存在的字段
    protected $field = true;

}
