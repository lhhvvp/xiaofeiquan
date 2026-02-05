<template>
  <div style="height: 100vh;width: 100%;">
    <div class="back">
      <el-icon>
        <ArrowLeftBold/>
      </el-icon>
      <text style="margin-left: 10px" @click="navtoback()">返回首页</text>
    </div>
    <div class="main">
      <div class="main-left backdrop-filter">
        <div class="left-top">
          <div class="title">票务种类</div>
          <div class="main-box ">

            <div class="item" v-for="(item,index) in list" :key="index">
              <div class="item-title">{{ item.ticket.title }}</div>

              <div class="item-number" style="margin-bottom: 8px"><span>数量：</span>
                <el-input-number min="0" size="small" v-model="item.number" :max="item.stock" style="width: 90px"
                                 @change="change($event,item)"></el-input-number>
              </div>
              <div class="item-number">
                <span style="font-size: 10px">门票单价：{{ item.price }}元</span>
              </div>
            </div>

            <div v-if="list.length === 0"
                 style="width: 100%;text-align: center;color: var(--el-text-color-secondary);font-size:14px;padding: 50px 0">
              当天暂无门票
            </div>

          </div>
        </div>
        <div class="left-bottom">
          <div class="title">票务订单信息</div>
          <div class="main-box">
            <el-table :data="FormData" sum-text="合计" empty-text="请选择门票" show-summary stripe border
                      style="width: 100%" height="220">
              <el-table-column prop="id" label="编号" width="80"/>
              <el-table-column prop="title" label="门票名称"/>
              <el-table-column sortable prop="price" label="零售价(元)" width="180"/>
              <el-table-column sortable prop="number" label="购买数量（张）" width="180"/>
              <el-table-column sortable prop="totalPrice" label="单项小计（元）" width="180"/>
              <el-table-column label="姓名">
                <template #default="scope">
                  <text v-for="(val) in scope.row.userall">{{ val.name || '' }},</text>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="180">
                <template #default="scope">
                  <el-button
                      size="small"
                      type="primary"
                      :loading="false"
                      @click="handUserOrder(scope.$index, scope.row)"
                  >填写游客信息
                  </el-button>
                </template>
              </el-table-column>
            </el-table>

            <el-dialog
                v-model="IsUserInfo"
                :title="UserInfoAll.title + ' - 填写用户信息'"
                width="700"
                :before-close="handleClose"
            >
              <div style="width: 100%">
                <div style="display: flex;align-items: center" v-for="(item,index) in UserInfoAll.userall">
                  <div style="width: 100px">游客信息{{ index + 1 }}：</div>
                  <el-form-item label="姓名" style="width: 150px;margin: 15px">
                    <el-input v-model="item.name" placeholder="姓名"/>
                  </el-form-item>

                  <el-form-item label="身份证号" style="width: calc(100% - 350px);margin: 15px">
                    <el-input v-model="item.idcard" placeholder="身份证号"/>
                  </el-form-item>

                  <el-button style="margin-left: 10px;" type="primary" @click="ReadIDCard(index + 1 ,UserInfoAll.id)"
                             v-loading.fullscreen.lock="fullscreenLoading">识别身份证
                  </el-button>

                </div>
              </div>
              <template #footer>
      <span class="dialog-footer">
        <el-button @click="IsUserInfo = false">取消</el-button>
        <el-button type="primary" @click="IsUserInfo = false">
          确定
        </el-button>
      </span>
              </template>
            </el-dialog>

          </div>
        </div>

        <div class="left-footer">
          <div class="title">联系人信息</div>
          <div class="main-box">
            <el-form :model="form" label-width="120px"
                     style="background: #f7f7f7;display: flex; align-items:center;padding: 15px"
                     ref="ruleForms"
                     :rules="rules">
              <el-form-item label="姓名" style="width: 33%;margin: 15px 0;" class="w-50 m-2" prop="name">
                <el-input v-model="form.name" placeholder="请输入姓名" style="font-size: 16px"/>
              </el-form-item>

              <el-form-item label="身份证号" style="width: 33%;margin: 15px 0" prop="idcard">
                <el-input v-model="form.idcard" placeholder="请输入身份证号" style="font-size: 16px"/>
              </el-form-item>

              <el-form-item label="手机号" style="width: 33%;margin: 15px 0" prop="phone">
                <el-input v-model="form.phone" placeholder="请输入手机号" style="font-size: 16px"/>
              </el-form-item>
              <el-button style="margin-left: 10px;" type="primary" @click="ReadIDCard()"
                         v-loading.fullscreen.lock="fullscreenLoading">识别身份证
              </el-button>
            </el-form>
          </div>
        </div>
      </div>
      <div class="main-right">
        <div class="right-top backdrop-filter">
          <div class="title">游玩日期</div>
          <div class="main-box">
            <div class="demo-date-picker" style="padding-top: 20px">
              <el-config-provider :locale="zhCn">
                <el-date-picker
                    v-model="value"
                    type="date"
                    placeholder="请选择时间"
                    format="YYYY/MM/DD"
                    value-format="YYYY-MM-DD"
                    style="width: 100%;height: 60px;font-size: 24px;"
                    @change="dateChange"
                    :disabled-date="disabledDate"
                >
                  <template #default="cell">
                    <div class="cell" :class="{ current: cell.isCurrent }">
                      <span class="text">{{ cell.text }}</span>
                      <span v-if="isHoliday(cell)" class="holiday"/>
                    </div>
                  </template>
                </el-date-picker>
              </el-config-provider>
            </div>
          </div>
        </div>
        <div class="right-cent backdrop-filter">
          <div class="title">票价总计</div>
          <div class="total-price">
            <span style="font-size: 16px">￥</span>
            {{ totalPrice.toFixed(2) }}
          </div>
        </div>
        <div class="right-bottom backdrop-filter">
          <div class="title">支付</div>
          <div class="main-box">
            <button class="item pay" v-loading.fullscreen.lock="fullscreenLoading" @click="submit()"
                    style="border: none;">
              立即付款
            </button>
          </div>
        </div>
      </div>
    </div>

    <el-dialog
        v-model="dialogVisible"
        title="支付订单"
        width="30%"
        :before-close="handleClose"
    >
      <div class="pay-img" v-if="!trade_no_pay.status"
           style="display: flex;align-items: center;justify-content: center;flex-direction: column">
        <img :src="trade_no_pay.url"
             style="width: 200px;height: 200px;">
        <span style="display: block;padding-top: 20px">扫码支付</span>
      </div>
      <div class="pay-success" v-if="trade_no_pay.status"
           style="display: flex;align-items: center;justify-content: center;flex-direction: column">
        <img src="../../assets/pay.png" style="width: 200px;height: 200px;">
        <el-button type="primary" @click="dialogVisibleFun" style="margin-top: 50px">支付成功 返回首页</el-button>
      </div>

    </el-dialog>

  </div>
