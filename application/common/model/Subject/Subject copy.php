<?php

namespace app\common\model\Subject;

use think\Model;

/**
 * 课程模型
*/

class Subject extends Model
{
    // 2、表名
    protected $name = 'subject'; // database文件中前缀拼接后是pro_business

    // 3、自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 4、定义时间戳字段名，在插入语句时自动写入时间戳
    protected $createTime = 'createtime'; // 创建字段时自动写入
    protected $updateTime = false; // 不更新
    protected $deleteTime = false; // 不删除

    // 5、忽略数据表不存在的字段
    protected $field = false;

    // 追加自定义字段属性,别名
    protected $append = [
        'thumbs_text',
        'likes_text'
    ];

    // 获取课程图片
    public function getThumbsTextAttr($value, $data) {
        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

        // 如果不是图片类型，路径判断要使用相对路径
        if(!is_file("." . $thumbs)) {
            // 如果不是图片就使用默认的图片
            $thumbs = '/assets/home/images/avatar.jpg';
        }

        // 有图片就返回数据库的图片路径，否则是默认的图片路径
        return $thumbs;
    }

    // 将获取的点赞字符串转为数组并将数组中的总数返回出去
    public function getLikesTextAttr($value, $data) {
        // 取出点赞字符串的字段值并去掉空白
        $likes = trim($data['likes']);
        // 将$likes中的字符串转为数组格式
        $likes = explode(',', $likes);
        // 取出两两边空的元素
        $likes = array_filter($likes);

        // 将数组中的值的总数量返回出去(点赞的数量)
        return count($likes);
    }
}
