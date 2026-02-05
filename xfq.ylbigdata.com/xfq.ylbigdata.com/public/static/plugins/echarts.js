/*var statData = {
    "district": [
        {
            "name": "子洲县",
            "value": "4.52",
            "value1": "10.57"
        },
        {
            "name": "清涧县",
            "value": "5.74",
            "value1": "10.57"
        },
        {
            "name": "吴堡县",
            "value": "4.72",
            "value1": "10.57"
        },
        {
            "name": "佳县",
            "value": "6.03",
            "value1": "10.57"
        },
        {
            "name": "米脂县",
            "value": "7.37",
            "value1": "10.57"
        },
        {
            "name": "绥德县",
            "value": "7.51",
            "value1": "10.57"
        },
        {
            "name": "定边县",
            "value": "6.35",
            "value1": "10.57"
        },
        {
            "name": "靖边县",
            "value": "7.37",
            "value1": "10.57"
        },
        {
            "name": "府谷县",
            "value": "6.75",
            "value1": "10.57"
        },
        {
            "name": "神木市",
            "value": "10.82",
            "value1": "10.57"
        },
        {
            "name": "横山区",
            "value": "13.25",
            "value1": "10.57"
        },
        {
            "name": "榆阳区",
            "value": "19.57",
            "value1": "10.57"
        }
    ],
}*/

function echarts_map(areadata = []) {
  //var areadata = statData.district;
  var area = [];
  var total = 0;
  for (let i = 0; i < areadata.length; i++) {
    var value = parseInt(areadata[i].value);
    if (total < value) {
      total = value;
    }
    area[i] = areadata[i].name;
  }
  areavalue = setCeil(total);
  $.get("/static/plugins/610800_full.json", function (chinaJson) {
    echarts.registerMap('yulin', chinaJson);
    var mapchart = echarts.init(document.getElementById('map'));
    mapoption = {
      tooltip: {
        show: true,
        trigger: 'item',
        formatter: function (params) {
          if (params.seriesName != "") {
            let h = `${params.name}</br>商家总数:${params.value}</br>累计核销券:${params.data.writeoff}`
            return h;
          }
        },
      },
      title: [{
        //text: '全国主要城市 业务量',
        //subtext: '内部数据请勿外传',
        left: 'center',
        textStyle: {
          color: '#fff'
        }
      }],
      visualMap: {
        min: 0,
        max: areavalue,
        left: '90%',
        bottom: 10,
        text: ['高', '低'], // 文本，默认为数值文本
        calculable: true,
        color: ['#00169d', '#006be4', '#3eabff'],
        textStyle: {
          color: '#fff'
        },
        formatter: function (value) { //标签的格式化工具。
          return value; // 范围标签显示内容。
        }
      },
      grid: {
        left: 80,
        top: 60,
        bottom: '50%',
        width: '40%'
      },
      xAxis: {
        type: 'value',
        scale: true,
        show: true, //是否显示 x 轴
        position: 'top',
        boundaryGap: true,
        splitLine: {
          show: false
        },
        axisLine: {//坐标 轴线
          show: true,
          lineStyle: {
            color: 'rgba(255,255,255,0.8)',
          }
        },
        axisTick: {//坐标轴刻度相关设置
          show: true,
          lineStyle: {
            color: '#ddd'
          }
        },
        axisLabel: {
          fontSize: 10,
          margin: 5,
          rotate: 45,
          textStyle: {
            color: '#ddd'
          },
          formatter: function (value) { //标签的格式化工具。
            return value; // 范围标签显示内容。
          }
        },
      },
      yAxis: {
        type: 'category',
        //name: 'TOP 20',
        nameGap: 10,
        axisLine: {
          show: true,
          lineStyle: {
            color: '#ddd'
          }
        },
        axisTick: {
          show: false,
          lineStyle: {
            color: '#ddd'
          }
        },
        axisLabel: {
          interval: 0,
          textStyle: {
            color: '#ddd'
          }
        },
        data: area
      },
      series: [{
        name: '游客量',
        type: 'map',
        roam: false,
        zoom: 1,
        map: 'yulin',
        layoutCenter: ['60%', '50%'], // 地图中心在屏幕中的位置
        layoutSize: '80%', //地图的大小
        label: { // 图形上的文本标签
          normal: {
            show: true,
            color: '#ffffff'
          },
          emphasis: {
            color: '#f'
          }
        },
        itemStyle: {
          normal: {
            areaColor: "rgba(43, 196, 243, 0.42)",
            borderColor: "rgba(43, 196, 243, 1)",
            borderWidth: 1
          },
          emphasis: {
            areaColor: "#2B91B7"
          }
        },
        data: areadata // 上方定义的数据
      }, {
        id: 'bar',
        zlevel: 2,
        type: 'bar',
        symbol: 'none',
        itemStyle: {
          normal: {
            color: '#ddb926'
          }
        },
        data: areadata // 上方定义的数据
      }]

    };
    // 动态显示tootip
    var faultByHourIndex = 0; //播放所在下标

    let endTid = setInterval(function () { });
    for (let i = 0; i <= endTid; i++) {
      clearInterval(i)
    }

    var faultByHourTime = setInterval(function () { //使得tootip每隔三秒自动显示
      mapchart.dispatchAction({
        type: 'showTip', // 根据 tooltip 的配置项显示提示框。
        seriesIndex: 0,
        dataIndex: faultByHourIndex
      });
      faultByHourIndex++;
      // faultRateOption.series[0].data.length 是已报名纵坐标数据的长度
      if (faultByHourIndex > mapoption.series[0].data.length) {
        faultByHourIndex = 0;
      }
    }, 4000);

    mapchart.setOption(mapoption);
    window.addEventListener("resize", function () {
      mapchart.resize();
    });
  });

}

