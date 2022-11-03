<?php

namespace app\common\model\Business;

use think\Model;

/**
 * 用户收货地址模型
 */
class Address extends Model
{
    //模型对应的是哪张表
    protected $name = "business_address";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = false; 

    //设置字段的名字
    protected $createTime = false; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    protected $append = [
        'region_text'
    ];

    //地区获取器
    public function getRegionTextAttr($value, $data)
    {
        $province = model('Region')->where(['code' => $data['province']])->find();
        $city = model('Region')->where(['code' => $data['city']])->find();
        $district = model('Region')->where(['code' => $data['district']])->find();

        $output = [];
        if($province)
        {
            $output[] = $province['name'];
        }

        if($city)
        {
            $output[] = $city['name'];
        }

        if($district)
        {
            $output[] = $district['name'];
        }

        return implode('-', $output);
    }

    //给模型定义一个关联查询
    public function provinces()
    {
        //参数1：关联的模型
        //参数2：用户表的外键的字段
        //参数3：关联表的主键
        //参数4：模型别名
        //参数5：链接方式 left
        // setEagerlyType(1) IN查询
        // setEagerlyType(0) JOIN查询
        return $this->belongsTo('app\common\model\Region', 'province', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    //查询城市
    public function citys()
    {
        return $this->belongsTo('app\common\model\Region', 'city', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    //查询地区
    public function districts()
    {
        return $this->belongsTo('app\common\model\Region', 'district', 'code', [], 'LEFT')->setEagerlyType(0);
    }
}
