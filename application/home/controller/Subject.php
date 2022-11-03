<?php

// 事务的步骤
// 1、开启事务 startTrans
// 2、回滚事务 rollback
// 3、提交事务 commit

namespace app\home\controller;

use app\common\controller\Home;
use think\db;

/**
 * 课程控制器
*/

class Subject extends Home {
    // 不需要登录搜索框
    protected $noNeedLogin = ['search', 'subject', 'likes'];

    // 构造函数
    public function __construct() {
        // 继承自Home
        parent::__construct();

        // 创建模型
        $this -> SubjectModel = model('Subject.Subject');
        $this -> OrderModel = model('Subject.Order');
        $this -> BusinessModel = model('Business.Business');
        $this -> ListsModel = model('Subject.Lists');
        $this -> RecordModel = model('Business.Record'); // 用户消费模型
    }

    // 搜索方法
    public function search() {
        // 接收keyword搜索内容
        $keywords = $this -> request -> param('keywords', '', 'trim');

        // 封装条件以便后续对数据库查询添加条件
        $where = [];

        // 判断搜索框关键字是否为空(为空就查全部, 否则就模糊查询指定)
        if(!empty($keywords)) {
            $where['title|content'] = ['like', "%$keywords%"];
        }

        // 根据where变量中的内容查询
        $sublist = $this -> SubjectModel -> where($where) -> select();

        $this -> view -> assign([
            'sublist' => $sublist,
            'keywords' => $keywords,
        ]);

        return $this->view->fetch();
    }

    // 课程详情
    public function subject($subid = 0) {
        // 接收课程列表中点击的课程详情的id, 用于数据库查询的条件
        $subject = $this -> SubjectModel -> find($subid);
        // $subjects = $this -> BusinessModel -> find($subid);
        // var_dump($subject['id']);
        // var_dump($subid);
        // exit;


        // exit;




        // $subid 评论

        // var_dump(
        //  Db::table('pro_subject_order') // 用户表
        // -> alias('ord') // 用户表别名
        // -> join('pro_business usd', "'$subid' = usd.id", 'LEFT') // 订单表
        // // -> where([ $subid => 'ord.busid'])
        // // ->join('think_card c','uses.card_id = c.id')
        // -> select());

        // exit;


        // var_dump(
        //     Db::table('pro_subject')
        //     ->alias('sub')
        //     ->join('subject_order ors ', 'ors.user_id = sub.id')
        //     ->select());
        // exit;

        // 默认没有点赞
        $likes = false;

        // 判断是否存在课程
        if(!$subject) {
            $this -> error('课程不存在');
            exit;
        }

        // 获取cookie
        $LoginAuth = cookie('LoginAuth') ? cookie('LoginAuth') : [];
        $busid = isset($LoginAuth['id']) ? $LoginAuth['id'] : 0;
        $busmobi = isset($LoginAuth['mobile']) ? $LoginAuth['mobile'] : '';
        $busava = isset($LoginAuth['avatar']) ? $LoginAuth['avatar'] : '';

        // 根据cookie中的id和手机号查询当前用户
        $where = [
            'id' => $busid,
            'mobile' => $busmobi,
        ];

        // 单条查询
        $business = $this -> BusinessModel -> where($where) -> find();

        // 不为空说明有登录, 判断用户id是否在当前课程的likes中
        if($business) {
            $str = trim($subject['likes']); // 去除字符串的空元素
            $arr = explode(',', $str); // 字符串转为数组
            $arr = array_filter($arr); // 去除数组的空元素

            // 如果当前用户在likes字段中说明之前点赞过
            $likes = in_array($busid, $arr) ? true : false;
        }

        // var_dump($business);
        // exit;

        // 查询课程的章节
        $lists = $this -> ListsModel -> where(['subid' => $subid]) -> order('createtime ASC') -> select();




        $orderlist = $this -> OrderModel
            -> where(['subid' => $subject['id']]) // 判断订单客户id等于当前课程id
            // -> join([$this -> OrderModel['busid'] => 'pro_business.id'], 'LEFT')
            -> select();
        // var_dump($orderlist);


        // for($i = 0; $i <= $orderlist; $i++) {
        //    $Use_img =  $orderlist[$i];
        //    $subjects = $this -> BusinessModel -> find($Use_img ->busid);
        //    echo $subjects;
        //    echo $Use_img;
        // }





        // 将$lists中的数据返回给前端用于渲染数据
        $this -> view -> assign([
            'id' => $busid,
            // 'avatar' => $busava,  // 头像
            'subject' => $subject,
            'lists' => $lists,
            'likes' => $likes, // 将likes数据返回出去
            // 'Use_img' => $Use_img,
            'remark' => $orderlist,
            // 'avatar_text' =>
        ]);

        return $this->view->fetch();
    }

