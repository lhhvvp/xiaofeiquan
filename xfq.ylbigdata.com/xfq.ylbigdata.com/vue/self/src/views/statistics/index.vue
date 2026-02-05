<template>
  <div class="main">
    <div class="card">
      <el-row :gutter="12">
        <el-col :span="8">
          <el-card shadow="hover" class="greenBg">
            <div class="number">{{ info.today_price }} <span>元</span></div>
            <div class="tit">今日累计收款金额</div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card shadow="hover" class="blueBg">
            <div class="number">{{ info.cash_total }} <span>元</span></div>
            <div class="tit">今日现金收款金额</div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card shadow="hover" class="redBg">
            <div class="number">{{ info.cash_not_total }} <span>元</span></div>
            <div class="tit">今日扫码收款金额</div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    <div class="main-box" ref="main" id="main">

    </div>
  </div>
</template>

<script setup>
import * as echarts from "echarts";
import {onMounted, ref} from "vue";
import {useStore} from "vuex";
const store = useStore();
const main = ref();
const info = ref({});
function init() {
  // 基于准备好的dom，初始化echarts实例
  var myChart = echarts.init(main.value);
  let date = [], array1 = [], array2 = [];
  let datas = info.value.data_chart;
  for (let key in datas) {
    if (datas[key] != null && datas[key].length != 0) {
      date.push(datas[key].ref_date);
      array1.push(datas[key].cash_not_total);
      array2.push(datas[key].cash_total);
    }
  };
  // 指定图表的配置项和数据
  let option = {
    title:{
      text:"七天收款金额统计",
    },
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        lineStyle: {
          color: '#333'
        }
      }
    },
    legend: {
      top: '6%',
      data: ['扫码收款金额', '现金收款金额'],
      textStyle: {
        color: 'rgba(0,0,0,.5)',
        fontSize: '14',
      }
    },
    grid: {
      left: '10',
      top: '15%',
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

      axisPointer: {show: false},
      axisLine: {show: false},
      position: 'bottom',
      offset: 20,

    }],

    yAxis: [{
      type: 'value',
      axisTick: {show: false},
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
        name: '扫码收款金额',
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
        name: '现金收款金额',
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

      }]
  }
  // 使用刚指定的配置项和数据显示图表。
  myChart.setOption(option);
}

onMounted(() => {
  let userinfo = store.state.UserInfo;
  let data = {
    bstr: userinfo.businesstr,
    uuid: userinfo.uuid,
  };
  store.dispatch('statistics', data).then((res)=>{
    info.value = res.data;
    init();

  })
})
</script>

<style scoped>
.main {
  width: calc(100% - 42px);
  padding: 20px;
  border-radius: 10px;
  height: calc(100% - 42px);
  background: #ffffff;
  border: 1px solid #409eff50;
}

.number, .tit {
  text-align: center;
  color: #ffffff;
}

.number {
  font-size: 46px;
  font-weight: bold;
  margin-bottom: 6px;
}

.number span {
  font-size: 16px;
  font-weight: 100;
}

.greenBg {
  background-color: #2fc7d3;
}

.blueBg {
  background-color: #349adf;
}

.redBg {
  background-color: #dc3545;
}

.main-box {
  width: 100%;
  margin-top: 50px;
  height: 504px;
}

</style>