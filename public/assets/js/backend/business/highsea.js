define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            // 下面地址是整个页面的内容包括按钮地址
            // 下面这些地址会被赋值到index、add、edit.html里面的各个功能标签上
            // 然后点击页面上的按钮就会去到Highsea.php控制器文件中指定的方法名中, 通过$this -> view -> fetch()就可以打开新的模板页面
            // 其次当前js控制器文件和php控制器文件权重是一样的，Highsea.php文件中有多少个方法，这里就也要有几个方法(如info,那么在最下面就要有个info函数)
            Table.api.init({
                extend: {
                    // 请求数据的控制器地址
                    index_url: 'business/highsea/index',
                    // recyclebin_url: 'business/highsea/recyclebin' + location.search,
                    // add_url: 'business/highsea/add',
                    // edit_url: 'business/highsea/edit',
                    info_url: 'business/highsea/info', // 详情地址
                    apply_url: 'business/highsea/apply', // 申请地址
                    recovery_url: 'business/highsea/recovery', // 分配
                    del_url: 'business/highsea/del',
                    // multi_url: 'business/highsea/multi',
                    // apply_url: 'business/highsea/multi',
                    table: 'business',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'asc',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),sortable: true,},
                        // {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'gender', title: __('Gender'), searchList: {"0":__('保密'),"1":__('男'),"2":__('女')}, formatter: Table.api.formatter.normal},
                        { field: 'gender_text', title: __('Gender') },
                        // {field: 'sourceid', title: __('Sourceid')},
                        // {field: 'deal', title: __('Deal'), searchList: {"0":__('未成交'),"1":__('已成交')}, formatter: Table.api.formatter.normal},
                        { field: 'deal_text', title: __('Deal') },
                        { field: 'sourceid', title: __('Sourceid'), searchList: { "1": __('云课堂'), "2": __('家居商城') }, formatter: Table.api.formatter.normal },
                        // {field: 'sourceid', title: __('Sourceid')},
                        // {field: 'adminid', title: __('Adminid')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'invitecode', title: __('Invitecode'), operate: 'LIKE'},
                        // {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [{
                            // 详情按钮(被放置在index.html上)
                                name: 'info',
                                // 通过遍历将课程名称通过title附加上去
                                title: '详情',
                                icon: 'fa fa-drivers-license',
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.info_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["100%", "100%"]\'', // 控制弹窗大小
                            },{
                            // 申请按钮(被放置在index.html上)
                                name: 'apply',
                                title: '申请',
                                icon: 'fa fa-paper-plane',
                                classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.apply_url,
                                confirm: '是否确认申请?',
                                extend:"data-toggle='tooltip'", // 用于显示信息
                                success: function (res) {
                                    // 成功就会通过ajax属性table数据列表
                                    table.bootstrapTable('refresh');
                                }
                            },{
                            // 分配按钮(被放置在index.html上)
                                name: 'recovery',
                                // 通过遍历将课程名称通过title附加上去
                                title: '分配',
                                icon: 'fa fa-group',
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.recovery_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["85%", "85%"]\'', // 控制弹窗大小
                                success: function (res) {
                                    // 成功就会通过ajax属性table数据列表
                                    table.bootstrapTable('refresh');
                                }
                            },
                        ]},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 申请的弹框
            $(document).on("click", ".btn-apply", function () {
            var ids = Table.api.selectedids(table);
                Layer.confirm(__('是否确认申请？'),{ icon: 3, title: __('Warning'), shadeClose: true },function (index) {
                    Backend.api.ajax(
                        {url: $.fn.bootstrapTable.defaults.extend.apply_url + `?ids=${ids}`},
                        function() {
                        table.bootstrapTable('refresh');
                        Layer.close(index);
                        }
                    );
                    }
                );
            });

            // 分配的弹框
            $(document).on("click", ".btn-recovery", function () {
                var ids = Table.api.selectedids(table);
                Fast.api.open($.fn.bootstrapTable.defaults.extend.recovery_url + `?ids=${ids}`, '分配', {})
            });

        },
        // 回收站
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
                url: 'business/highsea/recyclebin' + location.search,
                // url: $.fn.bootstrapTable.defaults.extend.recyclebin_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),sortable: true,},
                        // {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        // {field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'gender', title: __('Gender'), searchList: {"0":__('保密'),"1":__('男'),"2":__('女')}, formatter: Table.api.formatter.normal},
                        // {field: 'deal', title: __('Deal'), searchList: {"0":__('未成交'),"1":__('已成交')}, formatter: Table.api.formatter.normal},
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
                                    url: 'business/highsea/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'business/highsea/destroy',
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
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        apply: function () {
            Controller.api.bindevent();
        },
        recovery: function () {
            console.log(5555555);
            Controller.api.bindevent();
        },
        info: function () {
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
              var panel = $($(this).attr("href"));
              if (panel.size() > 0) {
                Controller.table[panel.attr("id")].call(this);
                $(this).on('click', function (e) {
                  $($(this).attr("href")).find(".btn-refresh").trigger("click");
                });
              }
            });
      
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
      
            Controller.api.bindevent();
        },table: {
            // 客户资料方法
            base: function () {
                console.log(1111111111);
                Controller.api.bindevent();
            },
            // 回访列表
            visit: function () {

                // 获取客户ids
                var ids = Fast.api.query('ids') ? Fast.api.query('ids') : 0;

                // 初始化表格参数配置
                Table.api.init({
                  extend: {
                    visit_url: `business/highsea/visit?ids=${ids}`,
                    table: 'business_visit',
                  }
                });

                var viTable = $("#viTable");

                // 初始化表格
                viTable.bootstrapTable({
                  url: $.fn.bootstrapTable.defaults.extend.visit_url,
                  pk: 'id',
                  sortName: 'id',
                  // sortOrder: 'asc',
                  fixedColumns: true,
                  toolbar: '#toolbar2',
                  fixedRightNumber: 1,
                  columns: [
                    [
                      { checkbox: true },
                      { field: 'id', title: __('Id'), sortable: true,},
                      { field: 'business.nickname', title: __('Busid') },
                      { field: 'content', title: __('Content') },
                      { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime, sortable: true,},
                    // 操作
                    //   {
                    //     field: 'operate',
                    //     title: __('Operate'),
                    //     table: viTable,
                    //     events: Table.api.events.operate,
                    //     formatter: Table.api.formatter.operate
                    //   }
                    ]
                  ]
                });
                Controller.api.bindevent();
            },
            // 申请记录
            receive: function () {
                // 获取客户ids
                var ids = Fast.api.query('ids') ? Fast.api.query('ids') : 0;

                // 初始化表格参数配置
                Table.api.init({
                  extend: {
                    receive_url: `business/highsea/receive?ids=${ids}`,
                    // del_url:'business/info/des',
                    table: 'business_receive',
                  }
                });

                var reTable = $("#reTable");

                // 初始化表格
                reTable.bootstrapTable({
                  url: $.fn.bootstrapTable.defaults.extend.receive_url,
                  pk: 'id',
                  sortName: 'id',
                  fixedColumns: true,
                  fixedRightNumber: 1,
                  toolbar: '#toolbar3',
                  columns: [
                    [
                      { checkbox: true },
                      { field: 'id', title: __('Id'), sortable: true,},
                      { field: 'business.nickname', title: __('Busid') },
                      { field: 'status', title: __('Status') },
                      { field: 'applyid', title: __('Applyid') },
                      { field: 'applytime', title: __('Applytime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime, sortable: true,},
                    //   {
                    //     field: 'operate',
                    //     title: __('Operate'),
                    //     table: reTable,
                    //     events: Table.api.events.operate,
                    //     formatter: Table.api.formatter.operate,
                    //     //要在操作这一栏增添自定义的按钮

                    //   }
                    ]
                  ]
                });
                Controller.api.bindevent();
            },
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
