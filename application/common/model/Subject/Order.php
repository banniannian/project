<?php

namespace app\common\model\Subject;

use think\Model;

/**
 * 课程购买订单表
*/

class Order extends Model {
    // 2、表名
    protected $name = 'subject_order'; // database文件中前缀拼接后是pro_business

    // 3、自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 4、定义时间戳字段名，在插入语句时自动写入时间戳
    protected $createTime = 'createtime'; // 创建字段时自动写入
    protected $updateTime = false; // 不更新
    protected $deleteTime = false; // 不删除

    // 5、忽略数据表不存在的字段
    protected $field = true;

    // 联表查询
    public function subject() {
        //subid 课程外键
        //关联课程模型
        // belongsTo 关联模型
        // join 链表的条件 ON 
        // subid 外键 
        // id Subject 表里面的主键
        // 参数1：关联模型
        // 参数2：外键(订单表 - $this)
        // 参数3：主键(课程表 - 关联查询表的)
        // setEagerlyType 返回关联查询的数据

        return $this -> belongsTo('app\common\model\Subject\Subject', 'subid', 'id') -> setEagerlyType(0);
    }

    public function business() {
        // 返回有关用户的其它关联信息
        return $this -> belongsTo('app\common\model\Business\Business', 'busid', 'id') -> setEagerlyType(0);
    }

}