</template>

<script setup>
import {onMounted, ref} from "vue";
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import {useStore} from "vuex";
import {ElMessage} from "element-plus";
import router from "@/router";

const store = useStore();   //vue 缓存
const value = ref('1970-01-01');  //接口的时间
const holidays = [];      //可以选的时间
const isHoliday = ({dayjs}) => {
  return holidays.includes(dayjs.format('YYYY-MM-DD'))
};

function navtoback() {
  router.push({path: '/main'});
}

function disabledDate(v) {
  return v.getTime() < (new Date().getTime() - 86400000);
}

const UserInfoAll = ref({});

const IsUserInfo = ref(false);

function handUserOrder(index, row) {
  UserInfoAll.value = row;
  if (FormData.value[index].userall === undefined) {
    UserInfoAll.value.userall = [];
    for (let i = 0; i < row.number; i++) {
      UserInfoAll.value.userall.push({
        name: null,
        idcard: null,
      });
    }
  } else {
    let number = row.number - FormData.value[index].userall.length;
    if (number > 0) {
      for (let i = 0; i < number; i++) {
        UserInfoAll.value.userall.push({
          name: null,
          idcard: null,
        });
      }
    } else {
      // unshift
      for (let i = 0; i < Math.abs(number); i++) {
        UserInfoAll.value.userall.pop();
      }
    }
    UserInfoAll.value.userall = FormData.value[index].userall;
  }
  ;
  IsUserInfo.value = true;
};


