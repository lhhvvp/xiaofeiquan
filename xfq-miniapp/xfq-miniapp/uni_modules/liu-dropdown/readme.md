# liu-dropdown适用于uni-app项目的下拉筛选、条件筛选菜单组件
### 本组件目前兼容微信小程序、H5
### 本组件是仿美团下拉筛选、条件筛选菜单，有4种筛选模式，支持单选、多选、主题色配置，使用相当简单
# --- 扫码预览、关注我们 ---

## 扫码关注公众号，查看更多插件信息，预览插件效果！ 

![](https://uni.ckapi.pro/uniapp/publicize.png)

### 使用示例
``` 
<template>
	<view>
		<liu-dropdown :menuList="menuList" :dataObj="dataObj" @change="change"></liu-dropdown>
	</view>
</template>
<script>
	export default {
		data() {
			return {
				//菜单信息
				menuList: [{
					id: 1,
					name: '全部美食',
					isMultiple: true, //是否多选
					showType: 1 //下拉框类型(1、2、3、4)
				}, {
					id: 2,
					name: '附近',
					isMultiple: false, //是否多选
					showType: 2 //下拉框类型(1、2、3、4)
				}, {
					id: 3,
					name: '智能排序',
					isMultiple: false, //是否多选
					showType: 3 //下拉框类型(1、2、3、4)
				}, {
					id: 4,
					name: '筛选',
					isMultiple: true, //是否多选
					showType: 4 //下拉框类型(1、2、3、4)
				}],
				//下拉框数据源
				dataObj: {
					//类型1数据结构
					itemList1: [{
						id: 1,
						name: '热门',
						childs: [{
							id: 1,
							name: '烤肉'
						}, {
							id: 2,
							name: '西北菜'
						}, {
							id: 3,
							name: '川湘菜'
						}]
					}, {
						id: 2,
						name: '火锅',
						childs: [{
							id: 1,
							name: '全部火锅'
						}, {
							id: 2,
							name: '川渝火锅'
						}, {
							id: 3,
							name: '串串香'
						}]
					}],
					//类型2数据结构
					itemList2: [{
						id: 1,
						name: '商圈',
						childs: [{
							id: 1,
							name: '大润发'
						}, {
							id: 2,
							name: '火车站'
						}, {
							id: 3,
							name: '金牛街'
						}]
					}, {
						id: 2,
						name: '商场',
						childs: [{
							id: 1,
							name: '北京华联'
						}, {
							id: 2,
							name: '国芳百货'
						}, {
							id: 3,
							name: '欣大'
						}]
					}],
					//类型3数据结构
					itemList3: [{
						id: 1,
						name: '智能排序'
					}, {
						id: 2,
						name: '距离优先'
					}, {
						id: 3,
						name: '好评优先'
					}, {
						id: 4,
						name: '销量优先'
					}],
					//类型4数据结构
					itemList4: [{
						id: 1,
						name: '用餐人数',
						childs: [{
							id: 1,
							name: '单人餐'
						}, {
							id: 2,
							name: '双人餐'
						}, {
							id: 3,
							name: '3～4人餐'
						}]
					}, {
						id: 2,
						name: '餐厅品质',
						childs: [{
							id: 1,
							name: '高分餐厅'
						}, {
							id: 2,
							name: '连锁餐厅'
						}, {
							id: 3,
							name: '金冠好店'
						}]
					}]
				},
			};
		},
		methods: {
			//所选择的信息
			change(e) {
				console.log('当前点击的菜单:' + JSON.stringify(e.chooseMenu))
				console.log('所有选择的条件:' + JSON.stringify(e.chooseInfo))
			}
		},
	};
</script>
```

### 属性说明
| 名称                         | 类型            | 默认值                 | 描述             |
| ----------------------------|--------------- | ---------------------- | ---------------|
| menuList                    | Array          | []                     | 菜单数据源
| dataObj                     | Object         |                        | 下拉框数据源
| top                         | Number         | 0                      | 菜单到顶部距离(rpx)
| themeColor                  | String         | #FD430E                | 主题色
| radius                      | String         | 12rpx                  | 圆角(rpx、px、%)
| isMask                      | Boolean        | true                   | 是否点击阴影关闭
| @change                     | Function       |                        | 所有选择的信息回调事件

### 菜单数据源说明
``` 
menuList: [{
	id: 1, //菜单id
	name: '全部美食', //菜单名称
	isMultiple: true, //是否多选
	showType: 1 //下拉框类型(1、2、3、4)
}]
``` 
### 下拉框类型说明
| 类型          | 描述                       |
| -------------| ---------------------------|
| 1            | 左右两列联动列表布局(如预览图一“全部美食”)    |
| 2            | 左右两列联动通讯录布局(如预览图二“附近”)  |
| 3            | 从上到下列表布局(如预览图三“智能排序”)    |
| 4            | 从上到下二级列表布局(如预览图四“智能筛选”) |
