<?php

namespace app\home\controller\business;

use app\common\controller\Home;

/**
 * 客户订单控制器
*/

class Order extends Home {
    // 访问订单需要登录
    protected $noNeedLogin = [];

    // 初始化继承自Home
    public function __construct() {
        parent::__construct();

        $this-> OrderModel = model('Subject.Order');
        $this -> RecordModel = model('Business.Record');
    }

    // 客户订单列表
    public function index(){
        // 订单
        $orderlist = $this -> OrderModel
                    // model/Subject中的方法名(可无限联表)
                    -> with (['subject', 'business'])
                    -> where(['busid' => $this -> LoginAuth['id']])
                    -> order('createtime DESC')
                    -> select();

        // 消费记录
        $recordlist = $this -> RecordModel -> where(['busid' => $this -> LoginAuth['id']]) -> select();


        // 将数据返回出去
        $this -> view -> assign([
            'orderlist' =>  $orderlist,
            'recordlist' =>  $recordlist,
        ]);

        return $this -> view -> fetch();
    }

    // 订单评价
    public function comment($orderid = 0){
        // 根据订单id判断订单是否存在
        $order = $this -> OrderModel -> with('subject') -> find($orderid);
        // var_dump($order);
        // exit;

        // 如果不存在就报错
        if(!$order) {
            $this -> error('订单不存在');
            exit;
        }

        // 判断是为post请求
        if($this -> request -> isPost()) {
            // 获取评论内容
            $comment = $this -> request -> param('comment', '', 'trim');
            $rating = $this -> request -> param('another-rating-stars-value', '', 'trim');
            // var_dump($this -> request);
            // exit;

            // 封装数据
            $data = [
                'id' => $order['id'],
                'comment' => $comment, // 内容
                'rate' => $rating, // 星级
            ];

            // 自定义验证器
            $validate = [
                // 规则
                [
                    'comment' => 'require',
                    'rate' => 'number|in:0,1,2,3,4,5',
                ],
                // 提示的文案
                [
                    'comment.require' => '评价内容必填',
                    'rate.number' => '评分内容必须是数字',
                    'rate.in' => '评分内容必须在0~5之间',
                ]
            ];

            // $result = $this -> OrderModel -> validate($validate[0], $validate[1]) -> isUpdate(true) -> save($data);

            // 使用...概括内容
            $result = $this -> OrderModel -> validate(...$validate) -> isUpdate(true) -> save($data);

            // 如果更新数据失败
            if($result === FALSE) {
                $this -> error($this -> OrderModel -> getError());
                exit;
            } else {
                $this -> success('评论成功');
                exit;
            }
        }

        // 将数据插入返回
        $this->view->assign([
            'order' => $order
        ]);

        return $this -> view -> fetch();
    }

}
