<template>
  <div class="header" v-if="userinfo">
    <div class="header-left"><h1 style="font-size: 30px;color: #ffffff">{{ userinfo.m_nickname }}-窗口售票系统</h1></div>
    <div class="header-right">
      <div class="user-info">
        <span>{{ userinfo.username }}</span>
        <span class="header-img">
            <el-avatar :size="30" :src="circleUrl"/>
          </span>
      </div>
      <div class="login-out" @click="loginOut">
        <el-icon style="margin-right: 5px">
          <el-icon><SwitchButton /></el-icon>
        </el-icon>
        退出
      </div>
    </div>
  </div>
</template>

<script setup>
const circleUrl = 'https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png';
import {useStore} from "vuex"
import {ElMessageBox} from "element-plus"
import router from "@/router";

const store = useStore();
let userinfo = store.state.UserInfo;

const loginOut = () => {
  ElMessageBox.confirm(
      '您确定要退出登录吗？',
      '提示',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
      }
  )
      .then(() => {
        store.dispatch('loginOut').then(res=>{
          if(res){
            router.push({path: '/login'});
          }
        });
      })
      .catch(() => {

      })
}

</script>

<style scoped>
.header {
  width: 100%;
  height: 80px;
  background: var(--el-menu-active-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-info {
  display: flex;
  align-items: center;
  color: #ffffff;

}

.header-img {
  margin-left: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-right {
  display: flex;
  align-items: center;
}

.login-out {
  margin-left: 20px;
  border-left: 2px solid #7ebeff;
  padding-left: 20px;
  font-size: 16px;
  color: #ffffff;
  display: flex;
  align-items: center;

}
</style>