    // 点赞
    public function likes() {
        // 判断是否是ajax请求
        if($this -> request -> isAjax()) {
            // 是就获取cookie
            $LoginAuth = cookie('LoginAuth') ? cookie('LoginAuth') : [];
            $busid = isset($LoginAuth['id']) ? $LoginAuth['id'] : 0;
            $busmobi = isset($LoginAuth['mobile']) ? $LoginAuth['mobile'] : '';

            // 根据cookie中的id和手机号查询数据库是否有此人
            $where = [
                'id' => $busid,
                'mobile' => $busmobi,
            ];

            // 单条查询数据
            $business = $this -> BusinessModel -> where($where) -> find();

            // 如果找不到要删除cookie和提醒并要求登录
            if(!$business) {
                cookie('LoginAuth', null);
                $this -> error('请先登录');
            }

            // 判断时是否登录的同时也要判断课程是否存在
            // 获取课程id
            $subid = $this -> request -> param('subid', 0);

            // 根据id查询课程是否存在
            $subject = $this -> SubjectModel -> find($subid);

            // 课程不存在
            if(!$subject) {
                $this -> error('课程不存在');
                exit;
            }

            // 将上面查到的课程中的likes从字符串换为数组用于判断里面的id是否等于当前用户id
            $likes = explode(',', $subject['likes']); // 转为数组
            // 对数组去除空元素
            $likes = array_filter($likes);

            $msg = "点赞成功";

            // 判断当前用户id是否在likes数组中
            if(in_array($busid, $likes)) {
                // 用户id在里面就取消点赞
                foreach($likes as $key => $item) {
                    // 数组中的哪一项是和当前用户id同名
                    if($item == $busid) {
                        unset($likes[$key]);
                        // break;
                    }
                }

                $msg = "取消点赞";
            } else {
                // 用户id不在就增加点赞
                $likes[] = $busid;

                $msg = '点赞';
            }

            // 组装数据将其数组转为字符串
            $data = [
                'id' => $subid,
                'likes' => implode(',', $likes)
            ];

            // 更新点赞数据
            $result = $this -> SubjectModel -> isUpdate(true) -> save($data);

            if($result === false) {
                $this -> error("{$msg}失败");
                exit;
            } else {
                $this -> success("{$msg}成功");
                exit;
            }

        }
    }

    // 课程播放
    public function play() {
        // 判断是否是ajax方式
        if($this -> request -> isAjax()) {
            // 获取课程id
            $subid = $this -> request -> param('subid', 0);
            // 获取课程下的章节id
            $listid = $this -> request -> param('listid', 0);

            // 获取用户id
            $busid = $this -> LoginAuth['id'];

            // 根据课程id和用户id查询是否有购买订单，有就可以看视频，没有说明没买不能看

            // 课程id与用户id
            $where = [
                'subid' => $subid,
                'busid' => $busid
            ];

            $order = $this -> OrderModel -> where($where) -> find();

            // 查询不到
            if(!$order) {
                $this -> error('请先购买课程', null , 'buy');
                exit;
            }

            $where = [
                'subid' => $subid
            ];

            // 有章节id的查询带章节id的，否则默认按时间升序查询
            if($listid) {
                $where['id'] = $listid; // 将得到的章节id放到where变量中
            }

            // 没有章节id的就默认按事件升序查询视频
            $list = $this -> ListsModel -> where($where) -> order('createtime ASC') -> find();

            if($list) {
                // 查询到就返回成功信息和章节id
                $this -> success('获取章节id成功', null , $list);
                exit;
            } else {
                $this -> error('暂无章节');
                exit;
            }

            // 有购买就退出不管
            exit;
        }
    }

