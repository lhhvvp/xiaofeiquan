#uniapp通用滑动验证组件

原理   
图片上放两个块滑动对比区间

作者   
`zmm2113@qq.com`

版本  
`1.0.0`
> 兼容性支持：安卓、苹果、H5、微信小程序（其他平台未测试理论上支持）。

一、使用示例
```
<template>
	<view>
		<button type="default" @click="sliderVerify()">显示滑动验证</button>
		<zmm-slider-verify ref="sliderVerify" @success="successHandle" @error="errorHandle" @close="closeHandle"></zmm-slider-verify>
	</view>
</template>

<script>
	export default {
		methods: {
			//显示验证组件
			sliderVerify(){
				this.$refs['sliderVerify'].show()
			},
			//验证通过回调
			successHandle(e) {
				console.log(e)
			},
			//验证失败回调
			errorHandle(e) {
				console.log(e)
			},
			//组件关闭回调
			closeHandle(e){
				console.log(e)
			}
		}
	}
</script>
```  
二、配置(注意配置数据类型)

配置|数据类型|默认参数|说明
-|-|-|-
title|String|"滑动校验"|顶部标题
tips|String|"请将左侧透明滑块拖进白色框内"|默认提醒文字
slideSize|Number|50|滑块大小
slideColor|String|"rgba(0,0,0,0.4)"|滑块颜色
maskColor|String|"rgba(0,0,0,0.4)"|遮罩层颜色
verifyImg|String|""|背景图片可自定义自己的图片
wrapColor|String|"#ffffff"|主体背景色
between|Number|10|校验正负差值区间像素

三、事件

配置|数据类型|说明
-|-|-
@success|function|验证通过事件
@error|function|验证失败回调
@close|function|组件关闭回调
this.$refs['sliderVerify'].show()|function|显示组件
this.$refs['sliderVerify'].hide()|function|隐藏组件