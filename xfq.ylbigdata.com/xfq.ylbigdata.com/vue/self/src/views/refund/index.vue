<template>
  <div class="main backdrop-filter">
    <el-config-provider :locale="zhCn">
      <vue3-pro-table :columns="columns" :request="getList" stripe border :hideTitleBar="true" :search="{fields:[
          {type:'input',label:'联系人',name:'contact_man'},
          {type:'input',label:'手机号',name:'contact_phone'},
          // {type:'input',label:'订单号',name:'order'},
          {type:'select',label:'订单状态',name:'order_status' ,options:[{
            value:'created',
            name:'已创建未支付'
          },{
            value:'paid',
            name:'已支付'
          },{
            value:'paid',
            name:'已支付'
          },{
            value:'used',
            name:'已使用/核销'
          },{
            value:'cancelled',
            name:'已取消'
          },{
            value:'refunded',
            name:'已退款'
          }]},
          {type:'daterange',label:'日期',name:'create_time'},

      ]}">
        <template #operate="scope">
          <el-button size="mini" type="primary" @click.prevent="info(scope)">查看</el-button>
        </template>
      </vue3-pro-table>
    </el-config-provider>

    <el-dialog v-model="dialogTableVisible" title="订单详情" align-center destroy-on-close  append-to-body>

      <template #default>
        <div class="info-item">
          <div class="item-info">
            <div class="item-info-lable">订单号：</div>
            <div class="item-info-centent">{{ orderDetail.trade_no }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">订单金额：</div>
            <div class="item-info-centent">{{ orderDetail.amount_price }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">联系人：</div>
            <div class="item-info-centent">{{ orderDetail.contact_man }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">联系方式：</div>
            <div class="item-info-centent">{{ orderDetail.contact_phone }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">下单时间：</div>
            <div class="item-info-centent">{{ orderDetail.create_time }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">退款状态：</div>
            <div class="item-info-centent">{{ orderDetail.order_status_text }}</div>
          </div>
        </div>
        <div class="info-item" v-for="(item,index) in orderDetail.detail">
          <div class="info-tit">{{ item.ticket_title }}</div>
          <div class="item-info">
            <div class="item-info-lable">门票名称：</div>
            <div class="item-info-centent">{{ item.ticket_title }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">门票数量：</div>
            <div class="item-info-centent">{{ item.ticket_number }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">门票时间：</div>
            <div class="item-info-centent">{{ item.ticket_date }}</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">门票单价：</div>
            <div class="item-info-centent">{{ item.ticket_price }} 元</div>
          </div>
          <div class="item-info">
            <div class="item-info-lable">门票总价：</div>
            <div class="item-info-centent">{{ item.ticket_number * Number(item.ticket_price) }} 元</div>
          </div>

          <div class="item-info">
            <div class="item-info-lable">退款状态：</div>
            <div class="item-info-centent">{{ item.refund_status_text }}</div>
          </div>
          <div class="item-info" v-if="item.refund_time">
            <div class="item-info-lable">退款时间：</div>
            <div class="item-info-centent">{{ item.refund_time }}</div>
          </div>
          <div class="item-info-blcok" v-if="item.refund_status !== 'fully_refunded'">
            <el-button type="danger" plain @click="refund(item)">立即退款</el-button>
          </div>
        </div>
        <el-dialog
            v-model="is_refund"
            width="30%"
            title="请填写退款信息"
            append-to-body
        >

          <div class="item-info-blcok">
            <div style="margin-bottom: 10px" v-if="refundVal.stock !==0 ">
              <span>数量：</span>
              <el-input-number min="0" v-model="refundVal.number" :max="refundVal.stock" style="width: 100px"
                               @change="change($event)"></el-input-number>
            </div>
            <el-input type="textarea" v-model="refundVal.refund_desc" maxlength="200" show-word-limit
                      placeholder="请输入退款备注"
                      :autosize="{minRows: 4, maxRows: 10}"></el-input>
          </div>
          <template #footer>
            <div class="dialog-footer">
              <el-button @click="is_refund = false">关闭</el-button>
              <el-button type="danger" @click="refundSubmit()">
                确定提交
              </el-button>
            </div>
          </template>

        </el-dialog>
      </template>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="dialogTableVisible = false">关闭</el-button>
          <el-button type="danger" @click="refund()" v-if="orderDetail.refund_status !== 'fully_refunded'">
            全部退款
          </el-button>
        </div>
      </template>

    </el-dialog>

  </div>
</template>

<script setup>
import Vue3ProTable from "vue3-pro-table"
import {ref, reactive} from "vue";
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import {useStore} from "vuex";
import {ElMessage} from "element-plus";

const store = useStore(), //vue 缓存,
    total = ref(0),
    value = ref(0),
    userinfo = ref(store.state.UserInfo),
    dialogTableVisible = ref(false),
    orderDetail = ref({}),
    is_refund = ref(false);

const refundVal = reactive({
  number: 0,
  stock: 0,
  out_trade_no: null,
  refund_desc: null,
})
const change = function (e) {
  refundVal.number = e;
}

const refund = (e) => {
  refundVal.out_trade_no = null;
  refundVal.refund_desc = null;
  refundVal.stock = 0;
  refundVal.number = 0;
  if (e) {
    refundVal.stock = e.ticket_number;
    refundVal.out_trade_no = e.out_trade_no;
  } else {
    refundVal.stock = 0;
  }
  is_refund.value = true;
};
const refundSubmit = () => {

  if (!refundVal.refund_desc) {
    ElMessage({
      message: '请填写备注信息',
      type: 'warning',
    })
    return false;
  };


  if (refundVal.out_trade_no == null) {
    //全部
    let option = {
      out_trade_no: orderDetail.value.out_trade_no,
      refund_desc: refundVal.refund_desc,
    };

    store.dispatch('refundAll', option).then(res => {
      if (res.code === 0) {
        ElMessage({
          message: res.msg,
          type: 'success',
        });
        window.location.reload();
      } else {
        ElMessage({
          message: res.msg,
          type: 'warning',
        })
      }

    });

  } else {
    //单条
    let option = {
      out_trade_no: refundVal.out_trade_no,
      ticket_number: refundVal.number,
      refund_desc: refundVal.refund_desc,
    };
    if (!refundVal.number) {
      ElMessage({
        message: '请选择数量',
        type: 'warning',
      })
      return false;
    }
    store.dispatch('refundOne', option).then(res => {
      if (res.code === 0) {
        ElMessage({
          message: res.msg,
          type: 'success',
        });
        window.location.reload();
      } else {
        ElMessage({
          message: res.msg,
          type: 'warning',
        })
      }
    });
  }
  return false;


}

const info = (e) => {
  // e.row
  let option = {
    bstr: userinfo.value.businesstr,
    trade_no: e.row.trade_no,
  };
  store.dispatch('orderDetail', option).then(res => {
    orderDetail.value = res.data;
    dialogTableVisible.value = true;
    // console.log(orderDetail.value.trade_no);
  });
}

const columns = ref([
  {label: "序号", type: "index", width: 60},
  {label: "订单号", prop: "trade_no", width: 240},
  {label: "金额", prop: "origin_price"},
  {label: "姓名", prop: "contact_man"},
  {label: "手机号", prop: "contact_phone"},
  {label: "订单状态", prop: "order_status_text"},
  {label: "退款状态", prop: "refund_status_text"},
  {label: "购买时间", prop: "create_time"},
  {
    label: "操作",
    fixed: "right",
    width: 180,
    align: "center",
    tdSlot: "operate", // 自定义单元格内容的插槽名称
  },
]);
const getList = async (params) => {
  let keyword = {
    contact_man: params.contact_man,
    contact_phone: params.contact_phone,
    order_status: params.order_status,
    create_time: params.create_time
  };
  keyword = JSON.stringify(keyword)
  let option = {
    page: params.pageNum,
    limit: params.pageSize,
    bstr: userinfo.value.businesstr,
    keyword
  };
  let data = {}
  await store.dispatch('orderList', option).then(res => {
    data = {
      data: res.data.list,
      total: res.data.cnt,
    }
  });
  return data;
  // businesstr
}
</script>

<style scoped>
.main {
  width: calc(100% - 40px);
  height: 100%;
  max-height: 800px;
  padding: 20px;
  border-radius: 10px;
}

.info-item {
  width: calc(100% - 40px);
  padding: 20px;
  margin-bottom: 20px;
  background: #f7f7f750;
  border-radius: 10px;
}

.info-tit {
  font-size: 22px;
  margin-bottom: 20px;
}

.item-info {
  width: 45%;
  margin-right: 2.5%;
  padding: 15px 0 0;
  display: inline-block;
}

.item-info-blcok {
  width: 100%;
  padding: 15px 0 0;
  display: block;
}

.item-info-lable {
  color: #9d9d9d;
  display: inline-block;
  margin-right: 10px;
}

.item-info-centent {
  color: #333333;
  display: inline-block;
}
</style>