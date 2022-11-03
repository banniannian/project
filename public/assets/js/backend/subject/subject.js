define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        // 控制器
        index: function () {
            // 初始化表格参数配置(页面中的按钮也是包含在数据列表中的，按钮要通过检查权限是否显示)
            Table.api.init({
                extend: {
                    // 请求数据的控制器的地址
                    index_url: 'subject/subject/index',
                    add_url: 'subject/subject/add',
                    edit_url: 'subject/subject/edit',
                    del_url: 'subject/subject/del',
                    multi_url: 'subject/subject/multi',
                    recyclebin_url: 'subject/subject/recyclebin',
                    import_url: 'subject/subject/import',
                    lists_url: 'subject/lists/index',
                    table: 'subject',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        // title是语言包中的对应同名文件
                        {checkbox: true},
                        // 表头字段
                        {field: 'id', title: __('Id'),sortable: true,},
                        // 表头名称
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        // 表头价格
                        {field: 'price', title: __('Price'), operate:'BETWEEN',sortable: true,},
                        // 表头分类名称
                        {field: 'category.name', title: __('category.name')},
                        // 表头课程点赞
                        {field: 'likes_text', title: __('Likes'),operate: false},
                        // 表头课程描述
                        // {field: 'content', title: __('content')},

                        // 表头创建时间
                        // operate是时间段
                        {field: 'createtime',
                        title: __('Createtime'),
                        operate:'RANGE',
                        addclass:'datetimerange',
                        autocomplete:false,
                        formatter: Table.api.formatter.datetime,
                        sortable: true,
                        },
                        // 表头操作
                        {field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [{
                            name: 'lists',
                            // 通过遍历将课程名称通过title附加上去
                            title: function(data) {
                                return `${data.title}的章节列表`
                            },
                            icon: 'fa fa-leanpub',
                            classname: 'btn btn-xs btn-primary btn-dialog',
                            url: $.fn.bootstrapTable.defaults.extend.lists_url,
                            extend: 'data-toggle=\'tooltip\' data-area= \'["85%", "85%"]\'', // 控制弹窗大小
                        }]}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        recyclebin: function () {
            // 初始化表格参数配置(页面中的按钮也是包含在数据列表中的，按钮要通过检查权限是否显示)
            Table.api.init({
                extend: {
                    // 请求数据的控制器的地址
                    recyclebin_url: 'subject/subject/recyclebin',
                    del_url: 'subject/subject/destroy', // 永久删除
                    restore_url: 'subject/subject/restore', // 还原
                    table: 'subject',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.recyclebin_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        // title是语言包中的对应同名文件
                        {checkbox: true},
                        // 表头字段
                        {field: 'id', title: __('Id'),sortable: true,},
                        // 表头名称
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        // 表头价格
                        {field: 'price', title: __('Price'), operate:'BETWEEN',sortable: true,},
                        // 表头分类名称
                        {field: 'category.name', title: __('category.name')},
                        // 表头课程点赞
                        {field: 'likes_text', title: __('Likes'),operate: false},
                        // 表头课程描述
                        // {field: 'content', title: __('content')},

                        // 表头创建时间
                        // operate是时间段
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,sortable: true,},
                        // 删除时间
                        {field: 'deletetime', title: __('Deletetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime,},

                        {field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        // 新增自定义按钮
                        buttons: [{
                            name: 'restore',
                            title: '还原数据',
                            icon: 'fa fa-reply',
                            classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                            url: $.fn.bootstrapTable.defaults.extend.restore_url,
                            confirm: '是否还原课程?',
                            extend:"data-toggle='tooltip'", // 用于显示信息
                            success: function (res) {
                                // 成功就会通过ajax属性table数据列表
                                table.bootstrapTable('refresh');
                            }
                        }]}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 给表格按钮绑定事件
            $(document).on('click', '#restore_btns', function () {
                // 弹出确认对话框
                layer.confirm(__('是否还原课程?'), {
                    icon: 3,
                    title: __('Warning'),
                    shadeClose: true,
                }, function(res) {
                    // 将获取的id通过ajax发送
                    // 通过selectedids获取表格中被选中的条目id(数组格式)
                    var ids = Table.api.selectedids(table);

                    // 通过Backend.api中的ajax进行请求
                    Backend.api.ajax(
                        {url: $.fn.bootstrapTable.defaults.extend.restore_url + `?ids=${ids}`},
                        function() {
                            // 点击确认发送请求后关闭窗口
                            Layer.close(res);

                            // 刷新表格
                            table.bootstrapTable('refresh');
                        }
                    )


                });
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