// 识别身份证
const valuation = ref(null);  //赋值操作
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
        document.querySelector("#message").innerHTML += `<p>接收数据: ${res.data}</p>`
        return;
      }
      fullscreenLoading.value = false;
      var alldata = res.data.split("|");
      if (alldata.length >= 17) {
        if (valuation.value === 'form') {
          // FormData.value
          eval(valuation.value).value.name = alldata[1]
          eval(valuation.value).value.idcard = alldata[6]
        } else {

          for (let key = 0; key < FormData.value.length; key++) {

            if (FormData.value[key].id === valuation.value.id) {

              let is_repetition = true;
              FormData.value[key].userall.forEach(val => {
                if (val.idcard === alldata[6]) {
                  is_repetition = false;
                }
              });
              if (!is_repetition) {
                //重复跳出;
                ElMessage({
                  message: '请务重复添加!',
                  type: 'warning',
                });
                return false;
              }
              FormData.value[key].userall[valuation.value.index].name = alldata[1];
              FormData.value[key].userall[valuation.value.index].idcard = alldata[6];
            }
          }
          ;
        }

      } else {
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
};

/**
 *   关闭连接
 *
 */
function closeConnect() {
  websockets.value.close();
}

function ReadIDCard(vals, id) {

  if (!isconncet.value) {
    ElMessage({
      message: '未连接服务',
      type: 'warning',
    })
  } else {
    if (!vals) {
      valuation.value = 'form';
    } else {
      let index = vals - 1;
      valuation.value = {index, id};
    }
    let paramTimeOut = 10000;
    paramTimeOut = "timeout=" + paramTimeOut;
    var parameterAll = paramTimeOut;
    let val = "03?" + parameterAll;
    websockets.value.send(val);
    fullscreenLoading.value = true;
  }
}


onMounted(() => {
  OcrRecognition();
});


//可以选的时间
const list = ref([]);//门票数据

const FromPayType = ref(null);

const dialogVisible = ref(false);
const handleClose = (done) => {
  dialogVisibleFun();
  done()
}

function dialogVisibleFun() {
  clearTimeout(timer.value);
  dialogVisible.value = false;
}

const ruleForms = ref(null);

const rules = {
  name: [
    {required: true, message: '请输入姓名', trigger: 'blur'},
  ],
  phone: [
    {required: true, message: '请输入手机号', trigger: 'blur'},
  ],
  idcard: [
    {required: true, message: '请输入身份证号', trigger: 'blur'},
  ]
}

const paySbmit = function (val) {
  ruleForms.value.validate((valid) => {
    if (valid) {
      if (FormData.value.length === 0) {
        ElMessage({
          message: '请选择门票',
          type: 'warning',
        });

        return false;

      } else {
        FromPayType.value = val;

      }

    }
  })
};

const form = ref({
  name: null,
  phone: null,
  idcard: null,
});