    // 课程购买
    public function buy() {
        // 判断是否是ajax
        if($this -> request -> isAjax()) {
            // 获取课程id和用户id
            $subid = $this -> request -> param('subid', 0);
            $busid = $this -> LoginAuth['id'];
            $mobiles = $this -> LoginAuth['mobile'];
            $nickname = $this -> LoginAuth['nickname'];
            $avatar = $this -> LoginAuth['avatar'];

            $mobile_new = substr_replace($mobiles, '****', 3, 4);
            
            // 根据id查询课程是否存在
            $subject = $this -> SubjectModel -> find($subid);

            // 为空说明没有这个课程
            if(!$subject) {
                $this -> error('课程不存在');
                exit;
            }

            // 判断当前用户是否已经买过课程
            $where = [
                'busid' => $busid,
                'subid' => $subid,
            ];

            // 查询一条数据
            $order = $this -> OrderModel -> where($where) -> find();

            if($order) {
                $this -> error('该课程已购买，无需重复购买');
                exit;
            }

            // 1、订单(subject_order) - 插入
            // 2、用户(business) - 更新
            // 3、用户消费记录(business_record) - 插入

            // 如果没有购买
            // 拿到课程价格来比较当前用户的余额来对比

            $price = isset($subject['price']) ? trim($subject['price']) : 0;

            // 个人余额
            $money = isset($this -> LoginAuth['money']) ? trim($this ->LoginAuth['money']) : 0;

            // 个人余额减去课程价格
            $UpdateMoney = bcsub($money, $price);

            // 用户余额不足就提示
            if($UpdateMoney < 0) {
                $this -> error('余额不足, 请充值');
                exit;
            }


    // [--------------------------------------------------------------]


            // 开启操作表的事务
            $this -> OrderModel -> startTrans();
            $this -> BusinessModel -> startTrans();
            $this -> RecordModel -> startTrans();

            // 生成订单号
            $code = build_code("SU");

            // 封装信息到数组中
            $OrderData = [
                'subid' => $subid,
                'busid' => $busid,
                'total' => $price,
                'code' => $code,
                'mobile' => $mobile_new,
                'nickname' => $nickname,
                'avatar' => $avatar,
            ];

            // 将封装好的订单表插入到数据库中
            $OrderStatus = $this -> OrderModel -> validate('common/Subject/Order') -> save($OrderData);

            // 订单插入失败
            if($OrderStatus == FALSE) {
                $this -> error($this -> OrderModel -> getError());
            }

            // 封装买完课程后要更新的用户的信息
            $BusData = [
                'id' => $busid,
                'money' => $UpdateMoney
            ];

            // 判断是否成交(如果是未成交就将其改为已成交)
            if(!$this -> LoginAuth['deal']) {
                $BusData['deal'] = 1;
            }

            // 更新数据
            $BusStatus = $this -> BusinessModel -> isUpdate(true) -> save($BusData);

            // 如果判断更新失败就进行数据库事务回滚(就是)
            if($BusStatus === FALSE) {
                // 更新失败订单回滚
                $this -> OrderModel -> rollback();
                $this -> error($this -> BusinessModel -> getError());
                exit;
            }

            // 封装消费记录信息
            $subtitle = $subject['title'];
            $RecordDate = [
                'total' => "-{$price}", // 消费所以是负
                'content' => "购买了[{$subtitle}]课程, 花费了￥{$price}元",
                'busid' => $busid
            ];

            // 插入消费记录
            $RecordStatus = $this -> RecordModel -> validate('common/Business/Record') -> save($RecordDate);

            // 如果消费记录插入失败
            if($RecordStatus === FALSE) {
                // 回滚用户表
                $this -> BusinessModel -> rollback();  // 回滚用户[2]
                $this -> OrderModel -> rollback(); // 回滚订单[1]
                $this -> error($this -> RecordModel -> getError()); // 返回错误信息
                exit;
            }

            // 如果3个其中有一个条件不成立就回滚
            if($OrderStatus === FALSE || $BusStatus === FALSE || $RecordStatus === FALSE) {
                $this->RecordModel->rollback(); // 回滚消费记录[3]
                $this->BusinessModel->rollback(); // 回滚用户[2]
                $this->OrderModel->rollback(); // 回滚订单[1]
                $this->error('购买失败'); // 返回错误信息
                exit;
            } else {
                // 如果3个条件成立了,就要提交事务执行到数据库中
                $this->OrderModel->commit(); // 获取订单[1]
                $this->BusinessModel->commit(); // 获取用户[2]
                $this->RecordModel->commit(); // 获取消费记录[3]
                $this->success('购买成功');
                exit;
            }
        }
    }

    // 立即购买成功
    public function complete($subid = 0) { // $subid默认为0
        // 查询课程
        $subject = $this -> SubjectModel -> find($subid);

        // 判断是否存在课程
        if(!$subject) {
            // 不存在就报错
            $this -> error('课程不存在', url('/home/subject/search'));
            exit;
        }

        // 有课程就将课程id返回出去
        $this -> view -> assign('subid', $subid);

        return $this -> view -> fetch();
    }


}
