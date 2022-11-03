<?php
namespace app\shop\controller\order;

use think\Controller;

class Cart extends Controller {
  public function __construct() {
    // 继承父类
    parent::__construct();

    $this -> ProductModel = model('Product.Product');
    $this -> OrderProductModel = model('Order.Product');
    $this -> OrderModel = model('Order.Order');
    $this -> CartModel = model('Order.Cart');
    $this -> BusinessModel = model('Business.Business');

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

  // 获取购物车列表
  public function index() {
    // 判断ajax
    if($this -> request->isAjax()) {
      // 接收来自订单页面发送的请求
      $cartids = $this -> request->param('cartids', 0, 'trim');

      $where = [
        'busid' => $this -> busid
      ];

      // 将接收到的字符串数据转为数组
      if($cartids) {
        $cartids = explode(',', $cartids);
        $where['cart.id'] = ['in', $cartids];
      }

      // 根据id查询当前用户购物车
      $list = $this -> CartModel 
                    -> with(['product'])
                    -> where($where)
                    -> select();

      $this->success('返回购物车数据', null , $list);
      exit;
    }
  }

  // 添加商品到购物车
  public function add() {
    // 判断ajax
    if($this -> request->isAjax()) {
      // 接收传递的参数并查询当前商品是否存在
      $proid = $this -> request -> param('proid', 0, 'trim');

      $product = $this -> ProductModel -> find($proid);

      // 判断是否存在商品
      if(!$product) {
        $this -> error('商品不存在');
        exit;
      }

      //组装数据
      $CartData = [];
      $res = false;

      // 查询当前用户的购物车是否有当前商品的记录
      $where = [
        'proid' => $proid, // 商品id
        'busid' => $this -> busid // 用户id
      ];

      $cart = $this -> CartModel -> where($where) -> find();

      // 判断是否查询到
      if($cart) {
        // 加完后的数量
        $pronum = $cart['pronum']+1;

        // 加完后再重新计算总价
        $total = bcmul($cart['price'], $pronum);

        $CartData = [
            'id' => $cart['id'],
            'busid' => $cart['busid'],
            'proid' => $cart['proid'],
            'price' => $cart['price'],
            'pronum' => $pronum,
            'total' => $total,
        ];

        $res = $this -> CartModel -> validate('common/Order/Cart') -> isUpdate(true) -> save($CartData);
      } else {
        // 如果没记录就只时插入到数据库
        $CartData = [
          'busid' => $this->busid,
          'proid' => $proid,
          'pronum' => 1,
          'price' => $product['price'],
          'total' => $product['price'],
        ];

        $res = $this -> CartModel -> validate('common/Order/Cart') -> save($CartData);
      }

      // 判断是否成功
      if($res === FALSE) {
        $this -> error($this -> CartModel -> getError());
        exit;
      } else {
        $this -> success('加入购物车成功, 是否前往购物车', '/order/order/cart');
        exit;
      }
    }
  }

  // 购物车编辑
  public function edit() {
    if($this->request->isAjax()) {
      $id = $this->request->param('id', 0, 'trim');
      $pronum = $this->request->param('pronum', 0, 'trim');

      if($pronum <= 0) {
          $this->error('购物车数量不能小于0');
          exit;
      }

      // 判断购物车是否存在
      $where = [
          'id' => $id,
          'busid' => $this->busid,
      ];

      $cart = $this -> CartModel -> where($where) -> find();

      // 判断是否成功
      if(!$cart) {
        $this -> error('购物车商品不存在');
        exit;
      }

      // 如果购物车商品存在就组装数据
      $data = [
        'id' => $cart['id'],
        'busid' => $cart['busid'],
        'proid' => $cart['proid'],
        'pronum' => $pronum,
        'price' => $cart['price'],
      ];

      // 合计总价
      $data['total'] = bcmul($cart['price'], $pronum);

      // 更新
      $res = $this -> CartModel -> validate('common/Order/Cart') ->isUpdate(true) -> save($data);

      // 判断是否成功
      if($res === FALSE) {
        $this -> error($this -> CartModel -> getError());
        exit;
      } else {
        $this -> success('更新购物车成功');
        exit;
      }
    }
  }

  // 购物车删除商品
  public function del() {
    if($this->request->isAjax()) {
      // 获取商品id
      $cartid = $this -> request->param('cartid', 0, 'trim');

      // 判断商品是否存在购物车表中
      $where = [
        'id' => $cartid,
        'busid' => $this -> busid
      ];

      // 查询
      $cart = $this -> CartModel -> where($where) -> find();

      // 判断是否成功
      if(!$cart) {
        $this -> error('当前商品不存在');
        exit;
      }

      // 删除商品
      $res = $this -> CartModel -> destroy($cartid);

      // 判断是否成功
      if($res === FALSE) {
        $this -> error('商品删除失败');
        exit;
      } else {
        $this -> success('商品删除成功');
        exit;
      }
    }
  }

  // 获取购物车小数字
  public function badge() {
    // 判断ajax
    if($this -> request -> isAjax()) {
      // 根据用户id查询当前用户得到购物车物品数量
        $total = $this -> CartModel->where(['busid' => $this -> busid]) -> sum('pronum');

        $this->success('返回购物车数量', null, $total);
        exit;
    }
  }

}