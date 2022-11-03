<?php
namespace app\shop\controller\business;

use think\Controller;

/**
 * 用户的收货地址
 */

class Address extends Controller {
    public function __construct() {
        parent::__construct();

        $this -> BusinessModel = model('Business.Business');
        $this -> AddressModel = model('Business.Address'); // 收货地址表
        $this -> RegionModel = model('Region'); // 地区表

        // 判断是否是ajax请求(写在这也是为了方便，不用在每个方法中重复书写相同的操作)
        if($this -> request->isAjax()) {
            $id = $this -> request-> param('id', 0, `trim`);

            // 判断当前用户是否存在
            $business = $this -> BusinessModel -> find($id);

            // 判断是否成功
            if(!$business) {
                $this -> error('用户不存在');
                exit;
            }

        }

    }

    // 获取收货地址列表
    public function index() {
        // 判断是否是ajax请求
        if($this -> request->isAjax()) {
            $id = $this -> request -> param('id', 0, 'trim');

            // 查询收货地址表中busid对应当前用户id的收货地址数据
            $address = $this -> AddressModel -> where(['busid' => $id]) -> select();

            $this -> success('返回收货地址', null, $address);
        }
    }

    // 获取收货地址添加
    public function add() {
        // 判断是否为ajax请求
        if($this -> request -> isAjax()) {
            // 接收数据
            $id = $this->request -> param('id', 0, 'trim');
            $consignee = $this -> request->param('consignee', '', 'trim');
            $mobile = $this -> request->param('mobile', '', 'trim');
            $address = $this -> request->param('address', '', 'trim');
            $code = $this -> request->param('code', '', 'trim');
            $status = $this -> request->param('status', 0, 'trim');

            // 组装数据
            $data = [
                'busid' => $id,
                'consignee' => $consignee,
                'mobile' => $mobile,
                'address' => $address,
                'status' => $status,
            ];

            // 根据地区代码查询到指定地区字段
            $path = $this -> RegionModel -> where(['code' => $code]) -> value('parentpath');

            // 拿到的地区数据字符串转为数组
            $RegionCode = explode(',', $path);

            // 判断地区中的各个数据是否为空
            $data['province'] = isset($RegionCode[0]) ? $RegionCode[0] : '';
            $data['city'] = isset($RegionCode[1]) ? $RegionCode[1] : '';
            $data['district'] = isset($RegionCode[2]) ? $RegionCode[2] : '';

            // 在勾选默认地址的情况下
            if($status) { // 是否等于1
                // 将当前用户的其它地址全部改为非默认(因为默认地址只能有一个)
                $this -> AddressModel -> where(['busid'=> $id]) -> update(['status' => 0]);
            }

            // 执行插入语句
            $res = $this -> AddressModel -> validate('common/Business/Address') -> save($data);

            // 判断是否成功
            if($res === FALSE) {
                $this -> error($this -> AddressModel -> getError());
                exit;
            } else {
                $this -> success('添加地址成功');
                exit;
            }
        }
    }

    // 查询收货地址
    public function search() {
        // 判断是否ajax
        if($this -> request -> isAjax()) {
            // 拿到传递的用户和用户地址的id
            $id = $this -> request -> param('id', 0, 'trim');
            $addrid = $this -> request -> param('addrid', 0, 'trim');

            // 判断地址是否存在于数据库中
            $where = [
                'id' => $addrid,
                'busid' => $id
            ];

            $address = $this -> AddressModel -> where($where) -> find();

            // 判断是否成功
            if($address) {
                $this -> success('返回收货地址', null, $address);
                exit;
            } else {
                $this -> error('未找到收货地址');
                exit;
            }
        }
    }

