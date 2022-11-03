<?php
namespace app\shop\controller\business;

use think\Controller;

class Base extends Controller {
    public function __construct() {
        parent::__construct();

        // 6、导入用户表的全局模型
        $this -> BusinessModel = model('Business.Business');
        $this -> EMSModel = model('Ems');
    }

    // 验证是否有登录
    public function check() {
        // 1、判断是否是ajax
        if($this->request-> isAjax()) {
            $busid = $this -> request -> param('busid', 0);
            $mobile = $this -> request -> param('mobile', '', 'trim');

            // 2、组装数据
            $where = [
                'id' => $busid,
                'mobile' => $mobile,
            ];

            // 3、根据条件查询
            $business = $this -> BusinessModel -> where($where) -> find();

            // 4、判断是否查询成功
            if($business) {
                $this -> success('验证登录成功', null, $business);
                exit;
            } else {
                $this -> error('验证登录失败', null);
                exit;
            }
        }
    }

    // 用户注册
    public function register() {
        if($this->request->isAjax()) {
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');
            $repass = $this->request->param('repass', '', 'trim');

            // 1、判断密码和二次密码
            if($password != $repass) {
                $this -> error('两次的密码不一样');
                exit;
            }

            // 2、生成密码盐
            $salt = build_ranstr();

            // 2-2、组合密码盐
            $password = md5($password.$salt);

            // 3、查询用户渠道来源
            $sourceid = model('Business.Source') -> where(['name' => '家居商城']) -> value('id');

            // 4、邀请码生成
            $invitecode = build_ranstr();

            // 5、封装数据
            $data = [
                'mobile' => $mobile,
                'password' => $password,
                'salt' => $salt,
                'sourceid' => $sourceid,
                'invitecode' => $invitecode,
                'deal' => 0, // 成交状态为未成交
                'money' => 0, // 余额为0
            ];

            // 7、插入数据
            $result = $this -> BusinessModel -> validate('common/Business/Business') -> save($data);

            // 8、判断语句是否执行成功
            if($result === FALSE) {
                // 8-2、报错信息让模型给
                $this -> error($this-> BusinessModel -> getError());exit;
            } else {
                $this -> success('注册成功, 登录');
                exit;
            }
        }
    }

    // 用户登录
    public function login() {
        // 1、判断是否是ajax请求
        if($this -> request -> isAjax()) {
            $mobile = $this -> request -> param('mobile', '', 'trim');
            $password = $this -> request -> param('password', '', 'trim');

            // 2、根据手机号查询business数据表中的用户是否存在
            $business = $this -> BusinessModel -> where(['mobile' => $mobile]) -> find();

            // 3、判断返回的信息是否正确
            if(!$business) {
                $this -> error('用户不存在');
                exit;
            }

            // 4、验证密码是否正确
            $salt = $business['salt'];

            // 5、将用户输入的密码将其加密和数据库中的密码比对
            $repass = md5($password.$salt);
            if($repass != $business['password']) { // 如果用户输入的密码不等于模型返回的密码
                $this -> error('密码错误');
                exit;
            }

            // 6、登录成功跳转大奥首页
            $this -> success('登录成功', '/business/base/index', $business);
            exit;
        }
    }

