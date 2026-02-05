<template>
  <div class="body">
    <div class="titlogin"></div>
    <div class="main">
      <div class="form-box">
        <el-form
            :label-position="labelPosition"
            label-width="100px"
            :model="formLabelAlign"
            :rules="rules"
            ref="ruleForms"
            :inline="true"
        >

          <el-form-item  prop="username" style="margin-right: 10px;">
              <el-input v-model="formLabelAlign.username" style="height: 40px" placeholder="请输入账号">
                <template #prepend>
                  <el-button disabled :icon="User" />
                </template>
            </el-input>
          </el-form-item>

          <el-form-item  prop="password" style="margin-right: 10px">
            <el-input v-model="formLabelAlign.password" style="height: 40px" show-password placeholder="请输入密码">
              <template #prepend>
                <el-button disabled :icon="Lock" />
              </template>
            </el-input>
          </el-form-item>

          <el-form-item  prop="code">
            <el-input v-model="formLabelAlign.code" style="height: 40px;width: 160px" placeholder="请输入验证码">
              <template #prepend>
                <el-button disabled :icon="Calendar" />
              </template>
            </el-input>
          </el-form-item>

          <el-form-item>
            <img :src=codeUrl @click="reloadCode" style="height:40px;margin-left: 10px;margin-right: 10px;width: 110px">
          </el-form-item>

          <el-button :loading="loading" type="primary" @click.native.prevent="onSubmit()"
                     style="height: 40px;width: 120px;">登录
          </el-button>


        </el-form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { User,Lock,Calendar } from '@element-plus/icons-vue'
import {reactive, ref} from 'vue'
import {useStore} from 'vuex'
import axios from "axios";
import {url} from "@/settings"
import {ElMessage} from "element-plus";
import router from "@/router";

const codeUrl = ref(null);

const store = useStore();
const loading = ref(false);//加载loading
let ruleForms = ref(null);//验证数据
const labelPosition = ref('right')
const formLabelAlign = reactive({
  username: null,
  password: null,
  code: null,
  pubkey: null
})

const rules = {
  username: [
    {required: true, message: '请输入账号', trigger: 'blur'},
  ],
  password: [
    {required: true, message: '请输入密码', trigger: 'blur'},
  ],
  code: [
    {required: true, message: '请输入验证码', trigger: 'blur'},
  ]
}

const onSubmit = async () => {
  formLabelAlign.pubkey = 11111111;
  ruleForms.value.validate((valid) => {
    if (valid) {
      loading.value = true;
      store.dispatch('login', formLabelAlign)
          .then((res) => {
            if (res.code === 1) {
              reloadCode();
              loading.value = false;
              ElMessage({
                message: res.msg || 'error', type: 'error', showClose: true, duration: 3 * 1000
              });
            } else {
              loading.value = false;
              ElMessage({
                message: res.msg || 'success', type: 'success', showClose: true, duration: 3 * 1000
              });
              router.push({path: '/index'});
            }

          })
          .catch(() => {
            loading.value = false
          })
      //触发成功验证表单，调用接口；
    } else {
    }
  });
}
const reloadCode = () => {
  let headers = ["Content-Type': 'multipart/form-data'"];
  axios({
    method: "get",
    url: "/window/index/captcha",
    responseType: "arraybuffer",
    headers: headers,
  }).then((response) => {
    return (
        'data:image/png;base64,' +
        btoa(new Uint8Array(response.data).reduce((data, byte) => data + String.fromCharCode(byte), ''))
    )
  }).then((data) => {
    codeUrl.value = data
  })
};
reloadCode();

</script>

<style scoped>
.body {
  background-image: url('../../assets/login-bg.jpg');
  position: fixed;
  min-width: 1400px;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

.titlogin {
  background-image: url('../../assets/logo-bg1.png');
  width: 100%;
  height: 200px;
  background-repeat: no-repeat;
  background-position: center;
  margin-bottom: 70px;
}

.main {
  width: 100%;
  left: 0;
}

.form-box {
  width: 928px;
  margin: auto;
  background-color: rgba(255, 255, 255, 0.3);
  padding: 30px 50px;
  border-radius: 10px;
}

.el-form-item {
  margin-bottom: 0;
  margin-right: 0;
}

.el-form-item__label {
}
/deep/ .el-input-group__prepend {
  background-color: var(--el-input-bg-color,var(--el-fill-color-blank));
}
</style>