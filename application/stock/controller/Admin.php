<?php
namespace app\stock\controller;

use think\Controller;
use fast\Random;

class Admin  extends Controller{
    public function __construct() {
        parent::__construct();
        $this -> AdminModule = model('Admin.Admin');
    }

    // 微信登录
    public function login() {
        if($this -> request -> isPost()) {
            // 临时凭证
            $code = $this -> request -> param('code', '', 'trim');

            // 调用当前控制器下封装的微信请求code2ession
            $res = $this -> code2ession($code);

            // 检查是否有openid，有就赋值没有就空字符串
            $openid = isset($res['openid']) ? $res['openid'] : '';

            // 判断$openid是否为空
            if(empty($openid)) {
                $this -> error('授权失败, 无法获取openid');
                exit;
            }

            // 根据微信给的openid查询管理员是否绑定过
            $admin = $this -> AdminModule -> where(['openid' => $openid]) -> find();

            // 如果管理员没有绑定
            if($admin) {
                // 绑定过的就成功
                $this -> success('授权成功', null, $admin);
                exit;
            } else {
                // 没绑定过的就成功，携带openid参数跳转到login页面并让他绑定给用户输入的账号
                $url = '/pages/admin/login?openid=' . $openid; // 带有openid参数的链接

                $this -> success('授权成功, 请先绑定账号',  $url, false);
                exit;
            }

            $this -> success('授权成功');
            exit;
        }
    }

    // 绑定账号
    public function bind() {
        // 判断是否post请求
        if($this -> request -> isPost()) {
            $username = $this -> request -> param('username', '', 'trim');
            $password = $this -> request -> param('password', '', 'trim');
            $openid = $this -> request -> param('openid', '', 'trim');

            // 判断openid是否为空
            if(empty($openid)) {
                $this -> error('授权信息未知，请重新授权');
                exit;
            }

            // 通过输入的账号查询admin表中的管理员
            $admin = $this -> AdminModule -> where(['username' => $username]) -> find();

            // 判断是否查询成功
            if(!$admin) {
                $this -> error('管理员不存在');
                exit;
            }

            // 查询成功就拿盐和密码跟用户输入的密码比对
            $salt = $admin['salt'];
            $repass = $admin['password'];

            // 将md5加密后的密码和密码盐拼接并再一次通过md5加密一次
            $password = md5(md5($password).$salt);

            // 判断密码是否一致
            if($repass != $password) {
                $this -> error('绑定账号的密码错误');
                exit;
            }

            // 判断openid是否为空
            if(!empty($admin['openid'])) {
                $this -> error('该管理员已被绑定，不能重复绑定');
                exit;
            }

            // 将对应管理员的openid更新
            $data = [
                'id' => $admin['id'],
                'openid' => $openid
            ];

            // 更新管理员的openid
            $res = $this -> AdminModule -> isUpdate(true) ->save($data);

            // 判断是否更新成功
            if($res === FALSE) {
                $this -> error($this -> AdminModule -> getError());
                exit;
            }

            // 将管理员最新的数据返回出去，因为绑定成功要跳转到首页并关闭其它所有界面
            $last = $this -> AdminModule -> find($admin['id']);
            $this -> success('绑定账号成功', '/pages/index/index', $last);
            exit;
        }
    }

    // 账号密码登录
    public function signin() {
        if($this -> request-> isPost()) {
            $username = $this -> request -> param('username', '', 'trim');
            $password = $this -> request -> param('password', '', 'trim');

            $admin = $this -> AdminModule -> where(['username' => $username]) -> find();

            // 判断是否存在
            if(!$admin) {
                $this -> error('管理员不存在');
                exit;
            }

            $salt = $admin['salt'];
            $repass = $admin['password'];

            $password = md5(md5($password) . $salt);

            if($repass != $password) {
                $this -> error('账号密码错误');
                exit;
            }

            $this -> success('登录成功', '/pages/index/index', $admin);
            exit;
        }
    }

