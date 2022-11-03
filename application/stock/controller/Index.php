<?php
namespace app\stock\controller;

use think\Controller;

class Index extends Controller {
    public function __construct() {
        parent::__construct();

        $this -> BusinessModel = model('Business.Business');
        $this -> OrderModel = model('Order.Order');
        $this -> VisitModel = model('Business.Visit');
        $this -> ReceiveModel = model('Business.Receive');
        $this -> SourceModel = model('Business.Source');

        // 时间条件数组
        $this -> TimeList = [];

        $year = date("Y"); // 2022

        // 时间封装
        for($i = 1; $i <= 12; $i++) {
            // strtotime用于将任何字符串日期和时间转为时间戳
            $time = strtotime($year . "-" . $i);

            // 获取每个月第一天
            $start = date("Y-m-01", $time);

            // 每个月最后一天
            $end = date("Y-m-t", $time);

            $this -> TimeList[] = [strtotime($start), strtotime($end)];
        }
    }

    // 查询总数
    public function total() {
        // 总订单数
        $OrderTotal = $this -> OrderModel -> count();

        // 算总和，销售额
        $AmountTotal = $this -> OrderModel -> sum('amount');

        $BusinessTotal = $this -> BusinessModel -> count();

        $res = [
            'OrderTotal' => $OrderTotal,
            'AmountTotal' => $AmountTotal,
            'BusinessTotal' => $BusinessTotal,
        ];

        $this -> success('返回总数', null, $res);
    }

    // 数量统计
    public function business() {
        $status0 = [];
        $status1 = [];

        foreach($this -> TimeList as $item) {

            // 未认证
            $where = [
                'status' => 0,
                // between是数据库中用于选取介于两个值之间的数据范围
                'createtime' => ['between', $item]
            ];

            $status0[] = $this -> BusinessModel -> where($where) -> count();

            // 已认证
            $where = [
                'status' => 1,
                // between是数据库中用于选取介于两个值之间的数据范围
                'createtime' => ['between', $item]
            ];

            $status1[] = $this -> BusinessModel -> where($where) -> count();
        }

        // 返回数据
        $this -> success('返回客户统计', null, ['status0' => $status0, 'status1' => $status1]);

    }

    // 回访统计
    public function visit() {
        $VisitCount = [];

        foreach($this -> TimeList as $item) {
            $where = [
                'createtime' => ['between', $item]
            ];

            $VisitCount[] = $this -> VisitModel -> where($where) -> count();
        }

        $this -> success('返回回访统计', null, $VisitCount);
    }

    // 客户被领取统计
    public function receive() {
        // 初始化数据
        $apply = $allot = $recovery = [];

        foreach($this -> TimeList as $item) {
            // 申请
            $where = [
                'status' => 'apply',
                'applytime' => ['between', $item]
            ];

            $apply[] = $this -> ReceiveModel -> where($where) -> count();
            
            // 分配
            $where = [
                'status' => 'allot',
                'applytime' => ['between', $item]
            ];

            $allot[] = $this -> ReceiveModel -> where($where) -> count();
            
            // 回收
            $where = [
                'status' => 'recovery',
                'applytime' => ['between', $item]
            ];

            $recovery[] = $this -> ReceiveModel -> where($where) -> count();
        }

        $result = [
            'apply' => $apply,
            'allot' => $allot,
            'recovery' => $recovery,
        ];

        $this -> success('领取统计', null, $result);
    }

    // 客户订单统计
    public function Order() {
        // 通过一个个查询将不同的订单状态获取
        $status1 = $this -> OrderModel -> where(['status' => 1]) -> count();
        $status2 = $this -> OrderModel -> where(['status' => 2]) -> count();
        $status3 = $this -> OrderModel -> where(['status' => 3]) -> count();
        $status4 = $this -> OrderModel -> where(['status' => 4]) -> count();

        // 将一个个不同的订单状态封装返回
        $res = [
            ['name' => '已支付', 'value' => $status1],
            ['name' => '已发货', 'value' => $status2],
            ['name' => '已收货', 'value' => $status3],
            ['name' => '已完成', 'value' => $status4],
        ];

        $this -> success('订单统计', null, $res);
    }

    // 客户来源统计
    public function Source() {
        $data = [];

        // 查询所有来源（返回的是对象类型的来源）
        $sourcelist = $this -> SourceModel -> select();

        // 通过遍历将数组中对象遍历出来
        foreach ($sourcelist as $item) {
            // 通过遍历拿到来源的id，通过 sourceid = 来源id 来筛选各个客户来源于哪
            $count = $this -> BusinessModel -> where(['sourceid' => $item['id']]) -> count();

            $data[] = [
                'name' => $item['name'],
                'value' => $count
            ];
        }

        $this -> success('来源统计', null, $data);
    }
}
