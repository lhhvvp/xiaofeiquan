<template>
	<view class="comment">
		<view class="comment-item">
			<my-comment :dataVal="comment"></my-comment>
		</view>
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<my-empty :empty="empty" v-if="empty.show"></my-empty>

	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	export default {
		data() {
			return {
				comment: [],
				page: 1,
				loadding: 'more',
				loadding_lock: false,
				empty: {
					show: false,
					id: 3
				},
				mid:'',
			};
		},

		onLoad(options) {
			if(options.mid){
				this.mid = options.mid;
			}
			this.init();
		},
		onReachBottom() {
			this.init();
		},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			init() {
				if (this.loadding_lock) {
					//加载锁，防止无限请求接口
					return false;
				}
				this.$api
					.httpRequest(
						`/ticket/getCommentList`, {
							page: this.page,
							page_size: 12,
							user_id: !!this.mid == true ? '' : this.uerInfo.uid,
							mid:this.mid,
						},
						'GET'

					)
					.then(res => {
						if (res.code == 0) {
							let data = res.data;
							if (this.page == 1 && data.length == 0) {
								// 如果没有数据则显示为空
								this.$set(this.empty, 'show', true);
								this.loadding_lock = true;
								return false;
							}
							this.comment = [...this.comment, ...data];

							if (data.length != 12) {
								//判断是否有下一页
								this.loadding = 'no-more';
								this.loadding_lock = true;
								return false;
							}

							if (data.length == 0) {
								//判断是否有下一页
								this.loadding = 'no-more';
								this.loadding_lock = true;
								return false;
							}
							this.page++;
							this.loadding = 'loading';
						}
					});
			}
		}
	};
</script>

<style lang="scss">


</style>