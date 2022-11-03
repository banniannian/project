<?php

namespace app\common\model\expressquery;

use think\Model;

class ExpressqueryConfig extends Model
{

    // 表名
    protected $name = 'expressquery_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'express_type_text',
        'kuaidi100_type_text'
    ];
    

    public function getExpressTypeList()
    {
        return ['kuaidiniao' => __('Kuaidiniao'), 'kuaidi100' => __('Kuaidi100'), 'ali' => __('Ali')];
    }

    public function getKuaidi100TypeList()
    {
        return ['free' => __('Free'), 'company' => __('Company')];
    }


    public function getExpressTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['express_type']) ? $data['express_type'] : '');
        $list = $this->getExpressTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKuaidi100TypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kuaidi100_type']) ? $data['kuaidi100_type'] : '');
        $list = $this->getKuaidi100TypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

}
