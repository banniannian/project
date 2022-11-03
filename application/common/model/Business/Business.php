<?php

namespace app\common\model\Business;

use think\Model;
use traits\model\SoftDelete;

/**
 * 客户模型
*/

class Business extends Model {
    use SoftDelete;
    
    // 1、因为tp5里面封装好了数据库的函数, 所以操作数据库变得简单许多
    // 1-2、在database文件中要设置好表前缀(一开始就会自动设置)

    // 2、表名
    protected $name = 'business'; // database文件中前缀拼接后是pro_business

    // 3、自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 4、定义时间戳字段名，在插入语句时自动写入时间戳
    protected $createTime = 'createtime'; // 创建字段时自动写入
    protected $updateTime = false; // 不更新
    protected $deleteTime = false; // 不删除

    // 5、忽略数据表不存在的字段
    protected $field = true;

    // 追加自定义字段属性,别名
    protected $append = [
        'avatar_text',
        'mobile_text',
        // 自定义性别给前台使用
        'gender_text',
        // 自定义地区选择器给前台使用
        'province_text', // 市
        'city_text', // 省
        'district_text', // 区
    ];

    // 给追加的新字段的赋值
    // 获取用户头像
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

    // 获取手机号
    public function getMobileTextAttr($value, $data) {
        // 有手机号就去除左右空白，否则为空
        $mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
        
        // 将手机号中间换为*
        return substr_replace($mobile, '****', 3, 4);
    }

    // 获取性别
    public function getGenderTextAttr($value, $data) {
        $list = ['保密', '男', '女'];

        $gender = $data['gender'];

        return $list[$gender];
    }

    // 分别获取市、省、区
    // 获取数据库中的市名字
    public function getProvinceTextAttr($value, $data) {
        // 1、拿到前台请求地址中的市的代码
        $province = $data['province'];

        // 2、判断是否有市代码
        if(empty($province)) {
            return '';
        }

        // 3、查询市代码然后取出市的名字将其返回
        return model('Region') -> where(['code' => $province]) -> value('name');
    }

    // 获取数据库中的省名字
    public function getCityTextAttr($value, $data) {
        // 1、拿到省代码
        $city = $data['city'];

        // 2、判断是否有省代码
        if(empty($city)) {
            return '';
        }

        // 3、根据省代码取出省的名字将其返回
        return model('Region') -> where(['code' => $city]) -> value('name');
    }

    // 获取数据库中的区名字
    public function getDistrictTextAttr($value, $data) {
        // 1、拿到前台的区代码
        $district = $data['district'];

        // 2、判断是否有区代码
        if(empty($district)) {
            return '';
        }

        // 3、根据区代码取出省名字将其返回
        return model('Region') -> where(['code' => $district]) -> value('name');
    }

}
