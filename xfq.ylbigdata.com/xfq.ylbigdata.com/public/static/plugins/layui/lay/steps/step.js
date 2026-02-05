layui.define(['jquery'], function (exports) {
    var $ = layui.$
        , step = {
            //设置全局项
            set: function (options) {
                var that = this;
                that.config = $.extend({}, that.config, options);
                return that;
            }
        }
        // 操作当前实例
        , thisIns = function () {
            var that = this, options = that.config;
            var elem = $(options.elem);
            return {
                next: function () {
                    var index = elem.find("li.active").length;
                    if (index == elem.find("li").length) {
                        return this;
                    }
                    elem.find("li").eq(index).addClass("active");
                    return this;
                },

                /**
                 * 上一步
                 */
                previous: function () {
                    var index = elem.find("li.active").length;
                    if (index == 1) {
                        return this;
                    }
                    elem.find("li").eq(index - 1).removeClass("active");
                    return this;
                },

                /**
                 * 去第几步
                 * @param step
                 */
                goto: function (s) {
                    if (s < 0 || s > elem.find("li").length) {
                        return this;
                    }
                    elem.find("li").removeClass("active");
                    var $target = elem.find("li").eq(s - 1);
                    $target.addClass("active");
                    $target.prevAll("li").addClass("active");
                    return this;
                }
            }
        }
        //构造器
        , Class = function (options) {
            var that = this;
            that.config = $.extend({}, that.config, step.config, options);
            that.render();
            return this;
        };
    //默认配置
    Class.prototype.config = {
        stepNames: ['', '', ''],
        initStep: 1
    };
    //渲染视图
    Class.prototype.render = function () {
        var that = this, options = that.config;
        var elem = $(options.elem);
        // 初始化样式
        var html = '';
        html += '<ul class="progressbar">';
        $.each(options.stepNames, function (index, name) {
            html += '<li';
            if (index < options.initStep) {
                html += ' class="active" ';
            }
            html += '>';
            html += name;
            html += '</li>';
        });
        html += '</ul>';
        elem.empty().append(html);
        // 计算宽度
        $(".progressbar li").css("width", 100 / options.stepNames.length + "%");
        
    };
    //核心入口
    step.render = function (options) {
        var ins = new Class(options);
        var ex = thisIns.call(ins);
        ins.config.thisIns = ex;
        return ex;
    };

    //加载组件所需样式
    layui.link(layui.cache.base + 'steps/step.css');

    exports('step', step);
});

