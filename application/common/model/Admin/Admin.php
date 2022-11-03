<?php

namespace app\common\model\Admin;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    // 表名
    protected $name = 'admin';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名 在插入语句的时候 会自动写入时间戳
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    protected $append = [
        'avatar_text',
        'group_text',
        'createtime_text',
    ];

    // 追加带链接前缀的头像
    public function getAvatarTextAttr($value, $data) {
        $avatar = isset($data['avatar']) ? $data['avatar'] : '';

        // 如果不是图片类型，路径判断要使用相对路径
        if(!is_file("." . $avatar)) {
            // 如果不是图片就使用默认的图片
            $avatar = '/assets/home/images/avatar.jpg';
        }

        // 获取系统配置中的选项
        $url = config('site.cdnurls') ? config('site.cdnurls') : '';

        // 将链接和图像地址拼接
        $avatar = trim($avatar, '/');
        $avatar = $url.'/'.$avatar;

        // 有图片就返回数据库的图片路径，否则是默认的图片路径
        return $avatar;
    }

    // 管理员角色组
    public function getGroupTextAttr($value, $data) {
        // 权限分组
        $AuthGroupAccessModule = model('Admin.AuthGroupAccess');
        // 分组
        $AuthGroupModule = model('Admin.AuthGroup');

        // 用户id = 页面中登录的管理员id =》分组id
        $gid = $AuthGroupAccessModule -> where(['uid' => $data['id']]) -> value('group_id');

        // 如果角色组不存在
        if(!$gid) {
            return '暂无角色组';
        }

        // 根据gid查询分组中对应的的id, 将对应id的名字取出
        $name = $AuthGroupModule -> where(['id' => $gid]) -> value('name');

        // 如果查询不到
        if(!$name) {
            return '暂无角色组名称';
        }

        return $name;
    }

    // 创建时间获取器
    public function getCreatetimeTextAttr($value, $data) {
        // 拿到当前页面登录管理员中的createtime
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;

        // 判断是否存在
        if($createtime) {
            return date('Y-m-d H:i', $createtime);
        } else {
            // 没有就新建个时间
            return date('Y-m-d H:i', time());
        }
    }


}
