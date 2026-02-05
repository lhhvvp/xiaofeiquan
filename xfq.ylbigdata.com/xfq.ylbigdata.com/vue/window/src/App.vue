<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
  document.title = '榆林市文旅数字化营销系统 | 窗口售票系统';
</script>
<style>
  body{
    margin: 0;
    padding: 0;
    background: #f7f7f7;
  }
  .backdrop-filter{
    background-color: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
  }
  .box-shadow{
    box-shadow: 12.5px 12.5px 10px rgba(0, 0, 0, 0),
    100px 100px 80px rgba(0, 0, 0, 0);
  }
</style>