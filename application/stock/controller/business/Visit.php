<?php
namespace app\stock\controller\business;

use think\Controller;

class Visit extends Controller {

    public function __construct() {
        parent::__construct();
        $this -> AdminModule = model('Admin.Admin');
        $this -> VisitModel = model('Business.Visit');
        $this -> BusinessModel = model('Business.Business');

        // 接收管理员id存放到this中的adminid中, 并查询是否存在
        $this -> adminid = $this -> request -> param('adminid', 0, 'trim');
        $admin = $this -> AdminModule -> find($this -> adminid);
        if(!$admin) {
            $this -> error('管理员不存在');
            exit;
        }
    }

    // 回访列表
    public function index() {
        if($this -> request -> isPost()) {

            // 根据business表，visit中的adminid要等于this中的adminid，且要按时间以降序进行排序
            $list = $this -> VisitModel -> with(['business']) -> where(['visit.adminid' => $this -> adminid]) -> order('createtime desc') -> select();

            $this -> success('返回回访记录', null, $list);
            exit;
        }
    }

    // 查询管理员
    public function business() {
        // 根据管理员id查询指定字段内容(column是根据给的字段查询内容)
        $business = $this -> BusinessModel -> where(['adminid' => $this -> adminid]) -> select();

        $this -> success('客户列表', null, $business);
        exit;
    }

    // 添加回访记录
    public function add() {
        if($this -> request -> isPost()) {
            $params = $this -> request -> param();

            $res = $this -> VisitModel -> validate('common/Business/Visit') -> save($params);

            if($res === FALSE) {
                $this -> error($this -> VisitModel -> getError());
                exit;
            } else {
                $this -> success('添加回访记录成功');
                exit;
            }
        }
    }

    // 编辑回访记录
    public function edit() {
        if($this -> request -> isPost()) {
            $params = $this -> request -> param();

            $visitid = $this -> request -> param('id', 0, 'trim');

            // 根据id查询记录是否存在
            $visit = $this -> VisitModel -> find($visitid);

            if(!$visit) {
                $this->error('回访记录不存在');
                exit;
            }

            $res = $this -> VisitModel -> validate('common/Business/Visit') -> isUpdate(true) -> save($params);

            if($res === FALSE) {
                $this -> error($this -> VisitModel -> getError());
                exit;
            } else {
                $this -> success('添加回访记录成功');
                exit;
            }
        }
    }

    // 查询是否存在
    public function check() {
        if($this -> request -> isPost()) {
            $visitid = $this->request->param('visitid', 0, 'trim');
            
            // 根据id查询记录是否存在
            $visit = $this->VisitModel->with(['business'])->find($visitid);

            // 判断是否查询成功
            if($visit) {
                $this -> success('返回回访记录', null, $visit);
                exit;
            } else {
                $this -> error('返回回访记录不存在');
                exit;
            }
        }
    }

    // 删除回访记录
    public function del() {
        if($this -> request -> isPost()) {
            $visitid = $this -> request -> param('visitid', 0, 'trim');

            // 根据id查询记录是否存在
            $visit = $this -> VisitModel -> with(['business']) -> find($visitid);

            // 判断是否记录存在
            if(!$visit) {
                $this->error('回访记录不存在');
                exit;
            }

            $res = $this -> VisitModel -> destroy($visitid);

            // 判断是否删除成功
            if($res === FALSE) {
                $this -> error($this -> VisitModel -> getError());
                exit;
            } else {
                $this -> success('删除回访记录成功');
                exit;
            }
        }
    }
}
