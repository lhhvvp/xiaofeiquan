<template>
  <div class="main-body">
    <div class="main">
      <el-row>
        <el-col :span="12">
          <div class="item1" @click="navto('/index')">
            <img src="../../assets/icon1.png">
            <text>自助售票</text>
          </div>
        </el-col>
        <el-col :span="12">
          <div class="item2" @click="navto('/writeOrder')">
            <img src="../../assets/icon2.png">
            <text>自助取票</text>
          </div>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup>
import router from "@/router";

function navto(url) {
  router.push({path: url});
}
</script>
<style>
.main-body {
  width: 100%;
  height: 100vh;
  background-image: url('../../assets/bg.gif');
}
body{
  background-image: url('../../assets/bg1.gif');
}
</style>
<style scoped>

.main {
  position: absolute;
  min-width: 1400px;
  left: calc(50% - 700px);
  bottom: 0px;
  height: 360px;
  z-index: 99;
}

.item1, .item2 {
  border-radius: 15px;
  height: 260px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.item1 text, .item2 text {
  color: #ffffff;
  font-weight: bold;
  font-size: 50px;
  margin-left: 40px;
}

.item1 img, .item2 img {
  width: 120px;
  height: 120px;
}

.item1 {
  background: rgb(11, 88, 214);
  background: linear-gradient(335deg, rgba(11, 88, 214, 1) 0%, rgba(78, 207, 255, 1) 100%);
  margin-right: 100px;
}

.item2 {
  background: rgb(106, 11, 214);
  background: linear-gradient(335deg, rgba(106, 11, 214, 1) 0%, rgba(194, 99, 254, 1) 100%);
  margin-left: 100px;
}

</style>