function echarts_map_tour(areadata = []) {
    //var areadata = statData.district;
    var area = [];
    var total = 0;
    for (let i = 0; i < areadata.length; i++) {
        var value = parseInt(areadata[i].value);
        if (total < value) {
            total = value;
        }
        area[i] = areadata[i].name;
    }
    areavalue = setCeil(total);
    $.get("/static/plugins/100000.json", function (chinaJson) {
        echarts.registerMap('yulin', chinaJson);
        var mapchart = echarts.init(document.getElementById('map'));
        mapoption = {
            tooltip: {
                show: true,
                trigger: 'item',
                formatter: function (params) {
                    if (params.seriesName != "") {
                        let h = `${params.name}</br>用户数量:${params.value || 0}`
                        return h;
                    }
                },
            },
            title: [{
                //text: '全国主要城市 业务量',
                //subtext: '内部数据请勿外传',
                left: 'center',
                textStyle: {
                    color: '#fff'
                }
            }],
            visualMap: {
                min: 0,
                show:false,
                max: areavalue,
                left: '90%',
                bottom: 10,
                text: ['高', '低'], // 文本，默认为数值文本
                calculable: false,
                color: ['#00169d', '#006be4','#5ba2ff','#a4c6fd', '#3eabff','#3eabff'],
                textStyle: {
                    color: '#fff'
                },
                formatter: function (value) { //标签的格式化工具。
                    return value; // 范围标签显示内容。
                }
            },

            series: [{
                name: '归属地',
                type: 'map',
                roam: false,
                zoom: 1,
                map: 'yulin',
                layoutCenter: ['50%', '50%'], // 地图中心在屏幕中的位置
                layoutSize: '100%', //地图的大小
                label: { // 图形上的文本标签
                    normal: {
                        show: true,
                        color: '#ffffff'
                    },
                    emphasis: {
                        color: '#ffffff'
                    }
                },
                itemStyle: {
                    normal: {
                        areaColor: "rgba(43, 196, 243, 0.42)",
                        borderColor: "rgba(43, 196, 243, 1)",
                        borderWidth: 1
                    },
                    emphasis: {
                        areaColor: "#2B91B7"
                    }
                },
                data: areadata // 上方定义的数据
            }]

        };

        mapchart.setOption(mapoption);
        window.addEventListener("resize", function () {
            mapchart.resize();
        });
    });

}

