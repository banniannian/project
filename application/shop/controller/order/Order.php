<?php
namespace app\shop\controller\order;

use think\Controller;

/**
 * 订单控制器
 */
class Order extends Controller {
  public function __construct() {
    // 继承父类
    parent::__construct();

    $this -> ProductModel = model('Product.Product');
    $this -> OrderProductModel = model('Order.Product');
    $this -> OrderModel = model('Order.Order');
    $this -> CartModel = model('Order.Cart');
    $this -> BusinessModel = model('Business.Business');
    $this -> AddressModel = model('Business.Address');

    //获取系统配置里面的选项
    $this->url = config('site.cdnurls') ? config('site.cdnurls') : '';

    // 判断用户是否存在
    if($this->request->isAjax()) {
      //接收用户id
      $this->busid = $this->request->param('busid', 0, 'trim');

      $business = $this->BusinessModel->find($this->busid);

      if(!$business) {
        $this->error('用户不存在');
        exit;
      }
    }
  }

  // 下订单
  public function add() {
    if($this -> request -> isAjax()) {
      // 1、获取提交订单提交来的数据
      $addrid = $this -> request->param('addrid', 0, 'trim');
      $cartids = $this -> request->param('cartids', 0, 'trim');
      $remark = $this -> request->param('remark', 0, 'trim');

      // 2、查询出用户的信息
      $business = $this -> BusinessModel -> find($this -> busid);

      // 2-2、判断用户是否有过邮箱认证
      if($business['status'] == '0') {
        $this -> error('您的账户暂未通过邮箱认证, 请认证后下单');
        exit;
      }

      // 3、判断收货地址是否存在
      $where = [
        'busid' => $this -> busid,
        'id' => $addrid
      ];

      // 3-2、查询默认收货地址
      $address = $this->AddressModel->where($where)->find();

      // 3-3、判断是否有地址
      if(!$address) {
        $this -> error('收货地址不存在');
        exit;
      }

      // 4、商品的id
      $where = [
        'cart.id' => ['in', $cartids]
      ];

      // 4-2、查询出购物车中的商品
      $cartlist = $this -> CartModel -> with(['product']) -> where($where) -> select();

      // 4-3、统计购物车商品的价格总计
      $total = $this -> CartModel -> with(['product']) -> where($where) -> sum('total');

      // 4-4、判断购物车记录
      if(!$cartlist) {
        $this -> error('购物车记录为空');
        exit;
      }

      // 4-5、判断库存够不够
      foreach($cartlist as $cart) {
        // 数据库中的库存数 < 用户购买数量
        if($cart['product']['stock'] < $cart['pronum']) {
          $this -> error($cart['product']['name'] . "库存不足, 无法下单");
          exit;
        }
      }

      // 5、余额 - 总价
      $UpdateModel = bcsub($business['money'], $total);

      // 5-2、判断钱是否足够
      if($UpdateModel < 0) {
        $this -> error('余额不足请充值');
        exit;
      }

      // 订单表 插入
      // 订单商品表 插入
      // 商品表 更新
      // 用户表 更新
      // 用户的消费记录表 插入
      // 购物车 删除

      // 引入需要的模型
      $OrderModel = model('Order.Order');
      $OrderProductModel = model('Order.Product');
      $ProductModel = model('Product.Product');
      $RecordModel = model('Business.Record');
      $BusinessModel = model('Business.Business');
      $CartModel = model('Order.Cart');

      // 开启各个模型事务回滚
      $OrderModel -> startTrans();
      $OrderProductModel -> startTrans();
      $ProductModel -> startTrans();
      $RecordModel -> startTrans();
      $BusinessModel -> startTrans();
      $CartModel -> startTrans();

      // 生成订单编号
      $code = build_code('PR');

      // 组装订单数据准备插入数据到订单
      $OrderData = [
        'code' => $code, // 订单号
        'busid' => $business['id'], // 用户的id
        'businessaddrid' => $addrid, // 
        'amount' => $total, // 订单的总价格
        'remark' => $remark, // 备注的内容
        'status' => 1, // 默认地址
        'adminid' => $business['adminid'] // 审核管理员
      ];

      // 将数据插入订单表中(返回的是影响的行数)
      $OrderStatus = $OrderModel -> validate('common/Order/Order') -> save($OrderData);

      // 判断是否成功
      if($OrderStatus === FALSE) {
        $this -> error($OrderModel -> getError());
        exit;
      }

      // 整理订单商品的数据
      $OrderProductData = [];

      // 整理商品表更新库存数据
      $ProductData = [];
      foreach($cartlist as $item) {
        // 订单商品
        $OrderProductData[] = [
          'orderid' => $OrderModel -> id, // 获取上一步插入的自增id
          'proid' => $item['proid'], // 从购物车拿取的商品id
          'pronum' => $item['pronum'], // 数量
          'price' => $item['price'], // 商品单价
          'total' => $item['total'], // 商品总计
        ];

        // 更新，将商品库存 - 购买数量
        $UpdateStock = bcsub($item['product']['stock'], $item['pronum']);

        $ProductData[] = [
          'id' => $item['proid'],
          'stock' => $UpdateStock
        ];
      }

      // 将多条数据插入
      $OrderProductStatus = $OrderProductModel -> validate('common/Order/Product') -> saveAll($OrderProductData);

      // 判断是否成功
      if($OrderProductStatus === FALSE) {
        // 如果失败就进行数据回滚
        $OrderModel -> rollback();
        $this -> error($OrderProductModel -> getError());
        exit;
      }
      // echo 34144342;
      // exit;

      // 对商品库存进行更新
      $ProductStatus = $ProductModel -> isUpdate(true) -> saveAll($ProductData);

      // 判断是否成功
      if($ProductStatus === FALSE) {
        // 上面第一个成功了，第二个不成功就回滚两个
        $OrderProductModel -> rollback();
        $OrderModel -> rollback();
        $this -> error($ProductModel -> getError());
        exit;
      }

      // 更新用户的余额
      $BusinessData = [
        'id' => $business['id'],
        'money' => $UpdateModel
      ];

      // 对余额更新
      $BusinessStatus = $BusinessModel -> isUpdate(true) -> save($BusinessData);

      // 判断是否成功
      if($BusinessStatus === FALSE) {
        $ProductModel -> rollback();
        $OrderProductModel -> rollback();
        $OrderModel -> rollback();
        $this -> error($BusinessModel -> getError());
        exit;
      }

      // 用户消费记录数据插入
      $RecordData = [
        'total' => "-$total",
        'content' => "购买商品, 订单号: 【{$code}】",
        'busid' => $business['id']
      ];

      // 将封装好的消费记录插入
      $RecordStatus = $RecordModel -> validate('common/Business/Record') -> save($RecordData);

      // 判断是否成功
      if($RecordStatus === FALSE) {
        $BusinessModel -> rollback();
        $ProductModel -> rollback();
        $OrderProductModel -> rollback();
        $OrderModel -> rollback();
        $this -> error($RecordModel -> getError());
        exit;
      }

      // 封装购物车id
      $where = ['id' => ['in', $cartids]];

      // 删除购物车商品
      $CartStatus = $CartModel -> where($where) -> delete();

      // 判断是否成功
      if($CartStatus === FALSE) {
        $RecordModel -> rollback();
        $BusinessModel -> rollback();
        $ProductModel -> rollback();
        $OrderProductModel -> rollback();
        $OrderModel -> rollback();
        $this -> error($CartModel -> getError());
        exit;
      }

      // 判断上面只要有一条执行失败就全部一个个回滚
      if($OrderStatus === FALSE || $OrderProductStatus === FALSE || $ProductStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE || $CartStatus === FALSE) {
        $CartModel -> rollback();
        $RecordModel -> rollback();
        $BusinessModel -> rollback();
        $ProductModel -> rollback();
        $OrderProductModel -> rollback();
        $OrderModel -> rollback();
        $this -> error('下单失败');
        exit;
      } else {
        // 成功就发送事务并跳转到订单列表
        $OrderModel -> commit();
        $OrderProductModel -> commit();
        $ProductModel -> commit();
        $BusinessModel -> commit();
        $RecordModel -> commit();
        $CartModel -> commit();
        $this -> success('下单成功, 等待商家发货', '/order/order/index');
        exit;
      }
    }
  }

