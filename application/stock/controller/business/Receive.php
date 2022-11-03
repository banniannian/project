<?php
namespace app\stock\controller\business;
use think\Controller;

class receive extends Controller {

    public function __construct() {
        parent::__construct();
        $this -> AdminModule = model('Admin.Admin');
        $this -> ReceiveModel = model('Business.Receive');

        // 接收管理员id查询是否存在
        $this -> adminid = $this -> request -> param('adminid', 0, 'trim');
        $admin = $this -> AdminModule -> find($this -> adminid);
        if(!$admin) {
            $this -> error('管理员不存在');
            exit;
        }

    }

    // 领取列表
    public function index() {
        if($this -> request -> isPost()) {
            $list = $this -> ReceiveModel -> with(['business']) -> where(['applyid' => $this -> adminid]) -> select();

            $this -> success('返回领取记录', null, $list);
            exit;
        }
    }
}
