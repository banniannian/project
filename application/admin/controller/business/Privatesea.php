<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

use think\Db;

/**
 * 客户管理
 *
 * @icon fa fa-circle-o
 */
class Privatesea extends Backend
{

    /**
     * Privatesea模型对象
     * @var \app\common\model\business\Privatesea
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('business.business');
        $this->AdminModel = model('admin');
        $this->SourceModel = model('business.source');
        $this->ReceiveModel = model('business.receive');
        $this->model = new \app\common\model\business\Privatesea;
        $this->view->assign("genderList", $this->model->getGenderList());
        $this->view->assign("dealList", $this->model->getDealList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }


    /**
     * 查看
     */
    public function index()
    {
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

            //查询数据
            $list = $this->model
                ->where($where)
                ->where(['adminid' => $this->auth->id])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);


            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');
            $region = $this->request->param('region', '', 'trim');

            $data = [];
            //判断地区是否为空
            if (!empty($region)) {
                //字符串转换为数组
                $region = explode('/', $region);
                $data['province'] = isset($region[0]) ? $region[0] : '';
                $data['city'] = isset($region[1]) ? $region[1] : '';
                $data['district'] = isset($region[2]) ? $region[2] : '';
            }

            // 重新生成密码盐
            $salt = build_ranstr();
            $params['salt'] = $salt;
            $params['password'] = md5($params['password'] . $salt);

            // 生成邀请码
            $params['invitecode'] = build_ranstr();

            $params = array_merge($params, $data);

            $result = $this->model->validate('common/business/business')->save($params);

            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('添加成功');
                exit;
            }
        }

        $user = $this->auth->id;

        // 查询当前管理员名称
        $admin = $this->AdminModel->where(['id' => $user])->order('id asc')->column('id,nickname');

        $this->view->assign('admin', build_select('row[adminid]', $admin, [], ['class' => 'form-control selectpicker']));

        // 查询所有客户来源
        $source = $this->SourceModel->order('id asc')->column('id,name');

        // 将生成好的select下拉框赋值到模板
        $this->view->assign('source', build_select('row[sourceid]', $source, [], ['class' => 'form-control selectpicker']));

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = 0)
    {
        $business = $this->model->find($ids);

        if (!$business) {
            $this->error('用户不存在');
            exit;
        }

        // 判断是否有post提交
        if ($this->request->isPost()) {
            // 接收数据
            $params = $this->request->param('row/a');

            // 将id要补录到数据中
            $params['id'] = $ids;

            //判断密码是否为空，如果不为空就修改密码
            if (!empty($password)) {
                //判断密码是否就等于当前密码
                $newsalt = $business['salt'];
                $repass = md5($business['password'] . $newsalt);

                if ($repass == $business['password']) {
                    $this->error('新密码不能等于当前密码');
                    exit;
                }

                //重新生成密码盐
                $salt = build_ranstr();

                $params['salt'] = $salt;
                $params['password'] = md5($params['password'] . $salt);
            }

            // 更新操作
            $result = $this->model->validate('common/Business/Business.BusEdit')->isUpdate(true)->save($params);

            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                // 判断是否有新图片上传
                // 旧的图片路径 和 表单中提交的图片路径 不一样就说明换图片了
                if ($business['avatar'] != $params['avatar']) {
                    @is_file('.' . $params['avatar']) && @unlink('.' . $business['avatar']);
                }

                $this->success();
                exit;
            }
        }

        $user = $this->auth->id;

        // 查询当前管理员名称
        $admin = $this->AdminModel->where(['id' => $user])->order('id asc')->column('id,nickname');

        $this->view->assign('admin', build_select('row[adminid]', $admin, [], ['class' => 'form-control selectpicker']));

        // 查询所有客户来源
        $source = $this->SourceModel->order('id asc')->column('id,name');

        // 将生成好的select下拉框赋值到模板
        $this->view->assign('source', build_select('row[sourceid]', $source, $business['sourceid'], ['class' => 'form-control selectpicker']));

        $this->view->assign('business', $business);

        return $this->view->fetch();
    }

    /**
     * 删除  回到公海
     */
    public function del($ids = 0)
    {
        $business = $this->model->where(['id' => ['in', $ids]])->select();

        if (!$business) {
            $this->error('用户不存在');
            exit;
        }

        // 开始事务
        $this->model->startTrans();
        $this->ReceiveModel->startTrans();

        //  执行更新语句
        $BusStatus = $this->model->where(['id' => ['in', $ids]])->update(['adminid' => NULL]);

        if ($BusStatus === FALSE) {
            $this->error('删除用户失败');
            exit;
        }
        $ids = explode(',', $ids);

        $data = [];
        // 插入领取表
        foreach ($ids as $item) {
            $data[] = [
                'applyid' => $this->auth->id,
                'status' => 'recovery',
                'busid' => $item
            ];
        }

        $ReStatus = $this->ReceiveModel->saveAll($data);

        if ($ReStatus === FALSE) {
            $this->model->rollback();
            $this->error($this->ReceiveModel->getError());
            exit;
        }

        if ($BusStatus === FALSE || $ReStatus === FALSE) {
            $this->BusStatus->rollback();
            $this->ReStatus->rollback();
            $this->error('删除失败');
            exit;
        } else {
            // 2个步骤都成功了 2个模拟的步骤都要提交事务 真正执行到数据库中
            $this->model->commit();
            $this->ReceiveModel->commit();
            $this->success('删除成功');
            exit;
        }
    }
}

