<?php

namespace app\common\model\Order;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

/**
 * 订单模型
 */
class Order extends Model
{
    //继承软删除
    use SoftDelete;
    
    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deleteTime';

    protected $append = [
        'createtime_text',
        'status_text'
    ];

    // 湖区时间将其转为正常时间
    public function getCreatetimeTextAttr($value, $data) {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;

        return date('Y-m-d H:i', $createtime);
    }

    // 获取用户对订单的支付状态
    public function getStatusTextAttr($value, $data) {
        $status = $data['status'];
        $text = '';

        switch($status) {
            case 1:
                $text = '已支付';
                break;
            case 2:
                $text = '已发货';
                break;
            case 3:
                $text = '已收货';
                break;
            case 4:
                $text = '已评价';
                break;
            case -1:
                $text = '已退货';
                break;
            case 0:
                $text = '未支付';
                break;
            default:
                $text = '未知状态';
        }

        return $text;
    }

    // 查询物流
    public function express() {
        return $this -> belongsTo('app\common\model\Expressquery\Expressquery', 'expressid', 'id', [], 'LEFT') -> setEagerlyType(0);
    }
}