function echarts_map_yulin(areadata = []) {
    //var areadata = statData.district;
    var area = [];
    var total = 0;
    for (let i = 0; i < areadata.length; i++) {
        var value = parseInt(areadata[i].value);
        if (total < value) {
            total = value;
        }
        area[i] = areadata[i].name;
    }
    areavalue = setCeil(total);
    $.get("/static/plugins/610000_full.json", function (chinaJson) {
        echarts.registerMap('yulin', chinaJson);
        var mapchart = echarts.init(document.getElementById('map1'));
        mapoption = {
            tooltip: {
                show: true,
                trigger: 'item',
                formatter: function (params) {
                    if (params.seriesName != "") {
                        let h = `${params.name}</br>用户数量:${params.value || 0}`
                        return h;
                    }
                },
            },
            title: [{
                //text: '全国主要城市 业务量',
                //subtext: '内部数据请勿外传',
                left: 'center',
                textStyle: {
                    color: '#fff'
                }
            }],
            visualMap: {
                min: 0,
                max: areavalue,
                left: '90%',
                show:false,
                bottom: 10,
                text: ['高', '低'], // 文本，默认为数值文本
                calculable: false,
                color: ['#00169d', '#006be4','#5ba2ff','#a4c6fd', '#3eabff','#3eabff'],
                textStyle: {
                    color: '#fff'
                },
                formatter: function (value) { //标签的格式化工具。
                    return value; // 范围标签显示内容。
                }
            },

            series: [{
                name: '归属地',
                type: 'map',
                roam: false,
                zoom: 1,
                map: 'yulin',
                layoutCenter: ['50%', '40%'], // 地图中心在屏幕中的位置
                layoutSize: '80%', //地图的大小
                label: { // 图形上的文本标签
                    normal: {
                        show: true,
                        color: '#ffffff'
                    },
                    emphasis: {
                        color: '#ffffff'
                    }
                },
                itemStyle: {
                    normal: {
                        areaColor: "rgba(43, 196, 243, 0.42)",
                        borderColor: "rgba(43, 196, 243, 1)",
                        borderWidth: 1
                    },
                    emphasis: {
                        areaColor: "#2B91B7"
                    }
                },
                data: areadata // 上方定义的数据
            }]

        };

        mapchart.setOption(mapoption);
        window.addEventListener("resize", function () {
            mapchart.resize();
        });
    });

}
function setCeil(value) {
  if (value < 10) {
    return 10
  } else {
    let num = Number(value.toString().substring(0, 1));
    return (num + 1) * Math.pow(10, (value.toString().length - 1));
  }
}

function map() {


  $.get("/static/admin/js/echarts/610800_full.json", function (YLJson) {
    echarts.registerMap("YL", YLJson);
    var chart = echarts.init(document.getElementById("map"));
    chart.setOption({
      visualMap: {
        type: "continuous",
        min: 0,
        max: 100,
        text: ["High", "Low"],
        realtime: false,
        calculable: true,
        color: ["#3ADEF1", "#0089FC", "#0057FE"],
        show: false
      },
      tooltip: {
        show: true,
        formatter: function () {
          return 1
        },
      },
      geo: {
        map: "YL",
        label: {
          show: true,
          color: '#00549f',
          emphasis: {
            show: true,
            color: "#fff"
          }
        },
        tooltip: {
          show: true,
          formatter: function () {
            return 1
          },
        },
        roam: true,
        zoom: 1,
        itemStyle: {
          normal: {
            areaColor: "rgba(43, 196, 243, 0.42)",
            borderColor: "rgba(43, 196, 243, 1)",
            borderWidth: 1
          },
          emphasis: {
            areaColor: "#2B91B7"
          }
        }
      },

    });
  });
}
// map();
function echarts_33(data) {
  // 基于准备好的dom，初始化echarts实例
  var myChart = echarts.init(document.getElementById('fb3'));
  option = {
    title: {
      text: '用户年龄统计',
      show: true,
      left: 'center',
      textStyle: {
        color: '#333333',
        fontSize: '12'
      }
    },
    tooltip: {
      trigger: 'item',
      formatter: "{a} <br/>{b}: {c} ({d}%)",
      position: function (p) {   //其中p为当前鼠标的位置
        return [p[0] + 10, p[1] - 10];
      }
    },
    legend: {
      top: '72%',
      left: '10%',
      right: '2%',
      itemWidth: 10,
      itemHeight: 10,
      textStyle: {
        color: 'rgba(0,0,0,.5)',
        fontSize: '10',
      }
    },
    series: [
      {
        name: '用户年龄统计：',
        type: 'pie',
        center: ['50%', '42%'],
        radius: ['30%', '50%'],
        color: ['#065aab', '#066eab', '#0682ab', '#0696ab', '#06a0ab', '#06b4ab', '#06c8ab', '#06dcab', '#06f0ab'],
        label: { show: false },
        labelLine: { show: false },
        data: data,
      }
    ]
  };

  // 使用刚指定的配置项和数据显示图表。
  myChart.setOption(option);
  // var index1 = 0; //播放所在下标
  // var faultByHourTime = setInterval(function () { //使得tootip每隔三秒自动显示
  //     myChart.dispatchAction({
  //         type: 'showTip', // 根据 tooltip 的配置项显示提示框。
  //         seriesIndex: 0,
  //         dataIndex: index1
  //     });
  //     index1++;
  //     // faultRateOption.series[0].data.length 是已报名纵坐标数据的长度

  //     if (index1 > option.series[0].data.length) {
  //         index1 = 0;
  //     }
  // }, 4000);
  window.addEventListener("resize", function () {
    myChart.resize();
  });
}


