<template>
  <div class="main backdrop-filter">
    <div class="table-box">

      <vue3-pro-table :columns="columns" :request="getList" ref="proTable" stripe border :hideTitleBar="true"
                      @selection-change="selection" :pagination="false" v-if="is_table"
                      :search="{fields:[{
      type: 'select', label: '类型', name: 'order_status',defaultValue: 'idCard', options: [{
        value: 'order',
        name: '订单号'
      }, {
        value: 'idCard',
        name: '身份证号',
      }]
    },
    {type: 'input', label: '编号/身份证', name: 'order' ,defaultValue:keyWord},
  ]}">
        <template #operate="scope">
          <el-button size="mini" type="primary" @click.prevent="submit(false,scope.row)">打印门票</el-button>
        </template>
      </vue3-pro-table>
      <div style="padding: 0 20px 0">
        <el-button type="primary" @click="ReadIDCard"
                   v-loading.fullscreen.lock="fullscreenLoading">识别身份证
        </el-button>
        <el-button type="primary" @click="submit(true,{})" v-if="keyWord">打印选中门票</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Vue3ProTable from "vue3-pro-table"

const is_table = ref(true);
const proTable = ref(null) //vue 缓存,
import {onMounted, ref, inject} from "vue";
import {ElMessage, ElMessageBox} from "element-plus";
import {useStore} from "vuex";

const $api = inject('api')
const store = useStore() //vue 缓存,
const keyWord = ref('')
const columns = ref([
  {label: "", type: "selection", width: 60},
  {label: "序号", type: "index", width: 60, align: "center"},
  {label: "门票名称", prop: "ticket_title", width: 240, align: "center"},
  {label: "订单号", prop: "trade_no", width: 240, align: "center"},
  // {label: "数量", prop: "ticket_number", align: "center", defaultValue: "1"},
  {label: "金额(元)", prop: "ticket_price", align: "center"},
  {label: "购买时间", prop: "create_time", align: "center"},
  {label: "有效期", prop: "ticket_date", align: "center"},
  {
    label: "操作",
    fixed: "right",
    width: 180,
    align: "center",
    tdSlot: "operate", // 自定义单元格内容的插槽名称
  },
]);

const getList = async (data) => {
  keyWord.value = data.order;
  let val = [];
  if (data.order_status === 'order') {
    await orderAjax(data).then(res => {
      val = res.data;
    });
  } else {
    await idcardAjax(data).then(res => {
      if (res) {
        val = res.data;
      }
    });
  }
  return {
    data: val,
    total: 0,
  }
}

async function orderAjax() {
  let option = {
    order_sn: keyWord.value,
  };
  return await store.dispatch('orderListSearch', option);
}

async function idcardAjax() {
  let option = {
    idcard: keyWord.value,
  };
  return await store.dispatch('orderListIdcard', option);
}


const selectionVal = ref('');

function submit(all = false, val) {
  let id = null;
  if (all) {
    //多选
    if (selectionVal.value === '') {
      ElMessage({
        message: '请选择数据' || 'error', type: 'warning', showClose: true, duration: 3 * 1000
      });
      return false;
    }
    id = selectionVal.value;
  } else {
    //单选
    id = val.ticket_code;
  }
  ajax(id);
}

function ajax(id) {
  let option = {
    codes: id
  }
  store.dispatch('takeTickets', option).then(res => {
    if (res.code === 0) {
      ElMessage({
        message: res.msg || 'success', type: 'success', showClose: true, duration: 3 * 1000
      });
      proTable.value.refresh();
    } else {
      ElMessage({
        message: res.msg || 'error', type: 'warning', showClose: true, duration: 3 * 1000
      });
    }
  });
}

function selection(e) {
  if (e) {
    let ids = e.map(item => {
      return item.ticket_code;
    });
    ids = ids.join(",");
    selectionVal.value = ids;
  }
}

// 识别身份证
const fullscreenLoading = ref(false);
const websockets = ref(null), isconncet = ref(false);

function socketChange() {
  let state = websockets.value.readyState;
  //读身份证
  if (1 === state) {
    isconncet.value = true;
  }
  if (3 === state) {
    isconncet.value = false;
  }
}

function OcrRecognition() {
  if (!isconncet.value) {
    // 1. 创建websockets对象，参数为服务器websockets地址
    var url = "ws:127.0.0.1:7415"
    websockets.value = new WebSocket(url);
    websockets.value.onopen = () => socketChange();
    //监听接收消息的情况
    websockets.value.onmessage = (res) => {
      if (res.data == "failed to obtain ID card information") {
        return;
      }
      fullscreenLoading.value = false;
      var alldata = res.data.split("|");
      if (alldata.length >= 17) {
        keyWord.value = alldata[6];
        is_table.value = true;
      } else {
        is_table.value = true;
        ElMessage({
          message: res.data,
          type: 'warning',
        })
      }
    }
    //监听关闭时的状态变化
  } else {
    closeConnect();
  }
}

/**
 *   关闭连接
 *
 */
function closeConnect() {
  websockets.value.close();
}

function ReadIDCard() {

  if (!isconncet.value) {
    ElMessageBox.confirm(
        '未连接服务! 请打开服务、驱动未安装,请安装驱动后重试！',
        'Warning',
        {
          confirmButtonText: '下载驱动',
          cancelButtonText: '取消',
          type: 'warning',
        }
    )
        .then(() => {
          window.open($api.urli + 'download/drive.zip');
        })
        .catch(() => {

        })
  } else {
    let paramTimeOut = 10000;
    paramTimeOut = "timeout=" + paramTimeOut;
    var parameterAll = paramTimeOut;
    let val = "03?" + parameterAll;
    websockets.value.send(val);
    fullscreenLoading.value = true;
    is_table.value = false;
    keyWord.value = '';
  }
}


onMounted(() => {
  OcrRecognition();
});

</script>
<style>
body {

}
</style>

<style scoped>
.main {
  width: calc(100% - 42px);
  height: calc(100% - 42px);
  padding: 20px;
  border-radius: 10px;
  background-color: none;
  border: 1px solid #409eff50;
}

.table-box {
  position: relative;
  width: 100%;
  height: 100%;
}


</style>