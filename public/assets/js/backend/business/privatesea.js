define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'business/privatesea/index' + location.search,
                    add_url: 'business/privatesea/add',
                    edit_url: 'business/privatesea/edit',
                    del_url: 'business/privatesea/del',
                    info_url: 'business/highsea/info', // 详情地址
                    multi_url: 'business/privatesea/multi',
                    import_url: 'business/privatesea/import',
                    table: 'business',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'mobile', title: __('Mobile'), operate: 'LIKE' },
                        { field: 'nickname', title: __('Nickname'), operate: 'LIKE' },
                        { field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image },
                        { field: 'gender_text', title: __('Gender') },
                        { field: 'sourceid', title: __('Sourceid'), searchList: { "1": __('云课堂'), "2": __('家居商城') }, formatter: Table.api.formatter.normal },
                        { field: 'deal_text', title: __('Deal') },
                        // { field: 'province', title: __('Province'), operate: 'LIKE' },
                        // { field: 'city', title: __('City'), operate: 'LIKE' },
                        // { field: 'district', title: __('District'), operate: 'LIKE' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'info',
                                    title: '详情',
                                    icon: 'fa fa-drivers-license',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                    extend: 'data-toggle=\'tooltip\' data-area= \'["100%", "100%"]\'',
                                    success: function (data) {
                                        //ajax成功会刷新一下table数据列表
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'business/privatesea/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'business/privatesea/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'business/privatesea/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
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
            //给城市信息做初始化
            $("#city-picker").on("cp:updated", function () {
                var citypicker = $(this).data("citypicker");
                var code = citypicker.getCode("district") || citypicker.getCode("city") || citypicker.getCode("province");
                $("#c-city").val(code);
            });
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
