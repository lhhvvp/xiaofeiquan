<template>
  <div class="common-layout">
    <el-container>
      <el-header style="height: 80px" class="box-shadow">
        <use-header></use-header>
      </el-header>
      <el-container style="margin-top: 20px">
        <el-aside width="200px" class="box-shadow">
          <div class="nav-item" v-for="(item,index) in navList" :key="index" :class="[index === on ? 'on':'']"
               @click="navClick(index)">
            <div class="img" :style="'background-image:url('+item.url+')'"></div>
            <div class="tit">{{ item.title }}</div>
          </div>
        </el-aside>
        <el-container>
          <el-main>
            <router-view></router-view>
          </el-main>
        </el-container>
      </el-container>
    </el-container>
  </div>
</template>

<script setup>
import {createApp, reactive, ref} from "vue"
import useHeader from "@/components/useHeader";
import router from "@/router";
import iocn1 from '../../assets/icon/001.png'
import iocn2 from '../../assets/icon/002.png'
import iocn3 from '../../assets/icon/003.png'
import iocn4 from '../../assets/icon/004.png'

let app = createApp({});
app.component("use-header", useHeader);
const on = ref(0);
const navClick = function (e) {
  on.value = e;
  switch (e) {
    case 0:
      router.push({path: '/index'});
      break;
    case 1:
      router.push({path: '/ticket'});
      break;
    case 2:
      router.push({path: '/order'});
      break;
    case 3:
      router.push({path: '/statistics'});
      break;
  }
};
const fullPath = () => {
  let routers = router.currentRoute.value.fullPath;
  switch (routers) {
    case '/index':
      on.value = 0;
      break;
    case '/ticket':
      on.value = 1;
      break;
    case '/order':
      on.value = 2;
      break;
    case '/statistics':
      on.value = 3;
      break;
  }
};
fullPath();

const navList = reactive([{
  title: "售票",
  value: 0,
  url: iocn1,
}, {
  title: "取票",
  value: 1,
  url: iocn2,
}, {
  title: "订单",
  value: 2,
  url: iocn3,
}, {
  title: "报表",
  value: 3,
  url: iocn4,
}]);

</script>

<style scoped>
.el-header {
  background: var(--el-menu-active-color);

}
.el-main, .el-aside {
  background-color: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  border-radius: 10px;
}

.el-main {
  margin: 0 20px;
  padding: 0;
  background: none;
  overflow: hidden;
  box-shadow: none;
}

.el-aside {
  margin-left: 20px;
  border: 1px solid #409eff50;
  display: flex;
  height: 800px;
  flex-direction: column;
  justify-content: center;
}

.nav-item {
  width: 100%;
  text-align: center;
  padding: 30px 0;
}

.nav-item .img {
  height: 80px;
  width: 80px;
  background-size: cover;
  margin: 0 auto 10px;
}

.nav-item .tit {
  color: #999999;
  font-size: 20px;
}

.main {
  width: 100%;
}

.on {
  background: #409eff10;
  width: calc(100% - 4px);
  border-left: 4px solid  var(--el-menu-active-color);
}

.on .tit {
  color: var(--el-menu-active-color);
}
</style>