var newsGridApp = new Vue({
    el: '#newsGridBox',
    data: {
        pager: {
            totalPage: 0,
            recordsTotal: 0,
            limit: 4,
            start: 0,
            page: 1
        },
        data: [],
        isLoaded: false,
        loading: false,
        newFilter: {
            folder_id: '',
            status: '',
            hot: '',
            name: '',
            donvi_id: ''
        },
        list_folder: [],
        modal: null,
        formElm: null,
        checkAll: false,
        newIds: [],
        listNewIds: [],
        kichHoatClick: false,
        huyKichHoatClick: false,
        onDelete: false,
    },
    methods: {
        moment: function (date) {
            return moment(date);
        },

        initListDonvi() {
            var me = this;
            //donviTree.init();
            $('#cuocThiGridFilterForm').on('selectDonviNodeEvent', {}, function (e, id) {
                me.cuocThiGridFilter.donvi_id = id;
            });
        },

        selectAll: function () {
            this.checkAll = !this.checkAll
            if (this.checkAll) {
                this.newIds = this.listNewIds
            } else {
                this.newIds = []
            }
        },

        checkSelectAll: function () {
            this.$nextTick(function () {
                this.checkAll = _.isEqual(_.sortBy(this.newIds), _.sortBy(this.listNewIds))
            })
        },

        chageStatusNew: function (label) {
            var me = this;
            me.kichHoatClick = true;
            var data = {
                listId: me.newIds,
            };
            let status = label === 'kich_hoat' ? 1 : 2;
            let addMoreMsg = label === 'kich_hoat' ? 'Bật' : 'Tắt';
            if (this.newIds.length) {
                App.showConfirm('Bạn có chắc chắn thực hiện "' + addMoreMsg + '" tin tức?', function () {
                    $.ajax({
                        url: "/module/blog/service/news/chageStatusNew",
                        data: {
                            data: data,
                            status: status
                        },
                        success: function (d) {
                            me.kichHoatClick = false;
                            if (!d.success) App.showMessageWarning(d.msg);
                            else {
                                me.select_all = false;
                                me.newIds = [];
                                me.initGrid();
                                App.notification("Thông báo", $.getContent("Bạn đã '" + addMoreMsg + "' thành công {data} tin tức!", d));
                            }
                        }
                    });
                })
            } else {
                App.showMessage("Bạn hãy chọn ít nhất một mục !");
            }
        },

        deleteNew() {
            if (!this.newIds.length) return;
            var me = this;

            App.showConfirm("Bạn có chắc muốn xóa " + this.newIds.length + " tin tức", function () {
                me.onDelete = true;
                $.ajax({
                    url: '/module/blog/service/news/deleteNewByIds',
                    dataType: 'json',
                    data: {
                        ids: me.newIds
                    },
                    success: function (json) {
                        me.onDelete = false;
                        if (json) {
                            App.showMessageSuccess(json.message, 'Đồng ý', function () {
                                me.initGrid();
                            });
                        }
                    },
                    error: function (e) {
                        me.onDelete = false;
                        if (e.responseText) {
                            App.showMessageWarning(e.responseText);
                        }
                    }
                })
            });
        },

        initGrid: function () {
            var me = this;
            me.loading = true;
            me.isLoaded = false;

            /* @todo Lấy danh sách danh mục tin tức */
            $.post('/module/blog/service/newsCategory/getTreeNewsCategory', {}, function (req) {
                me.list_folder = req;
                me.loading = false;
            });

            /* @todo Lấy danh sách tin tức */

            var arrFilter = {
                start: me.pager.start,
                limit: me.pager.limit,
                folder_id: me.newFilter.folder_id,
                status: me.newFilter.status,
                hot: me.newFilter.hot,
                donvi_id: me.newFilter.donvi_id,
                title: me.newFilter.name
            };
            $.post('/module/blog/service/news/getListForGrid', { filters: arrFilter }, function (req) {
                me.data = req.data;
                me.checkAll = false;
                me.newIds = [];
                me.listNewIds = [];
                for (index in me.data) {
                    me.listNewIds.push(me.data[index].id);
                };
                me.pager.recordsTotal = req.recordsTotal;
                me.pager.totalPage = Math.ceil(me.pager.recordsTotal / me.pager.limit);

                me.loading = false;
                me.isLoaded = true;
            });
        },

        initGridBySearch: function () {
            var me = this;
            me.loading = true;
            me.isLoaded = false;
            var arrFilter = {
                start: me.pager.start,
                limit: me.pager.limit,
                folder_id: me.newFilter.folder_id,
                status: me.newFilter.status,
                hot: me.newFilter.hot,
                donvi_id: me.newFilter.donvi_id,
                title: me.newFilter.name
            };
            $.post('/module/blog/service/news/getListForGrid', { filters: arrFilter }, function (req) {
                me.data = req.data;
                me.pager.recordsTotal = req.recordsTotal;
                me.pager.totalPage = Math.ceil(me.pager.recordsTotal / me.pager.limit);

                me.loading = false;
                me.isLoaded = true;
            });
        },
        initGridByResetSearch: function (is_reset = 2) {
            var me = this;
            if (is_reset == 1) {
                me.newFilter.folder_id = "";
                me.newFilter.status = "";
                me.newFilter.name = "";
            }
            me.loading = true;
            me.loaded = false;
            me.updateData(1);
        },
        updateData: function (i) {
            this.pager.page = i;
            this.pager.start = (this.pager.page - 1) * this.pager.limit;
            this.initGrid();
        }
    },
    created: function () {
        this.initGrid();
        this.$nextTick(function () {
        });
    },
    mounted: function () {
        $('.selectpicker').selectpicker();
        this.initListDonvi();
    }
});