  // 订单列表
  public function index() {
    // 判断是否aja
    if($this -> request-> isAjax()) {
      // 接收商品id和订单状态
      $busid = $this -> request -> param('busid', 0, 'trim');
      $status = $this -> request -> param('status', 0, 'trim');

      $where = [
        'busid' => $busid
      ];

      // 判断是否交易状态(有就追加到where变量中)
      if($status) {
        $where['status'] = $status; // 不等于0的状态
      }

      // 根据用户id查询订单列表
      $list = $this -> OrderModel -> where($where) -> select();

      // 判断是否成功
      if($list) {
        foreach($list as &$item) {
          $item['prolist'] = $this -> OrderProductModel
                                   -> with(['proinfo'])
                                   -> where(['orderid' => $item['id']])
                                   -> find();
        }
      }

      $this -> success('订单列表', null, $list);
      exit;
    }
  }

  // 订单详情
  public function info() {
    // 判断是否是post提交
    if($this ->request-> isPost()) {
      $busid = $this -> request->param('busid', 0, 'trim');
      $orderid = $this -> request->param('orderid', 0, 'trim');

      $orderinfo = $this -> OrderModel -> find($orderid);

      // 判断时是否成功
      if(!$orderinfo) {
        $this -> error('订单不存在');
        exit;
      }

      // 订单商品
      $product = $this -> OrderProductModel
                       -> with(['proinfo'])
                       -> where(['orderid' => $orderid])
                       -> select();

      // 判断商品是否存在
      if(!$product) {
        $this -> error('订单商品不存在');
        exit;
      }

      // 获取订单收货地址
      $address = $this -> AddressModel -> with(['provinces','citys','districts']) -> find($orderinfo['businessaddrid']);

      // 判断是否成功
      if(!$address) {
        $this -> error('未找到订单的收货地址');
        exit;
      }

      $data = [
        'order' => $orderinfo,
        'product' => $product,
        'address' => $address
      ];

      $this -> success('返回订单数据成功', null, $data);
      exit;
    }
  }

