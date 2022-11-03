<?php
namespace app\home\controller;

use think\Controller;

class Index extends Controller
{
    // 封装model全局模型构造函数
    public function __construct() {
        // 将父类构造函数继承过来(如果不写会将controller中的给覆盖掉)
        parent::__construct();

        // 加载全局模型
        $this -> BusinessModel = model('Business.Business');
    }

    // 首页
    public function index() {
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
            $subject = $SubjectModel -> where(['cateid' => $item['id']]) -> order('createtime DESC') -> limit(234567) -> select(); // 固定查询8条

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

    // 注册
    public function register() {
        // 临时关闭模板布局(下满是tp5用的)
        $this->view->engine->layout(false);

        if($this -> request -> isPost()) {
            // 1、接收密码，判断和确认密码是否一致
            // 2、生成密码盐, 对密码进行加密
            // 3、组装数据，插入数据库
            $mobile = $this -> request -> param('mobile', '', 'trim'); // 拿到mobile, 默认为空字符串, 两边去除空白
            $password = $this -> request -> param('password', '', 'trim'); // 拿到mobile, 默认为空字符串, 两边去除空白
            $repass = $this -> request -> param('repass', '', 'trim'); // 拿到mobile, 默认为空字符串, 两边去除空白

            // 判断一次密码和二次密码是否一致
            if($password != $repass) {
                $this -> error('第一次的密码和第二次密码不一致');
                exit;
            }

            // 一致就生成密码盐(在app/common文件中手动添加密码盐函数)
            $salt = build_ranstr();

            // 密码+密码盐进行加密
            $password = md5($password . $salt);

            // 加载模型
            // 查询云课堂渠道来源哪里(有两个方法)

            // 方法一: 只查询一条数据
            // $sourceid = model('Business.Source') -> where(['name' => '云课堂']) -> find();

            // 方法二: 查询一条数据中的一个字段
            $sourceid = model('Business.Source') -> where(['name' => '云课堂']) -> value('id');
            // var_dump($source);


            // 组装数据
            $data =[
                'mobile' => $mobile,
                'password' => $password,
                'salt' => $salt,
                'deal' => 0, // 成交的账户和未成交的账户
                'invitecode' => $salt, // 邀请码(一般小程序用的多)
                'sourceid' => $sourceid // 客户来源自什么地方
            ];

            // var_dump($data);

            // 插入数据库
            // 返回值: 插入的行数，否则为false
            // 插入数据到数据库前先进行验证规则验证(验证器一般用在插入、查询时)
            // 验证不通过则返回的是定义好的提示文案，下面判断不通过就自然的调用getError()方法进行输出提示文案中的错误信息
            $result = $this -> BusinessModel -> validate('common/Business/Business') -> save($data);

            // 判断是否成功插入
            if($result === false) {
                // 错误信息
                $this -> error($this -> BusinessModel -> getError());
                exit;
            } else {
                // 插入成功就跳转回登录界面
                $this -> success('注册成功, 请登录', url('home/index/login'));
                exit;
            }
        }

        // 渲染模板
        return $this -> view -> fetch();
    }

    // 登录
    public function login() {
        // 临时关闭模板布局(下满是tp5用的)
        $this->view->engine->layout(false);

        // 判断是否为post提交
        if($this -> request -> isPost()) {
            // 拿到手机和密码
            $mobile = $this -> request -> param('mobile', '', 'trim'); // 手机
            $password = $this -> request -> param('password', '', 'trim'); // 密码

            // 判断手机是否为空
            if(empty($mobile)) {
                $this -> error('手机号不能为空');
                exit;
            }

            // 判断密码是否为空
            if(empty($password)) {
                $this -> error('密码不能为空');
                exit;
            }

            // 没问题就根据手机查询数据
            // 只查询一条数据
            $business = $this->BusinessModel -> where(['mobile' => $mobile]) -> find();

            if(!$business) {
                $this -> error('用户不存在');
                exit;
            }

            // 判断密码是否正确(将拿到的用户数据中的密码盐和用户输入的密码盐比对)
            $salt = $business['salt'];
            $repass = md5($password . $salt);

            // var_dump($business['password']);
            // var_dump($repass);

            if($repass != $business['password']) {
                $this -> error('密码错误');
                exit;
            }

            // 将信息存储到cookie中便于渲染模板后读取数据
            $data = [
                'id' => $business['id'],
                'mobile' => $business['mobile'],
                'nickname' => $business['nickname'],
                'avatar' => $business['avatar'],
            ];

            // 放到cookie中
            cookie('LoginAuth', $data);

            // 登陆成功
            $this -> success('登录成功', url('/home/business/business/index'));

        }

        //渲染模板
        return $this -> view -> fetch();
    }

    // 退出登录
    public function logout(){
        // 删除cookie
        cookie('LoginAuth', null);
        $this->success('退出成功', url('/home/index/login'));
        exit;
    }
}

// 公共的东西找common
// 控制器就找controller
// 模型找model
// 视图找view
// 公共函数库找common.php