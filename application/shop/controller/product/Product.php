<?php
namespace app\shop\controller\product;

use think\Controller;

class Product extends Controller {
  public function __construct() {
    // 继承父类
    parent::__construct();

    $this -> TypeModel = model('Product.Type');
    $this -> ProductModel = model('Product.Product');
    $this -> OrderProductModel = model('Order.Product');

    //获取系统配置里面的选项
    $this->url = config('site.cdnurls') ? config('site.cdnurls') : '';
  }

  public function home() {
    // 查询首页商品分类（只查询8条）
    $typelist = $this -> TypeModel -> order('weigh asc') -> limit(8) -> select();

    // 查询首页新品（只查询4条）
    $newlist = $this -> ProductModel -> order('createtime desc') -> limit(4) -> select();

    $field = [
      'SUM(pronum)' => 'ordnum',
    ];

    // 查询热销商品（链表查询）
    $hotlist = $this -> OrderProductModel
                    -> with(['proinfo'])
                    -> field($field)
                    -> group('proid')
                    -> order('ordnum desc')
                    -> limit(6)
                    -> select();

  
    // 站点名称
    $sitename = config('site.name');

    // 广告图
    $siteadpic = config('site.adpic');
    // 给广告图拼上域名信息
    $siteadpic = trim($siteadpic, '/');
    $siteadpic = $this -> url.'/' . $siteadpic;

    // 拿到配置的轮播图
    $sitepiclist = config('site.piclist');

    // 判断轮播图数据不能为空且还要是个数组
    if($sitepiclist && is_array($sitepiclist)) {
      foreach($sitepiclist as &$item) {
        $item = trim($item, '/');
        $item = $this -> url. '/' .$item;
      }
    }

    // 将上面两个得到的数据组装到一起并返回给首页
    $res = [
      'typelist' => $typelist,
      'newlist' => $newlist,
      'hotlist' => $hotlist,
      'sitename' => $sitename,
      'siteadpic' => $siteadpic,
      'sitepiclist' => $sitepiclist,
    ];

    $this -> success('返回首页数据', null, $res);
    exit;
  }

  // 获取所有分类数据
  public function typelist() {
    // 判断是否为ajax请求
    if($this -> request -> isAjax()) {
      $typelist = $this -> TypeModel -> field('id, name') -> select();
      // var_dump($typelist);
      // exit;

      $this -> success('返回所有商品分类', null, $typelist);
    }
  }

  // 获取商品列表数据
  public function prolist() {
    if($this -> request -> isAjax()) {
      // 获取分类id
      $typeid = $this->request->param('typeid', 0, 'trim');
      $keyword = $this->request->param('keyword', '', 'trim');
      $orderby = $this->request->param('orderby', 'createtime', 'trim');

      $where = [];

      // 判断是否有分类id(有就放到where后面一起查询)
      if($typeid) {
        $where['typeid'] = $typeid;
      }

      // 判断是否为空
      if(!empty($keyword)) {
        // 模糊查询
        $where['name'] = ['LIKE', "%$keyword%"];
      }

      // 商品列表按降序进行排序
      $prolist = $this->ProductModel->where($where)->order("$orderby desc")->select();

      $this->success('返回商品列表', null, $prolist);
      exit;
    }
  }

  // 获取商品信息
  public function proinfo() {
    if($this -> request -> isAjax()) {
      $proid = $this -> request ->param('proid', 0, 'trim');

      // 根据id判断商品是否存在数据库(因为没说要查询公司所以直接查商品)
      $product = $this -> ProductModel -> find($proid);

      // 判断是否成功
      if(!$product) {
        $this -> error('商品不存在');
        exit;
      }

      $tel = config('site.tel');

      $res = [
        'product' => $product,
        'tel' => $tel
      ];

      // 返回数据
      $this -> success('返回商品数据', null, $res);
      exit;
    }
  }

  // 获取当前商品评论列表
  public function comlist() {
    if($this -> request -> isAjax()) {
      $proid = $this -> request -> param('proid', 0, 'trim');
      // 按偏移值分页的方式获取
      $page = $this -> request -> param('page', 1, 'trim');

      // 一样根据id判断商品是否存在数据库
      $product = $this -> ProductModel -> find($proid);

      // 一样判断是否成功
      if(!$product) {
        $this -> error('商品不存在');
        exit;
      }
      
      // 设置页面默认每页显示多少条数据
      $limit = 8;

      // 偏移值
      $offset = ($page - 1) * $limit;

      // 评价列表
      $comlist = $this -> OrderProductModel
                       -> where(['proid' => $proid]) // 根据传递的id查询
                       -> order('id desc') // 排序
                       -> limit($offset, $limit) // 偏移查询0~7, 8
                       -> select(); // 查询全部


      // 返回数据
      $this -> success('返回商品数据', null, $comlist);
      exit;
    }
  }

}
