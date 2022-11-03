<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

//引入Tp的数据库类
use think\Db;

/**
 * 课程章节管理
 * @icon fa fa-circle-o
 */
class Lists extends Backend
{

    /**
     * Lists模型对象
     */
    protected $model = null;

    // 是否进行关联查询
    protected $relationSearch = true;

    public function _initialize() {
        parent::_initialize();
        $this->model = model('Subject.Lists');

    }

    // 查看
    public function index($ids = 0) {
        // 设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            //查询总数
            $total = $this->model
                    ->where($where)
                    ->where(['subid' => $ids]) // 课程id必须等于ids
                    ->count();

            //查询数据
            $list = $this->model
                    ->where($where)
                    ->where(['subid' => $ids]) // 课程id必须等于ids
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    // 添加
    public function add($subid = 0) {
        // 判断是否是post
        if($this->request->isPost()) {
            // row/a 是获取请求中的row数组元素, 相当于是row[array], row开头的元素使用数组的类型存储
            $params = $this -> request -> param('row/a');

            // 补充课程id
            $params['subid'] = $subid;

            // 插入语句, 更改验证器为Lists
            $result = $this -> model ->  validate('common/Subject/Lists') -> save($params);

            // 判断是否插入成功
            if($result === FALSE) {
                // 插入失败
                $this -> error($this -> model -> getError());
                exit;
            } else {
                $this -> success();
                exit;
            }
        }

        return $this->view->fetch();
    }

    // 编辑
    public function edit($ids = 0) {
        // 根据id判断课程是否哦存在
        $rows = $this -> model -> find($ids);

        if(!$rows) {
            $this -> error('课程章节不存在');
            exit;
        }

        // 判断是否是post提交
        if($this -> request-> isPost()) {
            // 接收数据
            $params = $this -> request -> param('row/a');

            // 将数据补录到数据中
            $params['id'] = $ids;
            $params['subid'] = $rows['subid']; // 课程id

            // 更新数据
            // $params是新数据, $subject中是旧的数据
            $result = $this -> model -> validate('common/Subject/Lists') -> isUpdate(true) -> save($params);

            // 判断是否成功
            if($result === FALSE) {
                $this -> error($this -> model -> getError());
                exit;
            } else {
                // 判断是否有新图片上传
                // 判断$subject路径和$params中的路径是否一致
                if($rows['url'] != $params['url']) {
                    // 不是用一张图片就开始判断是否是文件, 是就移除就图片
                    @is_file(".".$rows['url']) && @unlink(".".$rows['url']);
                }

                $this -> success();
                exit;
            }

        }

        $this -> view -> assign('rows', $rows);

        return $this -> view -> fetch();
    }

    // 删除
    public function del($ids = 0) {
        // 直接查询多条
        $rows = $this -> model -> select($ids);

        // 如果一条数据都没有找不到
        if(!$rows) {
            $this -> error('暂无数据');
            exit;
        }

        // 查询指定字段
        $url = $this -> model -> where(['id' => ['in', $ids]]) -> column('url');

        // 去除空元素
        $url = array_filter($url);

        // 软删除是更新数据库的deletetime的字段放入时间戳达到目的
        // User::destroy(1) 软删除
        // User::destroy(1, true) 真删除
        $result = $this -> model -> destroy($ids);

        // 判断是否软删除成功
        if($result === FALSE) {
            $this -> error('删除课程章节失败');
            exit;
        } else {
            // 判断是否拿到数据
            if(!empty($url)) {
                foreach($url as $item) {
                    // 判断是否是文件，并删除视频
                    @is_file(".".$item) && @unlink(".".$item);
                }
            }

            $this -> success();
            exit;
        }
    }

}
