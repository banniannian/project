<?php

namespace app\common\model\Product;

use think\Model;

class Type extends Model {
  // 表名
  protected $name = 'product_type';
  
  // 自动写入时间戳字段
  protected $autoWriteTimestamp = false;

  // 定义时间戳字段名
  protected $createTime = false;
  protected $updateTime = false;
  protected $deleteTime = false;

  // 追加属性
  protected $append = [
    'thumb_text'
  ];

  public function getThumbTextAttr($value, $data) {
    $thumb = isset($data['thumb']) ? $data['thumb'] : '';

    // 路径判断
    if(!is_file(".".$thumb)) {
      //给个默认图
      $thumb = '/assets/home/images/avatar.jpg';
    }

    //获取系统配置里面的选项
    $url = config('site.cdnurls') ? config('site.cdnurls') : '';

    //拼上域名信息
    $thumb = trim($thumb, '/');
    $thumb = $url.'/'.$thumb;

    return $thumb;
  }
}