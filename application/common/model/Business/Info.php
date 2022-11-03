<?php

namespace app\common\model\business;

use think\Model;
use traits\model\SoftDelete;

class Info extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'business';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'gender_text',
        'deal_text',
        'status_text'
    ];
    

    
    public function getGenderList()
    {
        return ['0' => __('Gender 0'), '1' => __('Gender 1'), '2' => __('Gender 2')];
    }

    public function getDealList()
    {
        return ['0' => __('Deal 0'), '1' => __('Deal 1')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getGenderTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gender']) ? $data['gender'] : '');
        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getDealTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['deal']) ? $data['deal'] : '');
        $list = $this->getDealList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