    // 编辑地址
    public function edit() {
        // 判断是否为ajax请求
        if($this -> request -> isAjax()) {
            // 接收数据
            $id = $this->request -> param('id', 0, 'trim');
            $addrid = $this->request -> param('addrid', 0, 'trim');
            $consignee = $this -> request->param('consignee', '', 'trim');
            $mobile = $this -> request->param('mobile', '', 'trim');
            $address = $this -> request->param('address', '', 'trim');
            $code = $this -> request->param('code', '', 'trim');
            $status = $this -> request->param('status', 0, 'trim');

            $where = [
                'id' => $addrid,
                'busid' => $id
            ];

            $check = $this -> AddressModel -> where($where) -> find();

            // 判断收货地址是否存在
            if(!$check) {
                $this -> error('收货地址不存在');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $addrid,
                'busid' => $id,
                'consignee' => $consignee,
                'mobile' => $mobile,
                'address' => $address,
                'status' => $status,
            ];

            // 根据地区代码查询到指定地区字段
            $path = $this -> RegionModel -> where(['code' => $code]) -> value('parentpath');

            // 拿到的地区数据字符串转为数组
            $RegionCode = explode(',', $path);

            // 判断地区中的各个数据是否为空
            $data['province'] = isset($RegionCode[0]) ? $RegionCode[0] : '';
            $data['city'] = isset($RegionCode[1]) ? $RegionCode[1] : '';
            $data['district'] = isset($RegionCode[2]) ? $RegionCode[2] : '';

            // 在勾选默认地址的情况下
            if($status) { // 是否等于1
                // 将当前用户的其它地址全部改为非默认(因为默认地址只能有一个)
                $this -> AddressModel -> where(['busid' => $id]) -> update(['status' => 0]);
            }

            // 执行插入语句
            $res = $this -> AddressModel -> validate('common/Business/Address') -> isUpdate(true) -> save($data);

            // 判断是否成功
            if($res === FALSE) {
                $this -> error($this -> AddressModel -> getError());
                exit;
            } else {
                $this -> success('更新地址成功');
                exit;
            }
        }
    }

    // 删除收货地址
    public function del() {
        if($this -> request -> isAjax()) {
            // 收货地址id和用户id
            $id = $this -> request -> param('id', 0, 'trim');
            $addrid = $this -> request -> param('addrid', 0, 'trim');

            // 判断收货地址是否存在
            $where = [
                'id' => $addrid,
                'busid' => $id,
            ];

            // 查询收货地址表中是否存在地址
            $check = $this -> AddressModel -> where($where) -> find();

            // 判断是否有地址
            if(!$check) {
                $this -> error('收货地址不存在');
                exit;
            }

            // 没问题就执行删除语句
            $res = $this -> AddressModel -> destroy($addrid);

            if($res === FALSE) {
                $this -> error('删除收货地址失败');
                exit;
            } else {
                $this -> success('删除收货地址成功');
                exit;
            }
        }
    }

    // 切换默认地址
    public function check() {
        if($this->request->isAjax()) {
            // 接收用户和收货地址id
            $id = $this -> request -> param('id', 0, 'trim');
            $addrid = $this -> request -> param('addrid', 0, 'trim');

            //判断收货地址是否存在
            $where = [
                'id' => $addrid,
                'busid' => $id
            ];

            $check = $this -> AddressModel -> where($where) -> find();

            // 判断是否成功
            if(!$check) {
                $this -> error('收货地址不存在');
                exit;
            }

            // 将当前用户的全部地址的status设置为0后再将指定的那个地址的status设置为1
            $this->AddressModel->startTrans(); // 开启事务

            $UpdateStatus = $this->AddressModel->where(['busid' => $id])->update(['status' => 0]);

            // 判断是否成功
            if($UpdateStatus === FALSE) {
                $this -> error('更新默认收货地址失败');
                exit;
            }

            // 更新选中的地址
            $res = $this -> AddressModel -> where(['id' => $addrid]) -> update(['status' => 1]);

            // 判断是否成功
            if($res === FALSE) {
                // 失败就回滚失败并报错
                $this -> AddressModel -> rollback();
                $this -> error('更新默认地址失败');
                exit;
            } else {
                // 成功就提交事务
                $this -> AddressModel -> commit();
                $this -> success('更新默认地址成功');
                exit;
            }
        }
    }

    // 下单返回的收货地址
    public function default() {
        // 获取用户id
        $busid = $this -> request-> param('id', 0, 'trim');

        // 将用户id和默认地址id封装
        $where = [
            'busid' => $busid,
            'status' => 1 // 默认地址
        ];

        // 查询当前用户的默认地址数据
        $address = $this -> AddressModel -> where($where) -> find();

        // 判断是否有地址数据返回
        if($address) {
            $this -> success('返回默认地址', null, $address);
            exit;
        } else {
            $address = $this -> AddressModel -> where(['busid' => $busid]) -> find();

            if($address) {
                $this -> success('返回默认地址', null, $address);
                exit;
            } else {
                $this -> error('暂无收货地址');
                exit;
            }
        }
    }

    
}