const loading = ref(false);//加载loading
const trade_no_pay = ref({
  status: false,
  trade_no: null,
  url: null

});
const submit = () => {

  let paytype = 'weixin';
  dialogVisible.value = false;
  let ticket_date = value.value;

  let data = FormData.value.map(item => {
    let userall = null;
    if (item.userall) {
      userall = item.userall.map(item => {
        return {tourist_fullname: item.name, tourist_cert_id: item.idcard, tourist_cert_type: 1};
      })
    }
    return {
      uuno: item.uuno,
      number: item.number,
      price: Number(item.price) * Number(item.number),
      tourist: userall
    }
  });
  let err = data.map(item => {
    if (item.tourist) {
      if (item.number !== item.tourist.length) {
        return true;
      }
    } else {
      return true
    }
    return false;
  });
  if (err[0]) {
    ElMessage({
      message: '请完善游客信息' || 'error', type: 'error', showClose: true, duration: 3 * 1000
    });
    return false;
  }
  data = JSON.stringify(data);
  loading.value = true;
  ruleForms.value.validate((valid) => {
    if (valid) {
      fullscreenLoading.value = true;
      let userinfo = store.state.UserInfo;
      let {name: contact_man, phone: contact_phone, idcard: contact_certno} = form.value;
      let contact = {contact_certno, contact_phone, contact_man};
      contact = JSON.stringify(contact);
      let option = {contact, data, paytype, ticket_date, no: userinfo.no,};
      store.dispatch('paySubmit', option)
          .then((res) => {
            if (res.code === 1) {
              loading.value = false;
              ElMessage({
                message: res.msg || 'error', type: 'error', showClose: true, duration: 3 * 1000
              });
              fullscreenLoading.value = false;
            } else {
              loading.value = false;
              ElMessage({
                message: res.msg || 'success', type: 'success', showClose: true, duration: 3 * 1000
              });
              trade_no_pay.value.url = res.data.url;
              trade_no_pay.value.trade_no = res.data.trade_no;
              checkOrderStatus();
              dialogVisible.value = true;
              fullscreenLoading.value = false;
            }

          })
          .catch(() => {
            loading.value = false
          })
    }
  })

};

const timer = ref(null);

async function checkOrderStatus() {
  let data = {
    trade_no: trade_no_pay.value.trade_no
  }
  store.dispatch('checkOrderStatus', data)
      .then((res) => {
        if (res.code === 0) {
          if (res.data === 0) {
            timer.value = setTimeout(() => {
              checkOrderStatus();
              clearTimeout(timer.value);
            }, 10000)
            //0是未支付
          } else {
            //1是支付
            clearTimeout(timer.value);
            trade_no_pay.value.status = true;
          }
        }
      })
      .catch(() => {
      })


  // await checkOrderStatus();
}

const init = (date = '') => {
  let userinfo = store.state.UserInfo;
  let data = {
    channel: 'casual',
    oneday: date,
  }
  if (userinfo) {
    data.bstr = userinfo.businesstr;
    data.no = userinfo.no;
  }
  ;
  store.dispatch('getTicketPirce', data)
      .then((res) => {
        list.value = [];
        if (res.code === 0) {
          if (res.data) {
            value.value = res.data[0].date;
            res.data.forEach(item => {
              item.number = 0;
            });
          }
          list.value = res.data;
        }
      })
      .catch(() => {
      })
};
init();

const FormDataSet = new Set([]);    //选择的set数据id
const FormData = ref([]);     //选择的数据

const proceeds = ref(0); //收款

const totalPrice = ref(0);

const dateChange = function (e) {
  // 日期选择器
  init(e);
  FormDataSet.clear();
  FormData.value = [];
}
const change = function (e, val) {
  if (FormDataSet.has(val.ticket.id)) {
    //有的时候
    FormData.value.forEach(item => {
      if (item.uuno === val.ticket.id) {
        item.number = e;
        item.totalPrice = Number(e) * Number(val.price);
      }
    });
    if (UserInfoAll.value.userall !== undefined) {
      let number = e - UserInfoAll.value.userall.length;
      if (number < 0) {
        for (let i = 0; i < Math.abs(number); i++) {
          UserInfoAll.value.userall.pop();
        }
      }
    }

    if (e === 0) {
      FormData.value.forEach((item, index) => {
        if (item.uuno === val.ticket.id) {
          FormData.value.splice(index, 1);
        }
      });
      FormDataSet.delete(val.ticket.id);
    }
  } else {
    //没有;
    FormDataSet.add(val.ticket.id);
    FormData.value.push({
      id: val.ticket.id,
      number: e,
      price: val.price,
      uuno: val.ticket.id,
      title: val.ticket.title,
      totalPrice: Number(e) * Number(val.price),
    });
  }
  ;
  totalPrice.value = 0;
  FormData.value.forEach(item => {
    totalPrice.value += item.totalPrice;
  })

}

</script>
<style>
.demo-date-picker .el-input__wrapper {
  border-radius: 60px;
  overflow: hidden;
  background-color: #f7f7f7;
}