    // 个人资料修改
    public function profile() {
        // 判断是否是ajax请求
        if($this -> request -> isAjax()) {
            // 获取全部数据
            $params = $this->request->param();

            // 取出页面提交的用户信息
            $id = $this->request->param('id', 0, 'trim');
            $nickname = $this->request->param('nickname', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '', 'trim');
            $province = $this->request->param('province', '', 'trim');
            $city = $this->request->param('city', '', 'trim');
            $district = $this->request->param('district', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 判断用户是否存在
            $business = $this -> BusinessModel -> find($id);
            
            // 数据库中如果没有当前用户
            if(!$business)
            {
                $this->error('用户不存在');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $id,
                'nickname' => $nickname,
                'email' => $email,
                'gender' => $gender,
                'province' => $province,
                'city' => $city,
                'district' => $district,
            ];

            // 判断是否修改过邮箱(有就将status改为0)
            if($email != $business['email']) {
                $data['status'] = 0; // 修改过说明要重新进行邮箱验证
            }

            // 判断密码是否要修改
            if(!empty($password)) {
                // 要修改就要重新生成密码盐
                $salt = build_ranstr();

                $password = md5($password.$salt);

                $data['password'] = $password;
                $data['salt'] = $salt;
            }

            // 接收文件上传
            if(isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0)
            {
                // 调用公共方法
                $success = build_upload('avatar');

                if($success['result'])
                {
                    //上传成功就将图片放到avatar中
                    $data['avatar'] = $success['data'];
                }else
                {
                    //上传失败
                    $this->error($success['msg']);
                    exit;
                }
            }

            // 更新当前用户数据
            $res = $this -> BusinessModel -> validate('common/Business/Business.ShopProfile') -> isUpdate(true) -> save($data);

            // 判断是否更新成功
            if($res === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            } else {
                // 判断旧图片是否存在, 存在就删除并更新cookie
                if(isset($data['avatar'])) {
                    @is_file(".".$business['avatar']) && unlink(".".$business['avatar']);
                }

                // 将最新的数据返回
                $update = $this -> BusinessModel -> find($id);

                // 提示更新成功并跳转到主页
                $this -> success('修改成功', '/business/base/index', $update);
                exit;
            }

        }
    }

    // 发送邮箱验证码
    public function sendems() {
        // 1、判断是否是Ajax
        if($this -> request -> isAjax()) {
            // 1-2、获取当前用户id
            $id = $this -> request -> param('id', 0, 'trim');

            // 1-3、判断数据库中是否有当前用户
            $business = $this -> BusinessModel -> find($id);

            // 1-4、判断查询的结果
            if(!$business) {
                $this -> error('用户不存在');
                exit;
            }

            // 2、获取页面发送的邮箱地址
            $email = trim($business['email']);

            // 2-2、判断获取的邮箱是否为空
            if(empty($email)) {
                $this -> error('邮箱为空, 请填写邮箱再试一遍');
                exit;
            }

            // 3、生成一个6位数的验证码
            $code = build_ranstr(6);

            // 4、组装数据
            $data = [
                'event' => 'EmailCheck', // 自定义的内容
                'email' => $email,
                'code' => $code,
            ];

            // 5、开启事务回滚,
            $this -> EMSModel -> startTrans();

            // 6、将数据插入到pro_ems数据库中
            $res = $this -> EMSModel -> save($data);

            // 6-2、判断插入数据是否成功
            if($res === FALSE) {
                $this -> error('验证码添加失败');
                exit;
            }

            // 7、调用方法
            $success = send_email($email, $code);

            // 8、判断邮箱是否发送成功
            if(!$success['res']) {
                // 8-2、失败就回滚记录
                $this -> EMSModel -> rollback();

                $this -> error($success['msg']);
                exit;
            } else {
                // 提交事务
                $this -> EMSModel->commit();

                $this -> success('验证码发送成功，请注意查收');
                exit;
            }
        }
    }

    // 验证邮箱
    public function checkems() {
        if($this -> request -> isAjax()) {
            $id = $this -> request -> param('id', 0, 'trim');
            $code = $this -> request -> param('code', 0, 'trim');

            // 判断用户是否存在
            $business = $this -> BusinessModel -> find($id);

            if(!$business) {
                $this -> error('用户不存在');
                exit;
            }

            // 判断邮箱是否为空
            if(empty($business['email'])) {
                $this -> error('您的邮箱为空, 先修改邮箱后重新提交');
                exit;
            }

            // 判断当前用户的cookie中的status是否为1
            if($business['status']) {
                $this -> error('您已通过了邮箱验证, 无须重复验证');
                exit;
            }

            // 根据条件查询出验证码记录
            $where = [
                'email' => $business['email'],
                'code' => $code
            ];

            // 查询一条数据
            $ems = $this -> EMSModel -> where($where) -> find();

            // 判断查询是否成功
            if(!$ems) {
                $this -> error('验证码有误，请重新输入');
                exit;
            }

            // 发送给用户的验证码有效期(生成的那一刻的时间再加上1天时间)
            $checktime = $ems['createtime'] + 3600*24;

            // 判断设置的时间小于当前系统时间就说验证码过期
            if($checktime < time()) {
                // 删除数据库中的过期验证码字段
                $this -> EMSModel -> destroy($ems['id']);

                $this -> error('验证码已过期');
                exit;
            }

            // 更新验证码状态
            $this -> BusinessModel -> startTrans(); // 开启事务
            $this -> EMSModel -> startTrans(); // 开启事务

            // 组装数据, 要更新用户表
            $BusessinData = [
                'id' => $business['id'],
                'status' => 1
            ];

            // 根据用户id更新status字段
            $BusessinStatus = $this->BusinessModel->isUpdate(true)->save($BusessinData);

            // 判断是否成功
            if($BusessinStatus === FALSE) {
                $this -> error('更新用户验证状态失败');
                exit;
            }

            // 删除验证记录
            $EMStatus = $this->EMSModel->destroy($ems['id']);

            // 判断删除是否成功， 删除未成功就要进行事务回滚
            if($EMStatus === FALSE) {
                $this -> BusinessModel -> rollback();
                $this -> error('验证码删除失败');
                exit;
            }

            // 判断只要更新用户的status或验证码状态失败就回滚记录
            if($BusessinStatus === FALSE || $EMStatus === FALSE)
            {
                $this->EMSModel->rollback();
                $this->BusinessModel->rollback();
                $this->error('验证失败');
                exit;
            }else
            {
                //提交事务
                $this->BusinessModel->commit();
                $this->EMSModel->commit();
                $this->success('邮箱验证成功');
                exit;
            }

        }
    }
}
