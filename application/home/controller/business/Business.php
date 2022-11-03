<?php

namespace app\home\controller\business;

use app\common\controller\Home;

use think\Db;

/**
 * 客户中心控制器
*/

class Business extends Home {
    // 构造函数，加载全局模型
    public function __construct() {
        // Business继承自home, 意味着会先执行Home中的1类再回来执行当前的类
        parent::__construct(); // 继承父类

        // 全局模型加载
        $this -> BusinessModel = model('Business.Business');
        $this-> OrderModel = model('Subject.Order');
        $this -> RecordModel = model('Business.Record');
        $this-> RegionModel = model('Region');
    }

    // 客户中心
    public function index() {
        //渲染模板
        return $this -> view -> fetch();
    }

    // 个人资料
    public function profile() {
        // 判断是否有表单提交
        if($this -> request -> isPost()) {
            // 获取所有请求数据
            $nickname = $this -> request -> param('nickname', '', 'trim');
            $password = $this -> request -> param('password', '', 'trim');
            $email = $this -> request -> param('email', '', 'trim');
            $gender = $this -> request -> param('gender', '', 'trim');
            $region = $this -> request -> param('region', '', 'trim');

            // 组装数据
            $data = [
                'id' => $this -> LoginAuth['id'],
                'nickname' => $nickname,
                'gender' => $gender,
                'email' => $email,
            ];

            // 判断表单的密码是否为空，不为空就修改密码
            if(!empty($password)) {
                // 判断当前新密码是否等于旧密码
                $newsalt = $this -> LoginAuth['salt'];
                $repass = md5($password . $newsalt);

                if($repass == $this -> LoginAuth['password']) {
                    $this -> error("新密码不能等于旧密码");
                    exit;
                }

                // 新密码不等于旧密码就 重新生成密码盐
                $salt = build_ranstr();

                $data['salt'] = $salt;
                $data['password'] = md5($password . $salt);
            }

            // 判断地区是否为空
            if(!empty($region)) {
                // 1、将广东省/广州市/海珠区字符串转为数组，将/移除
                $RegionList = explode('/', $region);
                
                // 2、每次移除数组中最后一个数据
                // 2-2、转换为数组, 每次获取最后一位(可以确保用户没有选择三个地区的情况下一直拿到正确的地区数据)
                $last = array_pop($RegionList);

                // 3、根据最后一个地区名字查询出数据, 然后取出它和它的父数据代码
                $path = $this -> RegionModel -> where(['name' => $last]) ->value('parentpath');

                // 4、再将拿到的数据转为数组
                $RegionCode = explode('/', $path);

                // 5、分别拿到$RegionCode中的省、市、区代码
                $data['province'] = isset($RegionCode[0]) ? $RegionCode[0] : '';
                $data['city'] = isset($RegionCode[1]) ? $RegionCode[1] : '';
                $data['district'] = isset($RegionCode[2]) ? $RegionCode[2] : '';
            }

            // 接收文件上传
            if(isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
                // 调用common文件中的build_upload文件获取方法
                $success = build_upload('avatar');

                // 判读build_upload方法返回的成功还是失败
                if($success['result']) {
                    // 上传成功
                    $data['avatar'] = $success['data'];
                } else {
                    // 上传失败
                    $this -> error($success['msg']);
                    exit;
                }
            }

            // 上面代码没问题就准备更新数据(isUpdate可以更新数据true||false)
            $result = $this -> BusinessModel -> isUpdate(true) -> save($data);

            // 判断是否更新成功(返回影响行数)
            if($result === FALSE) {
                // 如果没更新成功就调用getError
                $this -> error($this -> BusinessModel -> getError());
                exit;
            } else {
                // 判断是否有上传图片
                if(isset($data['avatar'])) {
                    // 判断哪是否是图片，并删除旧图片(必须两个条件成立)
                    @is_file("." . $this -> LoginAuth['avatar']) && unlink("." . $this -> LoginAuth['avatar']);

                    // 拿到数据库中的id和手机号和现有的nickname和avatar进行组装
                    $login = [
                        'id' => $this -> LoginAuth['id'],
                        'mobile' => $this -> LoginAuth['mobile'],
                        'nickname' => $data['nickname'],
                        'avatar' => $data['avatar'],
                    ];

                    // 更新到cookie中
                    cookie('LoginAuth', $login);
                }
                // 提示修改成功
                $this -> success('修改成功', url('/home/business/business/index'));
                exit;
            }
        }

        //渲染模板
        return $this -> view -> fetch();
    }

    // 数据分析
    public function pandas() {
        // 封装数据
        // $money_biu =  $this
        // -> RecordModel
        // -> where(['busid' => $this-> LoginAuth['id']])
        // -> field('')
        // -> select();

        // $this -> model -> getLastSql($money_biu);

        // $q = (int)$money_biu[$i]->total;

        // for($i = 0; $i <= $money_biu; $i++) {
        //     $q = (int)$money_biu[$i]->total;

        // }
        // exit;

        // $res = $Model -> query("SELECT sum(total) from RecordModel Where busid = $this-> LoginAuth['id']");
        $idp = $this -> LoginAuth['id'];

        // 总消费
        $consume = Db::table('pro_business_record')->where('busid', $idp) ->sum('total');

        // 最小消费
        $mins = Db::table('pro_business_record')->where('busid', $idp) ->min('total');

        // 最大消费
        $maxs = Db::table('pro_business_record')->where('busid', $idp) ->max('total');

        // 平均消费
        $avgs = Db::table('pro_business_record')->where('busid', $idp) ->avg('total');

        $data =[
            'id' => $this -> LoginAuth['id'], // 用户id
            'money' => $this -> LoginAuth['money'], // 剩余钱财
            'consume' => $consume, // 总消费
            'avgs' => round($avgs, 2), // 平均消费，保留两位小数
            'mins' => $mins, // 最小消费
            'maxs' => $maxs, // 最大消费
        ];


        $this->view->assign('User_data', $data);

        return $this -> view -> fetch();
    }

    // 联系我们
    public function contact() {
        // 加载商品和分类的模型
        $CategoryModel = model('Subject.Category');
        $SubjectModel = model('Subject.Subject');

        // 查询数据库中的商品点赞量最多并降序8个
        $toplist = $SubjectModel -> orderRaw("LPAD(LOWER(likes), 10, 0) DESC") -> limit(8) -> select();

        // 查询分类, 升序
        $cate = $CategoryModel -> order('weight ASC') -> select();

        // 用于重新组装的空数组
        $catelist = [];

        // 循环分类(共3个)
        foreach($cate as $key => $item) {
            // 查询对应分类的课程id(分类的主键id等于课程的外键id)
            $subject = $SubjectModel -> where(['cateid' => $item['id']]) -> order('createtime DESC')  -> select(); // 固定查询8条

            // 有课程才追加分类
            if($subject) {
                $catelist[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'subject' => $subject
                ];
            }
        }

        // 将查询到数据赋值在商品上
        $this -> view -> assign([
            'catelist' => $catelist,
            'toplist' => $toplist,
        ]);


        // 渲染模板
        return $this -> view -> fetch();
    }
}
