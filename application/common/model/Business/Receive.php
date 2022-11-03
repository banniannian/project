<?php

namespace app\common\model\Business;

use think\Model;

/**
 * 客户领取模型
 */
class Receive extends Model
{
    // 表名
    protected $name = 'business_receive';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名 在插入语句的时候 会自动写入时间戳
    protected $createTime = 'applytime';
    protected $updateTime = false;
    // protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    //追加自定义字段属性 别名
    protected $append = [
        'status_text',
    ];


    public function apply()
    {
        // 返回客户信息的关联查询
        // 参数1：关联模型(命名空间)
        // 参数2：外键(消费记录表 - $this)
        // 参数3：主键(客户表 - 关联查询表的)
        // setEagerlyType 返回关联查询的数据
        return $this->belongsTo('app\common\model\Admin\Admin', 'applyid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        // 返回客户信息的关联查询
        // 参数1：关联模型(命名空间)
        // 参数2：外键(消费记录表 - $this)
        // 参数3：主键(客户表 - 关联查询表的)
        // setEagerlyType 返回关联查询的数据
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 状态
    public function getStatustextAttr($value, $data) {
        $status = $data['status'];

        $list = ['apply' => '申请', 'allot' => '分配', 'recovery' => '回收'];

        return $list[$status];
    }


    public function admin()
    {
        // 返回客户信息的关联查询
        // 参数1：关联模型(命名空间)
        // 参数2：外键(消费记录表 - $this)
        // 参数3：主键(客户表 - 关联查询表的)
        // setEagerlyType 返回关联查询的数据
        return $this->belongsTo('app\admin\model\Admin', 'busid', 'id')->setEagerlyType(0);
    }
}
