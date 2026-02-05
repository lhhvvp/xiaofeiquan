/**
 * date:2021/12/07
 * author:zhangximing
 * description:地图坐标拾取+加逆解析+正解析
 */
window._AMapSecurityConfig = {
    securityJsCode:'500a5b249c2efe84141884c5401999b2',
}
AMapLoader.load({
    "key": "4a804c431c312df552a971d54d645560",              // 申请好的Web端开发者Key，首次调用 load 时必填
    "version": "2.0",                                       // 指定要加载的 JSAPI 的版本，缺省时默认为 1.4.15
    "plugins": ['AMap.Scale,AMap.Geocoder,AMap.ElasticMarker,AMap.LngLat,AMap.ToolBar,AMap.Pixel,AMap.PlaceSearch,AMap.AutoComplete'],// 需要使用的的插件列表
    "AMapUI": {                                             // 是否加载 AMapUI，缺省不加载
        "version": '1.1',                                   // AMapUI 缺省 1.1
        "plugins":['overlay/SimpleMarker'],                 // 需要加载的 AMapUI ui插件
    },
    "Loca":{                // 是否加载 Loca， 缺省不加载
        "version": '2.0'  // Loca 版本，缺省 1.3.2
    },
}).then((AMap)=>{
        var scale = new AMap.Scale({
            visible: false
        });
        toolBar = new AMap.ToolBar({
            visible: false
        });
        var map = new AMap.Map("container", {
            zoom:11,
            center:[109.741193,38.290162],       // 默认显示榆林市
            showIndoorMap: true,                 // 显示地图自带室内地图
            //mapStyle: 'amap://styles/whitesmoke'
        });

        map.clearMap();  // 清除地图覆盖物

        // ajax请求现有位置信息
        // code ...

        // 设置鼠标样式
        map.setDefaultCursor('crosshair');

        // 比例尺插件。位于地图右下角，用户可控制其显示与隐藏。
        map.addControl(scale);
        map.addControl(toolBar);
        scale.show();               //左下角显示比例尺
        toolBar.show();             //右下角显示工具条  放大缩小
        

        // 地图加载完成做标记，默认为 榆林
        window.onload = function(){
            addMarker();
        }

        // 实例化点标记 添加marker标记
        function addMarker() {

            if (marker) {
                return;
            }
            marker = new AMap.Marker({
                icon: "//a.amap.com/jsapi_demos/static/demo-center/icons/poi-marker-default.png",
                position: [109.741193,38.290162],
                offset: new AMap.Pixel(-30, -60)    //修复标记位置 可根据icon的大小来调
            });
            marker.setMap(map);

            //鼠标点击marker弹出自定义的信息窗体
            marker.on('click', function (e) {
                infoWindow.open(map, marker.getPosition());
            });
        }

        var title_sub = '窗体还在开发中...';
        var title = title_sub,
            content = [];
            content.push("<img src='http://tpc.googlesyndication.com/simgad/5843493769827749134'>地址：xxxxxxx.....");
            content.push("电话：xxxxxxx.....");
            content.push("<a href='https://ditu.amap.com/detail/B000A8URXB?citycode=110105'>详细信息</a>");
        var infoWindow = new AMap.InfoWindow({
            isCustom: true,  //使用自定义窗体
            content: createInfoWindow(title, content.join("<br/>")),
            offset: new AMap.Pixel(16, -45)
        });

        //构建自定义信息窗体
        function createInfoWindow(title, content) {
            var info = document.createElement("div");
            info.className = "custom-info input-card content-window-card";

            //可以通过下面的方式修改自定义窗体的宽高
            //info.style.width = "400px";
            // 定义顶部标题
            var top = document.createElement("div");
            var titleD = document.createElement("div");
            var closeX = document.createElement("img");
            top.className = "info-top";
            titleD.innerHTML = title;
            closeX.src = "https://webapi.amap.com/images/close2.gif";
            closeX.onclick = closeInfoWindow;

            top.appendChild(titleD);
            top.appendChild(closeX);
            info.appendChild(top);

            // 定义中部内容
            var middle = document.createElement("div");
            middle.className = "info-middle";
            middle.style.backgroundColor = 'white';
            middle.innerHTML = content;
            info.appendChild(middle);

            // 定义底部内容
            var bottom = document.createElement("div");
            bottom.className = "info-bottom";
            bottom.style.position = 'relative';
            bottom.style.top = '0px';
            bottom.style.margin = '0 auto';
            var sharp = document.createElement("img");
            sharp.src = "https://webapi.amap.com/images/sharp.png";
            bottom.appendChild(sharp);
            info.appendChild(bottom);
            return info;
        }

        //关闭信息窗体
        function closeInfoWindow() {
            map.clearInfoWindow();
        }

        // 输入提示
        var autoOptions = {
            input: "address"
        };

        // 下拉搜索
        var auto = new AMap.AutoComplete(autoOptions);
        var placeSearch = new AMap.PlaceSearch({
            map: map
        });  //构造地点查询类
        auto.on("select", select);//注册监听，当选中某条记录时会触发
        function select(e) {
            placeSearch.setCity(e.poi.adcode);
            placeSearch.search(e.poi.name);  //关键字查询查询
        }

        var geocoder,marker;
        function geoCode() {
            if(!geocoder){
                var geocoder = new AMap.Geocoder({
                    city: "0912", //行政区编码与城市编码表
                });
            }
            var address  = $('input[name=title]').val();

            if(address == ''){
                address = '榆林';
            }

            geocoder.getLocation(address, function(status, result) {
                console.log(result);
                if (result.info === 'OK') {
                    var lnglat = result.geocodes[0].location
                    $('#longitude').val(lnglat.lng);
                    $('#latitude').val(lnglat.lat);
                    if(!marker){
                        marker = new AMap.Marker();
                        map.add(marker);
                    }
                    // 更新地图上锚点位置
                    marker.setPosition(lnglat);
                    map.setFitView(marker);
                }else{
                    layer.msg('当前位置不在榆林市内！');
                }
            });
        };
        document.getElementById("geo").onclick = geoCode;

        // 地图点击标记锚点
        var clickMark = function(e) {
            //console.log(e);
            $('#longitude').val(e.lnglat.lng);
            $('#latitude').val(e.lnglat.lat);
            // 更新地图上锚点位置
            marker.setPosition(e.lnglat);

            // 标签前 关闭上次打开的窗体
            closeInfoWindow();

            // 经纬度 转地址
            if(!geocoder){
                var geocoder = new AMap.Geocoder({
                    city: "0912", //行政区编码与城市编码表
                });
            }

            geocoder.getAddress(e.lnglat, function(status, result) {
                if (result.info === 'OK') {
                    var address = result.regeocode.formattedAddress;
                    document.getElementById('address').value = address;
                }else{
                    layer.msg('当前经纬度识别的地址无效！');
                }
            });
        };

        map.on('click', clickMark);

        
        $("#geo").click(function(e){
            if (e.keyCode === 13) {
                geoCode();
                return false;
            }
            return true;
        });
    }).catch((e)=>{
        console.error(e);  //加载错误提示
    });   