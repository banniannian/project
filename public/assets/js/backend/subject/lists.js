define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        // 控制器
        index: function () {
            // 获取ids参数(有就存储，没有就给0)
            var ids = Fast.api.query('ids') ? Fast.api.query('ids') : 0;

            // 初始化表格参数配置(页面中的按钮也是包含在数据列表中的，按钮要通过检查权限是否显示)
            Table.api.init({
                extend: {
                    // 请求数据的控制器的地址
                    index_url: `subject/lists/index?ids=${ids}`,
                    add_url: `subject/lists/add?subid=${ids}`,
                    edit_url: 'subject/lists/edit',
                    del_url: 'subject/lists/del',
                    multi_url: 'subject/lists/multi',
                    import_url: 'subject/lists/import',
                    table: 'subject_lists',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'asc', // 升序
                columns: [
                    [
                        // title是语言包中的对应同名文件
                        {checkbox: true},
                        // 表头字段
                        {field: 'id', title: __('Id'),sortable: true,},
                        // 表头名称
                        {field: 'title', title: __('Title'),sortable: true,},
                        // 表头地址
                        {field: 'url', title: __('Url'), operate: false},
                        // 表头创建时间
                        // operate是时间段
                        {field: 'createtime', title: __('Createtime'),
                        operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, sortable: true,},
                        // 表头操作
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
