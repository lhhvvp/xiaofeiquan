<template>
	<view>
		<uni-file-picker
			v-model="filePathsList"
			:readonly="readonly"
			:imageStyles="imageStyles"
			:file-mediatype="type"
			:limit="limit"
			:disabled="disabled"
			fileMediatype="image"
			mode="grid"
			@select="select"
			@delete="deletes"
			@success="success"
		/>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	name: 'my-file',
	props: {
		limit: {
			type: Number,
			default: 1
		},
		disabled: {
			type: Boolean,
			default: false
		},

		imageStyles: {
			type: Object,
			default: function() {
				return {};
			}
		},
		readonly: {
			type: Boolean,
			default: false
		},
		type: {
			type: String,
			default: 'image'
			// image/video/all
		},
		imageValue: {
			type: Array,
			default: function() {
				return [];
			}
		}
	},
	data() {
		return {
			filePathsList: this.imageValue
		};
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		select(res) {
			uni.showLoading({
				title: '上传中..'
			});
			res.tempFilePaths.forEach(item => {
				this.uploadImg(item);
			});
		},
		deletes(err) {
			const num = this.filePathsList.findIndex(v => v.url === err.tempFilePath);
			this.filePathsList.splice(num, 1);
			this.$emit('deleteFile', err);
		},
		async uploadImg(tempFilePaths) {
			let that = this;
			uni.uploadFile({
				url: `${that.$api.baseUrl}/upload/index`, //仅为示例，非真实的接口地址
				filePath: tempFilePaths,
				name: 'file',
				header: {
					Token: that.uerInfo.token,
					Userid: that.uerInfo.uid
				},
				success: uploadFileRes => {
					let data = JSON.parse(uploadFileRes.data);
					let img = data.url;
					let imgPath = that.$api.urli + data.url;
					that.filePathsList.push({ url: imgPath, imgPath:img });
					that.$emit('updateFile', that.filePathsList);
					uni.hideLoading();
					that.$api.msg(uploadFileRes.msg);
				}
			});
		},
		success(e) {
			console.log(e);
		}
	}
};
</script>

<style></style>
