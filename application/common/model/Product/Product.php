<?php

namespace app\common\model\Product;

use think\Model;

class Product extends Model {
  // 表名
  protected $name = 'product';
  
  // 自动写入时间戳字段
  protected $autoWriteTimestamp = 'integer';

  // 定义时间戳字段名
  protected $createTime = false;
  protected $updateTime = false;
  protected $deleteTime = false;

  // 追加属性
  protected $append = [
      'flag_text',
      'thumbs_text'
  ];

  public function getFlagList()
  {
      return ['0' => __('下架'), '1' => __('上架')];
  }

  public function getFlagTextAttr($value, $data)
  {
      $value = $value ? $value : (isset($data['flag']) ? $data['flag'] : '');
      $list = $this -> getFlagList();
      return isset($list[$value]) ? $list[$value] : '';
  }

  public function getThumbsTextAttr($value, $data) {
    $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

    // 路径判断
    if(!is_file(".".$thumbs)) {
      //给个默认图
      $thumbs = '/assets/home/images/avatar.jpg';
    }

    //获取系统配置里面的选项
    $url = config('site.cdnurls') ? config('site.cdnurls') : '';

    //拼上域名信息
    $thumbs = trim($thumbs, '/');
    $thumbs = $url.'/'.$thumbs;

    return $thumbs;
  }

  // 时间修改器
  public function setCreatetimeAttr($value,$data) {
      return strtotime($value);
  }

  // 分类关联查询
  public function type() {
      return $this -> belongsTo('app\common\model\Product\Type','typeid','id',[],'LEFT') -> setEagerlyType(0);
  }

  // 单位关联查询
  public function unit()
  {
      return $this -> belongsTo('app\common\model\Product\Unit','unitid','id',[],'LEFT') -> setEagerlyType(0);
  }
}