<?php

namespace app\common\model\Order;

use think\Model;

/**
 * 购物车模型
 */
class Cart extends Model
{
    // 表名
    protected $name = 'order_cart';
    

    //关联查询
    public function product() {
        return $this->belongsTo('app\common\model\Product\Product', 'proid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
