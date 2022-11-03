<?php

namespace app\common\model\Business;

use think\Model;

/**
 * 客户来源模型
*/

class Source extends Model {
    // 表名
    protected $name = 'business_source'; // database文件中前缀拼接后是pro_business_source
}