function echarts_1(data) {
  // 基于准备好的dom，初始化echarts实例
  var myChart = echarts.init(document.getElementById('echart1'));

  let option = {
    //  backgroundColor: '#00265f',
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    grid: {
      left: '10px',
      top: '30px',
      right: '0%',
      bottom: '4%',
      containLabel: true
    },
    xAxis: [{
      type: 'category',
      data: ['城中村', '老旧小区', '城乡结合部', '拆迁安置区', '疫情隔离场所', '学校', '医院', '工厂', '工地周边', '其他'],
      axisLine: {
        show: true,
        lineStyle: {
          color: "rgba(0,0,0,.1)",
          width: 1,
          type: "solid"
        },
      },

      axisTick: {
        show: false,
      },
      axisLabel: {
        interval: 0,
        rotate: 20,
        // rotate:50,
        show: true,
        splitNumber: 15,
        textStyle: {
          color: "rgba(0,0,0,.6)",
          fontSize: '10',
        },
      },
    }],
    yAxis: [{
      type: 'value',
      axisLabel: {
        //formatter: '{value} %'
        show: true,
        textStyle: {
          color: "rgba(0,0,0,.6)",
          fontSize: '10',
        },
      },
      axisTick: {
        show: false,
      },
      axisLine: {
        show: true,
        lineStyle: {
          color: "rgba(0,0,0,.1)",
          width: 1,
          type: "solid"
        },
      },
      splitLine: {
        lineStyle: {
          color: "rgba(0,0,0,.1)",
        }
      }
    }],
    series: [
      {
        type: 'bar',
        data: data,
        barWidth: '35%', //柱子宽度
        // barGap: 1, //柱子之间间距
        itemStyle: {
          normal: {
            color: '#2f89cf',
            opacity: 1,
            barBorderRadius: 5,
          }
        }
      }
    ]
  };

  // 使用刚指定的配置项和数据显示图表。
  // 动态显示tootip
  // var faultByHourIndex = 0; //播放所在下标
  // var faultByHourTime = setInterval(function () { //使得tootip每隔三秒自动显示
  //     myChart.dispatchAction({
  //         type: 'showTip', // 根据 tooltip 的配置项显示提示框。
  //         seriesIndex: 0,
  //         dataIndex: faultByHourIndex
  //     });
  //     faultByHourIndex++;
  //     // faultRateOption.series[0].data.length 是已报名纵坐标数据的长度
  //     if (faultByHourIndex > option.series[0].data.length) {
  //         faultByHourIndex = 0;
  //     }
  // }, 4000);
  myChart.setOption(option);
  window.addEventListener("resize", function () {
    myChart.resize();
  });
}