.el-form-item__label {
  font-size: 16px;
}
</style>
<style scoped>

.main {
  width: calc(100% - 40px);
  left: 20px;
  height: calc(100% - 120px);
  bottom: 20px;
  position: absolute;
  display: flex;
  justify-content: space-between;
}

.main .main-left {
  padding: 20px;
  width: calc(75% - 60px);
  height: calc(100% - 42px);
  overflow: hidden;
}

.main .main-right {
  width: 25%;
  height: 100%;
}

.main .main-right {
  border-radius: 10px;
}

.main .main-left {
  border-radius: 10px;
  border: 1px solid #409eff50;
}

.main .main-right .right-top {
  margin-bottom: 20px;
  height: 20%;
  padding: 20px;
  width: calc(100% - 42px);
  border-radius: 10px;
  border: 1px solid #409eff50;

}

.main .main-right .right-cent {
  border-radius: 10px;
  padding: 20px;
  height: 40%;
  margin-bottom: 20px;
  width: calc(100% - 42px);
  border: 1px solid #409eff50;
}

.main .main-right .right-cent .total-price {
  width: 100%;
  height: 70%;
  text-align: center;
  font-size: 45px;
  font-weight: bold;
  color: var(--el-color-danger);
  display: flex;
  align-items: center;
  justify-content: center;
}

.main .main-right .right-bottom {
  padding: 20px;
  height: calc(40% - 166px);
  width: calc(100% - 42px);
  border-radius: 10px;
  border: 1px solid #409eff50;
}

.main .main-right .right-bottom .main-box {
  width: 100%;
  display: flex;
  justify-content: space-around;
  margin-top: 40px;
}

.main .main-right .right-bottom .main-box .item {
  width: 100%;
  text-align: center;
}

.main .main-right .right-bottom .main-box .item .img {
  width: 50px;
  height: 50px;
  margin: 0 auto 10px;
  background-size: cover;
}


.main .main-left .left-top {
  width: 100%;
  margin-bottom: 2%;
  border-bottom: 1px solid #f7f7f7;
}

.main .main-left .left-top .main-box {
  overflow: auto;
  height: 220px;
  margin-bottom: 30px;
}

.main .main-left .left-top .main-box .item {
  width: calc(220px - 20px);
  padding: 10px;
  height: 80px;
  background: #f7f7f7;
  margin-right: 10px;
  margin-bottom: 10px;
  border-radius: 10px;
  display: inline-block;
}

.item-title {
  font-weight: bold;
  margin-bottom: 10px;
  overflow : hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
}

.item-number {
  font-size: 14px;
  color: #838383;
  display: flex;
  align-items: center;
}

.main .main-left .left-bottom {
  width: 100%;
  height: 36%;
  margin-bottom: 20px;
}

.title {
  font-size: 18px;
  font-weight: bold;
  color: var(--el-menu-hover-text-color);
  margin-bottom: 30px;
  position: relative;
  padding-left: 15px;
}

.title:before {
  position: absolute;
  content: "";
  width: 6px;
  height: 80%;
  top: 11%;
  left: 0;
  border-radius: 10px;
  background: var(--el-menu-hover-text-color);
}

.cell {
  height: 30px;
  padding: 3px 0;
  box-sizing: border-box;
}

.cell .text {
  width: 24px;
  height: 24px;
  display: block;
  margin: 0 auto;
  line-height: 24px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  border-radius: 50%;
}

.cell.current .text {
  background: #626aef;
  color: #fff;
}

.cell .holiday {
  position: absolute;
  width: 6px;
  height: 6px;
  background: var(--el-color-danger);
  border-radius: 50%;
  bottom: 0px;
  left: 50%;
  transform: translateX(-50%);
}

.back {
  width: 96%;
  height: 100px;
  line-height: 100px;
  font-size: 26px;
  margin: auto;
  color: #ffffff;
  font-weight: bold;
  display: flex;
  align-items: center;
}

.pay {
  background: #224399;
  color: #ffffff;
  padding: 16px 0;
  border-radius: 50px;
}

</style>