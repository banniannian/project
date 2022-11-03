<?php

namespace app\common\controller;

use think\Controller;

    /**
     * 前台公共控制器
     */

class Home extends Controller
{
    // 不需要登录的方法(当里面有指定的方法名就不会调用IsLogin方法)
    protected $noNeedLogin = [];

     public function __construct() {
        // 父类继承
        // Home继承自Controller
        parent::__construct();

        // 全局模型加载
        $this -> BusinessModel = model('Business.Business');

        // 全局身份变量
        $this -> LoginAuth = null;

        // 获取当前访问的控制器方法
        $action = $this -> request -> action();

        // 判断action是否在 noNeedLogin 数组中，在就不需要登录
        if(!in_array($action, $this -> noNeedLogin) && !in_array('*', $this -> noNeedLogin)) {
            // 自动调用判断是否登录的方法
            $this -> IsLogin();
        }

     }

    //  判断是否有登录
    public function IsLogin() {

        // 用户可能会伪造假cookie要拿到cookie存储的信息在数据库中查询
        $LoginAuth = cookie('LoginAuth') ? cookie('LoginAuth') : [];
        $busid = isset($LoginAuth['id']) ? $LoginAuth['id'] : 0;
        $busmobi = isset($LoginAuth['mobile']) ? $LoginAuth['mobile'] : '';

        // 如果id不存在或手机为空
        if(!$busid || empty($busmobi)) {
            // 将伪造的cookie清除
            cookie('LoginAuth', null);
            $this -> error('请重新登录', url('/home/index/login'));
            exit;
        }
        
        // 根据id和手机号查询当前用户信息
        $where = [
            'id' => $busid,
            'mobile' => $busmobi
        ];

        // 单条数据查询
        $LoginAuth = $this -> BusinessModel -> where($where) -> find();

        // 如果查询不到数据
        if(!$LoginAuth) {
            cookie('LoginAuth', null);
            $this -> error('非法登录', url('/home/index/login'));
            exit;
        }

        // cookie给所有子模板(全部文件中的方法)
        $this -> view -> assign('LoginAuth', $LoginAuth);

        // 
        $this -> LoginAuth = $LoginAuth;
    }
}