function echarts10(datas) {
  // 基于准备好的dom，初始化echarts实例
  var myChart = echarts.init(document.getElementById('echart1'));
  let date = [], array1 = [], array2 = [];
  for (let key in datas) {
    if (datas[key] != null && datas[key].length != 0) {
      date.push(datas[key][0].ref_date);
      array1.push(datas[key][0].visit_total);
      array2.push(datas[key][0].share_uv);
    }
  };
  let option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        lineStyle: {
          color: '#333'
        }
      }
    },
    legend: {
      top: '0%',
      data: ['当日会员新增数', '当日会员认证数'],
      textStyle: {
        color: 'rgba(0,0,0,.5)',
        fontSize: '10',
      }
    },
    grid: {
      left: '10',
      top: '30',
      right: '10',
      bottom: '10',
      containLabel: true
    },

    xAxis: [{
      type: 'category',
      boundaryGap: false,
      axisLabel: {
        textStyle: {
          color: 'rgba(0,0,0,.5)',
          fontSize: 10,
        },
      },
      axisLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }

      },

      data: date

    }, {

      axisPointer: { show: false },
      axisLine: { show: false },
      position: 'bottom',
      offset: 20,



    }],

    yAxis: [{
      type: 'value',
      axisTick: { show: false },
      axisLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }
      },
      axisLabel: {
        textStyle: {
          color: 'rgba(0,0,0,.5)',
          fontSize: 10,
        },
      },

      splitLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }
      }
    }],
    series: [
      {
        name: '当日会员新增数',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 5,
        showSymbol: false,
        lineStyle: {

          normal: {
            color: '#0184d5',
            width: 2
          }
        },
        areaStyle: {
          normal: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
              offset: 0,
              color: 'rgba(1, 132, 213, 0.4)'
            }, {
              offset: 0.8,
              color: 'rgba(1, 132, 213, 0.1)'
            }], false),
            shadowColor: 'rgba(0, 0, 0, 0.1)',
          }
        },
        itemStyle: {
          normal: {
            color: '#0184d5',
            borderColor: 'rgba(221, 220, 107, .1)',
            borderWidth: 12
          }
        },
        data: array1

      },
      {
        name: '当日会员认证数',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 5,
        showSymbol: false,
        lineStyle: {

          normal: {
            color: '#dc3545',
            width: 2
          }
        },
        areaStyle: {
          normal: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
              offset: 0,
              color: 'rgba(220, 53, 69, 0.4)'
            }, {
              offset: 0.8,
              color: 'rgba(220, 53, 69, 0.1)'
            }], false),
            shadowColor: 'rgba(0, 0, 0, 0.1)',
          }
        },
        itemStyle: {
          normal: {
            color: '#dc3545',
            borderColor: 'rgba(220, 53, 69, .1)',
            borderWidth: 12
          }
        },
        data: array2

      },

    ]

  };

  // 动态显示tootip
  var faultByHourIndex = 0; //播放所在下标



  setInterval(function () { //使得tootip每隔三秒自动显示

    myChart.dispatchAction({
      type: 'showTip', // 根据 tooltip 的配置项显示提示框。
      seriesIndex: 0,
      dataIndex: faultByHourIndex
    });
    faultByHourIndex++;
    // faultRateOption.series[0].data.length 是已报名纵坐标数据的长度
    if (faultByHourIndex > option.series[0].data.length) {
      faultByHourIndex = 0;
    }
  }, 4000);


  // 使用刚指定的配置项和数据显示图表。
  myChart.setOption(option);
  window.addEventListener("resize", function () {
    myChart.resize();
  });

}

function echarts11() {
  // 基于准备好的dom，初始化echarts实例
  var myChart = echarts.init(document.getElementById('echart1'));
  let data = {
    date: ['01', '03', '05', '07', '09', '12', '15', '18', '20', '22', '24'],
    array1: [3, 4, 13, 4, 3, 4, 3, 6, 2, 4, 2, 4],
    array2: [3, 4, 3, 4, 3, 14, 13, 6, 2, 14, 2, 4]
  }
  let option = {
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        lineStyle: {
          color: '#333'
        }
      }
    },
    legend: {
      top: '0%',
      data: ['历史访问量', '今日访问量'],
      textStyle: {
        color: 'rgba(0,0,0,.5)',
        fontSize: '10',
      }
    },
    grid: {
      left: '10',
      top: '30',
      right: '10',
      bottom: '10',
      containLabel: true
    },

    xAxis: [{
      type: 'category',
      boundaryGap: false,
      axisLabel: {
        textStyle: {
          color: 'rgba(0,0,0,.5)',
          fontSize: 10,
        },
      },
      axisLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }

      },

      data: data.date

    }, {

      axisPointer: { show: false },
      axisLine: { show: false },
      position: 'bottom',
      offset: 20,



    }],

    yAxis: [{
      type: 'value',
      axisTick: { show: false },
      axisLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }
      },
      axisLabel: {
        textStyle: {
          color: 'rgba(0,0,0,.5)',
          fontSize: 10,
        },
      },

      splitLine: {
        lineStyle: {
          color: 'rgba(0,0,0,.1)',
        }
      }
    }],
    series: [
      {
        name: '历史访问量',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 5,
        showSymbol: false,
        lineStyle: {

          normal: {
            color: '#0184d5',
            width: 2
          }
        },
        areaStyle: {
          normal: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
              offset: 0,
              color: 'rgba(1, 132, 213, 0.4)'
            }, {
              offset: 0.8,
              color: 'rgba(1, 132, 213, 0.1)'
            }], false),
            shadowColor: 'rgba(0, 0, 0, 0.1)',
          }
        },
        itemStyle: {
          normal: {
            color: '#0184d5',
            borderColor: 'rgba(221, 220, 107, .1)',
            borderWidth: 12
          }
        },
        data: data.array1

      },
      {
        name: '今日访问量',
        type: 'line',
        smooth: true,
        symbol: 'circle',
        symbolSize: 5,
        showSymbol: false,
        lineStyle: {

          normal: {
            color: '#00d887',
            width: 2
          }
        },
        areaStyle: {
          normal: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
              offset: 0,
              color: 'rgba(0, 216, 135, 0.4)'
            }, {
              offset: 0.8,
              color: 'rgba(0, 216, 135, 0.1)'
            }], false),
            shadowColor: 'rgba(0, 0, 0, 0.1)',
          }
        },
        itemStyle: {
          normal: {
            color: '#00d887',
            borderColor: 'rgba(221, 220, 107, .1)',
            borderWidth: 12
          }
        },
        data: data.array2

      },

    ]

  };

  // 动态显示tootip
  var faultByHourIndex = 0; //播放所在下标



  setInterval(function () { //使得tootip每隔三秒自动显示

    myChart.dispatchAction({
      type: 'showTip', // 根据 tooltip 的配置项显示提示框。
      seriesIndex: 0,
      dataIndex: faultByHourIndex
    });
    faultByHourIndex++;
    // faultRateOption.series[0].data.length 是已报名纵坐标数据的长度
    if (faultByHourIndex > option.series[0].data.length) {
      faultByHourIndex = 0;
    }
  }, 4000);

  // 使用刚指定的配置项和数据显示图表。
  myChart.setOption(option);
  window.addEventListener("resize", function () {
    myChart.resize();
  });

}

