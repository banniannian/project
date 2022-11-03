<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

use think\Db;

/**
 * 客户中心
 *
 * @icon fa fa-circle-o
 */
class Highsea extends Backend {

    /**
     * Highsea模型对象
     */
    protected $model = null;

    // 是否进行关联查询
    protected $relationSearcher = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('business.business');
        $this->model = model('Business.Highsea');
        $this->VisitModel = model('business.visit');
        $this->ReceiveModel = model('business.receive');

        // $this->BusinessModel = model('Business.Business');
        // $this->view->assign("mobile", $this->model->getmobile());
        $this->view->assign("genderList", $this->model->getGenderList());
        $this->view->assign("dealList", $this->model->getDealList());
    }

    // 查看
    public function index($ids = 0) {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            //查询总数
            $total = $this->model
                    ->where($where)
                    ->count();

            // //查询数据
            // $list = $this->model
            //         ->with(['category'])
            //         ->where($where)
            //         ->order($sort, $order)
            //         ->limit($offset, $limit)
            //         ->select();

            // $result = array("total" => $total, "rows" => $list);

            $list = $this-> model
                -> where(['adminid' => NULL]) // adminid判断为空的数据全部取出
                -> order($sort, $order)
                -> limit($offset, $limit)
                -> select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this -> view -> fetch();
    }

    // {"id":8,"mobile":"15918465990","nickname":"6号","password":"534607ac2f2453450a595dfc120f09d8","salt":"M2cSzQBzNe","avatar":"\/uploads\/20220925\\75c3c23034d8b42a1a1dfdd4e65958e7.png","gender":"0","sourceid":null,"deal":"1","openid":null,"province":"广州","city":"广州市","district":"荔湾区","adminid":null,"createtime":1664086119,"invitecode":"M2cSzQBzNe","deletetime":null,"money":"80873867.00","gender_text":"保密","deal_text":"已成交"}

    // 详情
    public function info($ids = 0) {
        // 根据id判断用户是否存在
        $uses = $this->model->find($ids);

        // 判断是否查询成功
        if(!$uses) {
            $this -> error('用户不存在');
            exit;
        }

        // 获取用户来源
        $sourceids = Db::name('business_source') -> where('id', $uses['sourceid']) -> value('name');

        // 获取回访记录表
        $visits = Db::name('business_visit') -> where('busid', $uses['id']) -> value(['id', 'content', 'adminid', 'createtime']);


        // 时间戳转时间
        $Times = date('Y-m-d H:i:s', $uses['createtime']);

        // build_select(下拉框名字, 要给的数据是什么, 是否有默认选中的, class名)
        $this -> view -> assign('catelist', build_select('row[gender]', ['0' => '保密', '1' => '男', '2' => '女'], $uses['gender'], ['class' => 'form-control selectpicker', 'disabled']));

        $this -> view -> assign('deals', build_select('row[deal]', ['0' => '未成交', '1' => '已成交'], $uses['deal'], ['class' => 'form-control selectpicker', 'disabled']));

        $this -> view -> assign('uses_info', $uses); // 用户本体全部数据

        $this -> view -> assign('uses_CT', $Times); // 时间

        $this -> view -> assign('uses_sourceids', $sourceids); // 用户来源

        $this -> view -> assign('uses_visits', $visits); // 回访记录

        return $this -> view -> fetch();

    }

    // 自己写的申请
    public function apply_backup($ids = 0) {
        // 根据id判断用户是否存在
        $uses = $this->model->where(['id' => $ids])->select();

        // 判断是否查询成功
        if(!$uses) {
            $this -> error('用户不存在');
            exit;
        }

        // 判断是否重复申请
        // $res = Db::name('business') -> where(['id' => ['in', $ids]] , [$this->auth->id => 'adminid']);
        // echo $this -> model -> getLastSql($res);

        // if($res) {
        //     $this -> error('当前用户已被申请, 请刷新页面');
        //     exit;
        // }

        $res = Db::name('business') -> where(['id' => ['in', $ids]]) -> update(['adminid' => $this -> auth -> id]);

        // 封装日志
        $data = [
            'applyid' => $this -> auth -> id,
            'applytime' => time(),
            'status' => 'apply',
            'busid' => $ids
        ];

        // 管理员操作日志
        $rec = Db::name('business_receive') -> insert($data);

        if($res === FALSE || $rec === FALSE) {
            $this -> error('申请失败');
            exit;
        } else {
            $this -> success('申请成功');
            exit;
        }
    }

    // 申请
    public function apply($ids = 0) {
        // 根据id判断用户是否存在
        $users = $this->model->where(['id' => $ids])->select();

        // 判断申请的用户是否存在
        if(!$users) {
            $this -> error('用户不存在');
            exit;
        }

        $adminid = $this->auth->id;

        $ids = explode(',', $ids);

        // 开启操作表的事务
        $this -> model -> startTrans();
        $this -> ReceiveModel -> startTrans();

        // 更新数据
        $BusStatus = Db::name('business') -> where(['id' => ['in', $ids]]) -> update(['adminid' => $adminid]);

        // 判断是否更新成功
        if(!$BusStatus) {
            $this -> error('申请失败');
            exit;
        }

        $data = [];

        // 如果选择多个客户
        foreach($ids as $item) {
            $data[] = [
                'applyid' => $adminid,
                'status' => 'apply',
                'busid' => $item
            ];
        }

        // 操作日志插入
        $ReStatus = $this -> ReceiveModel -> saveAll($data);

        // 判断日志是否插入成功
        if($ReStatus === FALSE) {
            $this -> model -> rollback();
            $this -> error($this -> ReceiveModel -> getError());
            exit;
        }

        // 判断上面两个是否都完成更新和插入操作
        if($BusStatus === FALSE || $ReStatus === FALSE) {
            $this -> BusStatus -> rollback();
            $this -> ReStatus -> rollback();
            $this -> error('申请失败');
            exit;
        } else {
            $this -> model -> commit();
            $this -> ReceiveModel -> commit();
            $this -> success('申请成功');
            exit;
        }
    }

    // 分配
    public function recovery($ids = 0) {

        // $result = Db::name('admin') -> where(['adminid' => $this->auth->id]);


        // 判断是否重复申请
        // $res = Db::name('business') -> where(['id' => ['in', $ids]] , [$this->auth->id => 'adminid']);
        // echo $this -> model -> getLastSql($res);

        // if($res) {
        //     $this -> error('当前用户已被申请, 请刷新页面');
        //     exit;
        // }

        // $where = $this -> auth -> getChildrenAdminIds();
        // $where = json_encode($where);
        // echo \think\Db::name('auth_group_access')->field('uid')->where('group_id', 6)->select();

        // var_dump(\think\Db::name('admin')->field('username')->select());

        // echo 88;

        // exit;
        // $res = Db::name('business') -> where(['id' => ['in', $ids]]) -> update(['adminid' => $this->auth->id]);
        // // echo $this -> model -> getLastSql($result);

        // if($res === FALSE) {
        //     echo 6666;
        //     $this -> error('分配失败');
        //     exit;
        // } else {
        //     echo 8888;
        //     $this -> success('分配成功');
        //     exit;
        // }

        // 判断是否是post请求
        if($this -> request -> isPost()) {
            // 拿到管理员id
            $list = $this -> request -> param();

            // 根据选中的公海用户的id判断用户是否存在
            $uses = $this -> model -> find($ids);

            // 判断是否查询成功
            if(!$uses) {
                $this -> error('用户不存在');
                exit;
            }

            // 将用户的admin更新为分配的管理员
            $res = Db::name('business') -> where(['id' => ['in', $ids]]) -> update(['adminid' => $list['row']['adminid']]);

            // 封装日志
            $data = [
                'applyid' => $list['row']['adminid'],
                'applytime' => time(),
                'status' => 'allot',
                'busid' => $ids
            ];

            // 管理员操作日志
            $rec = Db::name('business_receive') -> insert($data);

            if($res === FALSE || $rec === FALSE) {
                $this -> error('分配失败');
                exit;
            } else {
                $this -> success('分配成功');
                exit;
            }


        }

        // 获取管理员表中的数据给页面渲染
        $adminlist = model('admin') -> column('id, nickname');

        $this -> view -> assign('admin', build_select('row[adminid]', $adminlist, [], ['class' => 'form-control selectpicker']));

        return $this -> view -> fetch();
    }

    // ===================================================================

    // 查看当前客户的回访记录
    public function visit($ids = 0) {
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this-> selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this-> buildparams();

            //查询总数
            $total = $this->model
                ->where($where)
                ->count();

            //查询数据
            $list = $this->VisitModel
                ->with('business')
                ->where(['busid' => $ids])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $res = array("total" => $total, "rows" => $list);

            return json($res);
        }
    }

    // 查看当前客户的被申请记录

    public function receive($ids = 0) {

        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            //查询总数
            $total = $this->ReceiveModel
                ->where($where)
                ->count();

            //查询数据
            $list = $this->ReceiveModel
                ->with('business')
                ->where(['busid' => $ids])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();


            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
    }


}