    // 解除绑定
    public function wechat() {
        if($this -> request -> isPost()) {
            $adminid = $this -> request -> param('adminid', 0, 'trim');

            // 根据id查询管理员是否存在
            $admin = $this -> AdminModule -> find($adminid);

            // 如果不存在
            if(!$admin) {
                $this -> error('管理员不存在');
                exit;
            }

            // 存在但如果没绑定过openid
            if(empty($admin['openid'])) {
                $this -> error('您暂未绑定微信账号');
                exit;
            }

            // 上面两个都没问题就封装数据
            $data = [
                'id' => $admin['id'],
                'openid' => null
            ];

            // 更新当前管理员的openid
            $res = $this -> AdminModule -> isUpdate(true) -> save($data);

            // 判断是否成功
            if($res === FALSE) {
                $this -> error('解绑失败');
                exit;
            } else {
                // 解绑成功就将当前管理员的最新数据返回以便覆盖本地存储
                $admin = $this->AdminModule->find($adminid);
                $this->success('解绑成功', null, $admin);
                exit;
            }
        }
    }

    // 修改资料
    public function profile() {
        if($this -> request -> isPost()) {
            $adminid = $this -> request -> param('adminid', 0, 'trim');
            $nickname = $this -> request -> param('nickname', '', 'trim');
            $mobile = $this -> request -> param('mobile', '', 'trim');
            $email = $this -> request -> param('email', '', 'trim');
            $password = $this -> request -> param('password', '', 'trim');

            // 查询管理员是否存在
            $admin = $this -> AdminModule -> find($adminid);

            if(!$admin) {
                $this -> error('管理员不存在');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $adminid,
                'nickname' => $nickname,
                'mobile' => $mobile,
                'email' => $email,
            ];

            // 如果密码不为空
            if(!empty($password)) {
                // 重新生成密码盐
                $salt = Random::alnum(); // 生成随机字母和数字
                $password = md5(md5($password) . $salt);
                $data['salt'] = $salt;
                $data['password'] = $password;
            }

            // 更新资料
            $res = $this -> AdminModule -> isUpdate(true) -> save($data);

            // 判断更新是否成功
            if($res === FALSE) {
                $this -> error('更新资料失败');
                exit;
            }

            // 查询最新的管理员数据，以便覆盖本地存储旧数据
            $last = $this -> AdminModule -> find($adminid);
            $this -> success('更新资料成功', '/pages/admin/index', $last);
            exit;
        }
    }

    // 修改头像
    public function avatar() {
        if($this -> request -> isPost()) {
            // 获取传递过来的data中的adminid管理员id数据
            $adminid = $this -> request -> param('adminid', 0, 'trim');
            
            // 查询管理员是否存在
            $admin = $this -> AdminModule -> find($adminid);

            // 如果管理员不存在
            if(!$admin) {
                $this -> error('管理员不存在');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $adminid
            ];

            // 接收新头像的上传
            if(isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
                // 调用common文件中公共的方法
                $success = build_upload('avatar');

                // 判断是否上传成功
                if($success['result']) {
                    $data['avatar'] = $success['data'];
                } else {
                    $this -> error($success['msg']);
                    exit;
                }
            }

            // 更新管理员头像数据
            $res = $this -> AdminModule -> isUpdate(true) -> save($data);

            // 判断是否更新成功
            if($res === FALSE) {
                $this -> error('更新头像失败');
                exit;
            }

            // 删除旧头像
            if(isset($data['avatar'])) {
                // 判断旧头像是否存在，存在就删除
                @is_file("." . $admin['avatar']) && unlink("." . $admin['avatar']);
            }

            // 返回新头像地址，并获取云课堂后台的系统配置中的site选项
            $url = config('site.cdnurls') ? config('site.cdnurls') : '';

            // 合并域名
            $avatar = trim($data['avatar'], '/');
            $avatar = $url . '/' . $avatar; // http://www.projects.com/图片路径

            // 将其原路数据返回
            $this -> success('头像更新成功', null, $avatar);
            exit;
        }
    }

    // 微信服务端发送GET请求
    public function code2ession($js_code = null){
        
        if($js_code)
        {
            // 小程序 appid  换成自己的
            $appid = 'wxb0c2722714a992a4';

            // AppSecret(小程序密钥) 换成自己的
            $appSecret = '4ec1a1ff2610f49bf3cd4b50acbf1543';

            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appSecret&js_code=$js_code&grant_type=authorization_code";

            //发起get请求
            $result =$this->https_request($url);

            //获取结果 将json转化为数组
            $resultArr = json_decode($result,true);

            return $resultArr;
        }else{
            return false;
        }

    } 

    // http请求 利用php curl扩展去发送get 或者 post请求 服务器上面一定要开启 php curl扩展
    // https://www.php.net/manual/zh/book.curl.php
    protected function https_request($url,$data = null) {
        if(function_exists('curl_init')){
        $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // 发送会话，返回结果
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        }else{
            return false;
        }
    }

}