function echarts_5(dataValue) {
        let xData  =  [] ;
        let data = [];
        dataValue.forEach(element => {
          console.log(element);
            if(!!element.province){
              xData.push(element.province);
             
              data.push(element.total);
            }
        });
        // 基于准备好的dom，初始化echarts实例
        var myChart = echarts.init(document.getElementById('echarts_5'));

        option = {
            // backgroundColor: "#141f56",
            tooltip: {
                show: "true",
                trigger: 'item',
                textStyle:{
                  color:'#fff'
                },
                backgroundColor: 'rgba(0,0,0,0.4)', // 背景
                padding: [5], //内边距
                formatter: function(params) {
                    if (params.seriesName != "") {
                        return params.name + ' ：  ' + params.value + '人';
                    }
                },

            },
            grid: {
                borderWidth: 0,
                top: 20,
                bottom: 35,
                left:55,
                right:30,
                textStyle: {
                    color: "#fff"
                }
            },
            xAxis: [{
                type: 'category',
                axisTick: {
                    show: false
                },
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#f1f1f1',
                    }
                },
                axisLabel: {
                    inside: false,
                    interval: 0,    //强制文字产生间隔
                    // rotate: 45,     //文字逆时针旋转45°
                    textStyle: {
                        color: '#bac0c0',
                        fontWeight: 'normal',
                        fontSize: '10',
                    },
                },
                data: xData,
            }, {
                type: 'category',
                axisLine: {
                    show: false
                },
                axisTick: {
                    show: false
                },
                axisLabel: {
                    show: false
                },
                splitArea: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                data: xData,
            }],
            yAxis: {
                type: 'value',
                axisTick: {
                    show: false
                },
                axisLine: {
                    show: true,
                    lineStyle: {
                        color: '#f1f1f1',
                    }
                },
                splitLine: {
                    show: true,
                    lineStyle: {
                        color: '#f1f1f1',
                    }
                },
                axisLabel: {
                    textStyle: {
                        color: '#bac0c0',
                        fontWeight: 'normal',
                        fontSize: '10',
                    },
                    formatter: '{value}',
                },
            },
            series: [{
                type: 'bar',
                itemStyle: {
                    normal: {
                        show: true,
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                            offset: 0,
                            color: '#00c0e9'
                        }, {
                            offset: 1,
                            color: '#3b73cf'
                        }]),
                        barBorderRadius: 50,
                        borderWidth: 0,
                    },
                    emphasis: {
                        shadowBlur: 15,
                        shadowColor: 'rgba(105,123, 214, 0.7)'
                    }
                },
                zlevel: 2,
                barWidth: '15%',
                data: data,
            }]
        }


        // 使用刚指定的配置项和数据显示图表。
        myChart.setOption(option);
        window.addEventListener("resize",function(){
            myChart.resize();
        });
    }


