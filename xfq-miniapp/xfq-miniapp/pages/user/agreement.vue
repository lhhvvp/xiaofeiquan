<template>
	<view class="view-box"> 
		<rich-text :nodes="content"></rich-text>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import {replaceContent} from "@/common/common.js"
export default {
	data() {
		return {
			content: ''
		};
	},
	onLoad(o) {
		
		uni.setNavigationBarTitle({
			title: o.title
		});

		this.init(o.title);
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		init(title) {
			this.$api.httpRequest('/index/system',{},"GET").then(res => {
				
				if (res.code == 0) {
					if(title == '服务协议'){
						this.content = replaceContent(res.data.service);
					}else{
						this.content = replaceContent(res.data.policy);
					}
				} else {
					this.$api.msg(`数据加载失败！`);
					setTimeout(function() {
						uni.switchTab({
							url: '/pages/news/news'
						});
					}, 2000);
				}
			});
		}
	}
};
</script>

<style lang="scss">
	.view-box{
		padding: 30upx;
	}
</style>
