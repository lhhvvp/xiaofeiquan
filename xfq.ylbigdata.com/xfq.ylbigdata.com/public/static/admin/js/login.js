// 回车触发登录
$(document).keyup(function (event) {
    if (event.keyCode == 13) {
        $(".login").trigger("click");
    }
});
// ceshi..
$(function () {
    // 后台登录
    $(".login").click(function () {
        var username = $("input[name='username']").val();
        var password = $("input[name='password']").val();
        var __token__ = $("input[name='__token__']").val();
        var vercode = $("input[name='vercode']").val();
        var pubkey = { username, password, __token__, vercode };
        
        if (!username) {
            layer.alert('请输入用户名', {
                icon: 2
            }, function (index) {
                layer.close(index);
                $("input[name='username']").focus();
            });
            return false;
        }
        if (!password) {
            layer.alert('请输入密码', {
                icon: 2
            }, function (index) {
                layer.close(index);
                $("input[name='password']").focus();
            });
            return false;
        };
        pubkey = sm2Encrypt(JSON.stringify(pubkey), 0, 1);
        password = hex_md5(password);
        $.ajax({
            type: "post",
            url: "/admin/login/checkLogin",
            data: {
                username: username,
                password: password,
                vercode: vercode,
                __token__: __token__,
                pubkey: pubkey,
            },
            dataType: "json",
            success: function (data) {
                if (data.error == 1) {
                    layer.alert(data.msg, {
                        icon: 2
                    }, function (index) {
                        layer.close(index);
                        // $(".layadmin-user-login-codeimg").attr("src", $(".layadmin-user-login-codeimg").attr("src") + '?' + Math.random());
                        $(".v-code img").trigger("click")
                        $("input[name='vercode']").val('').focus();
                    });
                } else if (data.error == 2) {
                    layer.alert(data.msg, {
                        icon: 2
                    }, function (index) {
                        layer.close(index);
                        window.location.reload();
                    });
                } else {
                    window.location.href = data.href;
                }
            },
            error: function (xhr) {

            }
        });

    })
})