  public function express() {
    if($this -> request-> isAjax()) {
      // 接收用户id和订单id
      $orderid = $this -> request->param('orderid', 0, 'trim');

      // 查询数据库中的订单是否存在
      $order = $this -> OrderModel
                     -> with(['express'])
                     -> find($orderid);

      // 判断是否查询到啊订单
      if(!$order) {
        $this -> error('订单不存在');
        exit;
      }

      // 判断物流号是否存在
      if(empty($order['expresscode'])) {
        $this -> error('暂无物流单号');
        exit;
      }

      // 是否有物流公司
      if(empty($order['express']['name'])) {
        $this -> error('物流公司未知');
        exit;
      }

      // 判断缓存中是否有查询过的记录, 有就不用重复请求
      $cache = cache($order['expresscode']);
      if($cache) {
        // 有缓存就返回缓存数据
        if($cache) {
          $this -> success('返回物流信息', null, $cache);
          exit;
        } else {
          $this -> error('暂无物流信息');
          exit;
        }
      } else {
        $success = query_express($order['expresscode']);

        // 判断物流接口是否有返回物流信息
        if($success['result']) {
          // 通过cache存放缓存信息
          cache($order['expresscode'], $success['data']);
          $this -> success('返回物流信息', null, $success['data']);
          exit;
        } else {
          // 通过cache存放缓存信息
          cache($order['expresscode'], []);
          $this -> error($success['msg']);
          exit;
        }
      }

    }
  }

}
