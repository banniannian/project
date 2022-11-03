<?php

namespace app\common\model\Business;

use think\Model;

/**
 * 用户消费记录模型
*/

class Record extends Model {
    // 表名
    protected $name = 'business_record';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名 在插入语句的时候 会自动写入时间戳
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;
    
    public function business()
    {
        // 返回客户信息的关联查询
        // 参数1：关联模型(命名空间)
        // 参数2：外键(消费记录表 - $this)
        // 参数3：主键(客户表 - 关联查询表的)
        // setEagerlyType 返回关联查询的数据
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id')->setEagerlyType(0);
    }
}
