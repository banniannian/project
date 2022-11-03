<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

//引入Tp的数据库类
use think\Db;

/**
 * 课程管理
 *
 * @icon fa fa-circle-o
 */
class Subject extends Backend
{

    /**
     * Subject模型对象
     */
    protected $model = null;

    // 是否进行关联查询
    protected $relationSearch = true;

    public function _initialize() {
        parent::_initialize();
        $this->model = model('Subject.Subject');

        // 课程分类模型
        $this->CategoryModel = model('Subject.Category');
    }

    // 查看
    public function index() {
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
                    ->count();

            //查询数据
            $list = $this->model
                    ->with(['category'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    // 添加
    public function add() {
        // 判断是否是post
        if($this->request->isPost()) {
            // row/a 是获取请求中的row数组元素, 相当于是row[array], row开头的元素使用数组的类型存储
            $params = $this -> request -> param('row/a');

            // 插入语句
            $result = $this -> model ->  validate('common/Subject/Subject') -> save($params);

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
        
        // 查询所有课程分类(weight是权重, asc升序)
        // column只查询id和name两个字段
        $catelist = $this -> CategoryModel -> order('weight asc') -> column('id, name');

        // 将生成的select下拉框赋值到模块
        // build_select(下拉框名字, 要给的数据是什么, 是否有默认选中的, class名)
        $this -> view -> assign('catelist', build_select('row[cateid]', $catelist, [], ['class' => 'form-control selectpicker']));

        return $this->view->fetch();
    }

    // 编辑
    public function edit($ids = 0) {
        // 根据id判断课程是否哦存在
        $subject = $this -> model -> find($ids);

        if(!$subject) {
            $this -> error('课程不存在');
            exit;
        }

        // 判断是否是post提交
        if($this -> request-> isPost()) {
            // 接收数据
            $params = $this -> request -> param('row/a');

            // 将数据补录到数据中
            $params['id'] = $ids;

            // 更新数据
            // $params是新数据, $subject中是旧的数据
            $result = $this -> model -> validate('common/Subject/Subject') -> isUpdate(true) -> save($params);

            // 判断是否成功
            if($result === FALSE) {
                $this -> error($this -> model -> getError());
                exit;
            } else {
                // 判断是否有新图片上传
                // 判断$subject路径和$params中的路径是否一致
                if($subject['thumbs'] != $params['thumbs']) {
                    // 不是用一张图片就开始判断是否是文件, 是就移除就图片
                    @is_file(".".$subject['thumbs']) && @unlink(".".$subject['thumbs']);
                }

                $this -> success();
                exit;
            }

        }

        // 查询所有课程分类
        $catelist = $this -> CategoryModel -> order('weight asc') -> column('id, name');

        // build_select(下拉框名字, 要给的数据是什么, 是否有默认选中的, class名)
        $this -> view -> assign('catelist', build_select('row[cateid]', $catelist, $subject['cateid'], ['class' => 'form-control selectpicker']));


        $this -> view -> assign('subject', $subject);

        return $this -> view -> fetch();
    }

    // 删除
    public function del($ids = 0) {
        // 先去查询数据库中对应的数据
        $rows = $this -> model -> where(['id' => ['in', $ids]]) -> select();

        // 如果一条数据都没有找不到
        if(!$rows) {
            $this -> error('暂无数据');
            exit;
        }

        // 软删除是更新数据库的deletetime的字段放入时间戳达到目的
        // User::destroy(1) 软删除
        // User::destroy(1, true) 真删除
        $result = $this -> model -> destroy($ids);

        // 判断是否软删除成功
        if($result === FALSE) {
            $this -> error('删除课程失败');
            exit;
        } else {
            $this -> success();
            exit;
        }
    }

    // 回收站
    public function recyclebin() {
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
                    ->onlyTrashed() // 只查询软删除数据
                    ->where($where)
                    ->count();

            //查询数据
            $list = $this->model
                    ->onlyTrashed() // 只查询软删除数据
                    ->with(['category'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    // 永久删除
    public function destroy($ids = 0) {
        // 查询被软删除的数据
        // 要读软删除的字段进行删除要通过onlyTrashed才可以删除
        $rows = $this -> model -> onlyTrashed() -> select($ids);

        // 判断是否成功
        if(!$rows) {
            $this -> error('暂无要删除的数据');
            exit;
        }

        // 单独查询是否有图片
        $thumbs = $this -> model -> onlyTrashed() -> where(['id' => ['in', $ids]]) -> column('thumbs');

        // 去除没有图片的
        $thumbs = array_filter($thumbs);

        // 先删除数据再删除图片，防止先删除图片后删除数据不成功导致的问题
        $result = $this -> model -> destroy($ids, true);

        // 判断是否删除成功
        if($result === FALSE) {
            $this -> error('删除失败');
            exit;
        } else {
            // 删除图片
            if(!empty($thumbs)) {
                // 数组将图片删除
                foreach($thumbs as $item) {
                    // 判断是否是文件
                    @is_file('.'.$item) && @unlink('.'.$item);
                }
            }

            // 删除完成后提示成功
            $this -> success();
            exit;
        }


    }

    // 还原(更新, 将数据表中的数据deletetime字段更新为null)
    public function restore($ids = 0) {
        // 执行更新语句
        // 查询pro_subject表id为获取的id将deletetime字段更新为null
        // 因为updata无法更新指定字段为null, 所以只能使用Db纯原生的方式更新字段为null
        $result = Db::name('subject') -> where(['id' => ['in', $ids]]) -> update(['deletetime' => null]);

        // 判断是否更新成功
        if($result) {
            $this -> success();
            exit;
        } else {
            $this -> error(__('还原课程失败'));
            exit;
        }

    }
}
