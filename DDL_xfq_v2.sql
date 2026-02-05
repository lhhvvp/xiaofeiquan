/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_accounting   */
/******************************************/
CREATE TABLE `tp_accounting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '运营审核   1=通过  0=待上传资料  2=不通过 3=待审核',
  `write_off_ids` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '核销记录ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '备注信息',
  `sum_coupon_price` decimal(10,0) unsigned NOT NULL DEFAULT '0' COMMENT '核算金额',
  `writeoff_total` int unsigned NOT NULL DEFAULT '0' COMMENT '核算记录数',
  `card_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '收款账号',
  `card_deposit` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `cart_number` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '卡号',
  `sup_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '监管单位审核  1=通过  0=待审核  2=不通过',
  `back_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '银行打款        1=已付款  0=待付款  2=拒绝付款',
  `sup_card` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '监管单位上传附件地址',
  `back_card` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '银行打款凭据地址',
  `class_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '商家分类',
  `area` int unsigned NOT NULL DEFAULT '0' COMMENT '所属区域',
  `nickname` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `no` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '结算单号',
  `data_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '申请材料明细文件地址',
  `data_detail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '申请资料明细url',
  `tour_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '文旅审核',
  `tags` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '结算类型  1=团申请  0=散客申请',
  PRIMARY KEY (`id`),
  KEY `mid_idx` (`mid`),
  KEY `tags_idx` (`tags`)
) ENGINE=InnoDB AUTO_INCREMENT=709 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='消费券-散客-核算'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_admin   */
/******************************************/
CREATE TABLE `tp_admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_login_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '上次登录IP',
  `loginnum` int NOT NULL COMMENT '登录次数',
  `err_num` int NOT NULL COMMENT '错误次数',
  `lock_time` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '锁定时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-管理员-信息'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_admin_log   */
/******************************************/
CREATE TABLE `tp_admin_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `admin_id` int NOT NULL DEFAULT '0' COMMENT '管理员',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作页面	',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日志内容',
  `ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User-Agent',
  PRIMARY KEY (`id`),
  KEY `admin_id_idx` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=232247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-管理员-日志'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_area   */
/******************************************/
CREATE TABLE `tp_area` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pid` int DEFAULT '0' COMMENT '父级id',
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '区划编码',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '区划名称',
  `level` tinyint(1) DEFAULT NULL COMMENT '级次id 0:省/自治区/直辖市 1:市级 2:县级',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间	',
  PRIMARY KEY (`id`),
  KEY `code` (`code`(191)),
  KEY `level` (`level`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=3658 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='基础-数据-区域代码v1'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_area_code   */
/******************************************/
CREATE TABLE `tp_area_code` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `code` int NOT NULL,
  `province` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `city` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `district` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `detail` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `update_time` int DEFAULT '0',
  `create_time` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6759 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-数据-区域代码v2'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_article   */
/******************************************/
CREATE TABLE `tp_article` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT NULL COMMENT '状态',
  `cate_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '栏目',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者',
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '摘要',
  `image` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片',
  `images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '图片集',
  `download` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件下载',
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'TAG',
  `hits` int unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `template` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '跳转地址',
  `view_auth` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '阅读权限',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-文章'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_audit_record   */
/******************************************/
CREATE TABLE `tp_audit_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `step` int unsigned NOT NULL DEFAULT '0' COMMENT '审核阶段',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核人',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '角色',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '审核备注',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '文件凭据',
  `aid` int unsigned NOT NULL DEFAULT '0' COMMENT '核算记录ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='消费券-散客-核算审核记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_auth_group   */
/******************************************/
CREATE TABLE `tp_auth_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '角色组',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '权限',
  `col_rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容权限组',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-角色组管理'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_auth_group_access   */
/******************************************/
CREATE TABLE `tp_auth_group_access` (
  `uid` mediumint unsigned NOT NULL COMMENT '用户ID',
  `group_id` mediumint unsigned NOT NULL COMMENT '分组ID',
  `create_time` int unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned DEFAULT '0' COMMENT '更新时间	',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-用户组明细表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_auth_rule   */
/******************************************/
CREATE TABLE `tp_auth_rule` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '控制器/方法',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限名称',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '菜单状态',
  `condition` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort` mediumint NOT NULL DEFAULT '0' COMMENT '排序',
  `auth_open` tinyint DEFAULT '1' COMMENT '验证权限',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图标名称',
  `create_time` int unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `param` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '参数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=650 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-规则表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_base_paydata   */
/******************************************/
CREATE TABLE `tp_base_paydata` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '交易ID',
  `order_no` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单ID',
  `openid` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '用户ID',
  `body` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '描述',
  `money` char(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '价格',
  `model` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `payip` char(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '支付IP',
  `appid` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '小程序ID',
  `mch_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '商户号',
  `result_code` char(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '业务结果',
  `trade_type` char(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '交易类型:JSAPI、NATIVE、APP',
  `total_fee` int DEFAULT NULL COMMENT '订单金额',
  `transaction_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '微信支付订单号',
  `time_end` char(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '支付方式名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待支付 1 支付成功 2支付失败',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`openid`),
  KEY `trade_sn` (`order_no`,`money`,`status`,`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-支付-交易数据'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_base_payment   */
/******************************************/
CREATE TABLE `tp_base_payment` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '支付类型',
  `wechat_appid` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '绑定小程序',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '支付名称',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付图标',
  `wechat_mch_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信商户密钥',
  `wechat_mch_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信商户号',
  `sort` bigint DEFAULT '0' COMMENT '排序权重',
  `status` tinyint(1) DEFAULT '1' COMMENT '支付状态(1使用,0禁用)',
  `deleted` tinyint(1) DEFAULT '0' COMMENT '删除状态',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_base_user_payment_status` (`status`),
  KEY `idx_base_user_payment_type` (`type`),
  KEY `idx_base_user_payment_code` (`wechat_appid`),
  KEY `idx_base_user_payment_deleted` (`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='基础-支付-配置'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_base_refunds   */
/******************************************/
CREATE TABLE `tp_base_refunds` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '交易ID',
  `appid` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '小程序ID',
  `mch_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户号',
  `order_no` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单ID',
  `out_refund_no` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `total_fee` char(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单金额 ',
  `refund_fee` char(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '申请退款金额',
  `refund_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款原因',
  `model` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '模型',
  `refund_ip` char(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '支付IP',
  `return_code` char(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '返回状态码',
  `result_code` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '业务结果',
  `transaction_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信支付订单号',
  `refund_id` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信退款单号',
  `settlement_refund_fee` char(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款金额',
  `refund_status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款状态',
  `success_time` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款成功时间',
  `refund_recv_accout` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款入账账户',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待退款 1 退款成功 2退款失败',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `trade_sn` (`order_no`,`total_fee`,`status`,`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-退款-交易数据'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_cache   */
/******************************************/
CREATE TABLE `tp_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` char(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '缓存KEY值',
  `name` char(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `module` char(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '模块名称',
  `model` char(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `action` char(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '方法名',
  `system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统',
  `create_time` int NOT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ckey` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='系统-数据-缓存对列表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_cate   */
/******************************************/
CREATE TABLE `tp_cate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` int unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `cate_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目名称',
  `en_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '英文名称',
  `cate_folder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目目录',
  `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT '上级栏目',
  `module_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '所属模块',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外部链接',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目图片',
  `ico_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'ICO图片',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '简介',
  `template_list` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '列表模板',
  `template_show` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '详情模版',
  `page_size` int unsigned NOT NULL DEFAULT '0' COMMENT '分页条数',
  `is_menu` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '导航状态',
  `is_next` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '跳转下级',
  `is_blank` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '新窗口打开',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-内容-栏目管理'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_collection   */
/******************************************/
CREATE TABLE `tp_collection` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=646 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='用户-数据-收藏商家'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_config   */
/******************************************/
CREATE TABLE `tp_config` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '配置的key键名',
  `value` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '配置的val值',
  `inc_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '配置分组',
  `desc` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-配置表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_class   */
/******************************************/
CREATE TABLE `tp_coupon_class` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类标题',
  `class_icon` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '消费券分类图标',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='消费券-基础-分类'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_condition_details   */
/******************************************/
CREATE TABLE `tp_coupon_condition_details` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键值',
  `class_id` tinyint(1) NOT NULL COMMENT '商户分类',
  `mark_num` int NOT NULL COMMENT '打卡次数',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='消费券-规则详情表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_issue   */
/******************************************/
CREATE TABLE `tp_coupon_issue` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuno` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '消费券唯一标识编号',
  `cid` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '所属分类',
  `coupon_title` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券名称',
  `coupon_icon` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券图标',
  `limit_time` tinyint(1) NOT NULL COMMENT '是否限时  1=限时  0=不限时',
  `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券领取开启时间',
  `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券领取结束时间',
  `total_count` int NOT NULL DEFAULT '0' COMMENT '消费券领取数量',
  `remain_count` int NOT NULL DEFAULT '0' COMMENT '消费券剩余领取数量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 正常 0 未开启 -1 已无效  2=已领完',
  `is_del` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `coupon_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '兑换的消费券面值',
  `is_threshold` tinyint(1) NOT NULL COMMENT '是否有门槛  1=是  0=否',
  `use_min_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最低消费多少金额可用消费券',
  `is_permanent` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '有效期设置 1永久有效  2期限内有效  3指定天数内有效',
  `coupon_time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始时段',
  `coupon_time_end` int NOT NULL COMMENT '有效期结束时段',
  `class_id` int unsigned NOT NULL DEFAULT '0' COMMENT '栏目类型 1=通用 2=门票 3=线路 4=商品',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '消费券类型 1=通用 2=品类券 3=商品券',
  `product_id` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '所属商品id 根据消费类型选择不同的商品ID',
  `category_id` int NOT NULL DEFAULT '0' COMMENT '分类id 根据消费类型选择不同的分类ID',
  `receive_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 手动领取，2 新人券，3赠送券，4会员券',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '备注=使用细则',
  `last_time` int NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  `tag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否在首页展示  1=是  0=否',
  `is_limit_total` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否限量   1=是  0=否',
  `limit_total` tinyint(1) NOT NULL DEFAULT '0' COMMENT '单人限制领取数量',
  `use_store` tinyint(1) NOT NULL DEFAULT '1' COMMENT '使用门店  1=通用  2=景区  3=旅行社 4=剧院 5=影院',
  `use_stroe_id` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '门店ID  如果use_store=1 则等于0   否则为门店ID  如果use_store不等于1 则等于0时为具体某个分类的全部商家',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `sale_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买价格',
  `is_get` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否可领取',
  `tips` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '不可领取的提示语',
  `day` int unsigned NOT NULL DEFAULT '0' COMMENT '天数',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `is_rollback` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否回滚数据  1=需要回滚数据  0=不需要回滚  2=已经回滚数据',
  `rollback_num` int unsigned NOT NULL DEFAULT '0' COMMENT '回滚数量',
  `coupon_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '券类型 1=通用  2=散客 3=团体',
  `provide_count` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券领取数量',
  `rollback_num_extend` int unsigned NOT NULL DEFAULT '0' COMMENT '散客领取记录过期回滚数量',
  `use_type` tinyint(1) DEFAULT '2' COMMENT '核销方式 1=线上 2=线下',
  `use_type_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '领取规则描述',
  `receive_crowd` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '领取人群 1=全部  2=本地 3=外地',
  `write_off_seller` int unsigned NOT NULL DEFAULT '0' COMMENT '线上核销时,选择的核销商户id',
  PRIMARY KEY (`id`),
  KEY `start_time` (`start_time`,`end_time`),
  KEY `use_stroe_id` (`use_stroe_id`),
  KEY `receive_type` (`receive_type`),
  KEY `uuno` (`uuno`),
  KEY `is_del` (`is_del`),
  KEY `remain_count` (`remain_count`),
  KEY `coupon_time` (`coupon_time_start`),
  KEY `coupon_type` (`coupon_type`),
  KEY `cid` (`cid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1069 DEFAULT CHARSET=utf8mb3 COMMENT='消费券-基础-内容表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_issue_user   */
/******************************************/
CREATE TABLE `tp_coupon_issue_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '领取时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '领取人',
  `issue_coupon_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `coupon_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券标题',
  `coupon_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费券面额',
  `use_min_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最低消费多少可使用优惠券',
  `coupon_create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券创建时间',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券开启时间',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券结束时间',
  `time_use` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券使用时间',
  `status` int unsigned NOT NULL DEFAULT '0' COMMENT '状态（0：未使用，1：已使用, 2:已过期）',
  `is_fail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '是否有效',
  `is_limit_total` tinyint(1) NOT NULL COMMENT '是否限制领取  1=是 0=否',
  `issue_coupon_class_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券种类',
  `enstr_salt` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '数据加密串',
  `qrcode_url` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '领取二维码图片地址',
  `code_time_create` int unsigned NOT NULL DEFAULT '0' COMMENT '加密串生成时间',
  `code_time_expire` int unsigned NOT NULL DEFAULT '0' COMMENT '加密串过期时间',
  `ips` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '领取IP',
  `longitude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '经度',
  `latitude` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '纬度',
  `expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券过期时间',
  `is_rollback` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否回滚数据 1=默认  2=待会滚 3=已回滚',
  `rollback_numbers` tinyint(1) NOT NULL DEFAULT '0' COMMENT '回滚执行次数',
  `delivery_user` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '收货姓名',
  `delivery_phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '收货号码',
  `delivery_address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '收货地址',
  `tracking_number` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '快递单号',
  `delivery_input_time` int unsigned NOT NULL DEFAULT '0' COMMENT '填写时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_2` (`uid`,`issue_coupon_id`),
  KEY `uid` (`uid`),
  KEY `delivery_address` (`delivery_address`),
  KEY `idx_is_rollback_status_index` (`is_rollback`,`status`),
  KEY `idx_uid_issuecouponid_issuecouponclassid` (`uid`,`issue_coupon_id`,`issue_coupon_class_id`),
  KEY `issue_coupon_id` (`issue_coupon_id`),
  KEY `idx_uid_status_issuecouponid` (`uid`,`status`,`issue_coupon_id`),
  KEY `idx_issuecouponclassid` (`issue_coupon_class_id`),
  KEY `idx_issuecouponid_status_expiretime` (`issue_coupon_id`,`status`,`expire_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1088718 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='消费券-用户-领取记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_order   */
/******************************************/
CREATE TABLE `tp_coupon_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `openid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '小程序openid',
  `uuid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '下单用户编号uuid',
  `mch_id` int NOT NULL DEFAULT '0' COMMENT '订单所属商户',
  `order_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号 = 内部订单编号',
  `order_out_no` varchar(33) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单编号 = 外部调用编号',
  `origin_price` decimal(10,2) NOT NULL COMMENT '订单原来的价格 = 数量*单价',
  `amount_price` decimal(20,2) DEFAULT '0.00' COMMENT '消费券统计金额 = 实际要去支付的金额',
  `payment_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '实际渠道编号 = 支付配置主键ID',
  `payment_trade` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '实际支付单号 = 微信支付生成的单号',
  `payment_status` tinyint(1) DEFAULT '0' COMMENT '实际支付状态  1=已支付  0=未支付 2=已退款',
  `payment_image` varchar(999) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '支付凭证图片',
  `payment_remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '支付结果描述',
  `payment_datetime` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0' COMMENT '支付到账时间 = 微信返回',
  `number_count` int DEFAULT '0' COMMENT '订单数量',
  `order_remark` varchar(999) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '订单用户备注',
  `cancel_status` tinyint(1) DEFAULT '0' COMMENT '订单取消状态  1=是 0=否',
  `cancel_remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '订单取消描述',
  `cancel_datetime` int DEFAULT '0' COMMENT '订单取消时间',
  `deleted_status` tinyint(1) DEFAULT '0' COMMENT '订单删除状态 1=已删  0=未删',
  `deleted_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '订单删除描述',
  `deleted_datetime` int DEFAULT '0' COMMENT '订单删除时间',
  `is_refund` int DEFAULT NULL COMMENT '订单退款状态  1=退款中 0= 表示未退款 2=已退款',
  `status` tinyint(1) DEFAULT '1' COMMENT '订单流程状态(0已取消,,1已下单=待支付,3支付中,4已支付,5已完成)',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '订单创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '订单更新时间',
  `issue_coupon_user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '领取记录ID串',
  `payment_data_id` int NOT NULL DEFAULT '0' COMMENT '支付记录交易数据ID',
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_orderno` (`order_no`),
  KEY `idx_shop_order_payment_status` (`payment_status`),
  KEY `idx_shop_order_deleted` (`deleted_status`),
  KEY `idx_shop_order_status` (`status`),
  KEY `idx_shop_order_cancel_status` (`cancel_status`),
  KEY `idx_shop_order_mid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='消费券-订单-内容'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_order_item   */
/******************************************/
CREATE TABLE `tp_coupon_order_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0' COMMENT '消费券用户编号',
  `order_no` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '消费券订单单号',
  `coupon_uuno` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '消费券编号',
  `coupon_cid` int NOT NULL DEFAULT '0' COMMENT '消费券所属分类ID',
  `coupon_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '消费券名称',
  `coupon_price` decimal(10,2) DEFAULT '0.00' COMMENT '消费券面额',
  `coupon_icon` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '封面图片',
  `coupon_sale_price` decimal(20,2) DEFAULT '0.00' COMMENT '市场单价 = 消费券销售价格',
  `total_market` decimal(20,2) DEFAULT '0.00' COMMENT '市场总价',
  `price_selling` decimal(20,2) DEFAULT '0.00' COMMENT '销售单价 = 消费券销售价格',
  `total_selling` decimal(20,2) DEFAULT '0.00' COMMENT '销售总价',
  `stock_sales` bigint DEFAULT '1' COMMENT '消费券包含数量',
  `discount_amount` decimal(20,2) DEFAULT '0.00' COMMENT '优惠金额',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态(1已使用,0未使用)',
  `deleted` tinyint(1) DEFAULT '0' COMMENT '删除状态(0未删,1已删)',
  `update_time` int DEFAULT '0' COMMENT '订单创建时间',
  `create_time` int DEFAULT '0' COMMENT '订单创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_item_order_no` (`order_no`),
  KEY `idx_shop_order_item_status` (`status`),
  KEY `uuid` (`uuid`),
  KEY `idx_shop_order_item_deleted` (`deleted`),
  KEY `coupon_cid` (`coupon_cid`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='消费券-订单-消费券'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_coupon_receive_condition   */
/******************************************/
CREATE TABLE `tp_coupon_receive_condition` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键值',
  `coupon_id` int NOT NULL COMMENT '优惠券ID',
  `condition_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则ID',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='消费券-领取规则表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_data_summary   */
/******************************************/
CREATE TABLE `tp_data_summary` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `class_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '消费券分类',
  `class_cid` int NOT NULL COMMENT '消费券分类id',
  `coupon_title` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '消费券标题',
  `coupon_id` int NOT NULL COMMENT '消费券ID',
  `coupon_price` decimal(10,2) NOT NULL COMMENT '消费券面额',
  `issue_total` int NOT NULL COMMENT '发行数量',
  `issue_price` decimal(10,2) NOT NULL COMMENT '发行金额',
  `receive_total` int NOT NULL COMMENT '领取数量',
  `writeoff_total` int NOT NULL COMMENT '核销数量',
  `writeoff_price` decimal(10,2) NOT NULL COMMENT '核销金额',
  `writeoff_ratio` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '核销比率',
  `overstep` int NOT NULL COMMENT '超出多发数量',
  `issue_date` int NOT NULL COMMENT '发行日期',
  `issue_number` tinyint(1) NOT NULL COMMENT '第几期',
  `rollback_num` int NOT NULL COMMENT '库存回滚',
  `rollback_num_extend` int NOT NULL COMMENT '散客领取回滚',
  `tags` tinyint(1) NOT NULL COMMENT '类型 1=散客发行统计 2=团体发行统计',
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `tags` (`tags`)
) ENGINE=InnoDB AUTO_INCREMENT=590 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='消费券-数据汇总'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_dictionary   */
/******************************************/
CREATE TABLE `tp_dictionary` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '编号',
  `dict_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典标签',
  `dict_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典键值',
  `dict_type` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典类型',
  `remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `sort` int unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-字典'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_dictionary_type   */
/******************************************/
CREATE TABLE `tp_dictionary_type` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '编号',
  `dict_name` char(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典名称',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-字典类型'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_examine_record   */
/******************************************/
CREATE TABLE `tp_examine_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `step` int unsigned NOT NULL DEFAULT '0' COMMENT '审核阶段',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核人',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '角色',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '审核备注',
  `sid` int unsigned NOT NULL DEFAULT '0' COMMENT '根据审核类型确定对应类型的ID',
  `tags` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '审核类型 1=商户申请审核   2=旅行团申请审核',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '审核凭据',
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=4125 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='后台-操作-审核商户记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_feedback   */
/******************************************/
CREATE TABLE `tp_feedback` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '投诉时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `create_ip` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '投诉人',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci COMMENT '投诉、建议内容',
  `images` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '图片信息',
  `mobile` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='用户-信息-投诉反馈'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_field   */
/******************************************/
CREATE TABLE `tp_field` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `module_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '所属模块',
  `field` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段名',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段别名',
  `tips` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提示信息',
  `required` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否必填',
  `minlength` int unsigned NOT NULL DEFAULT '0' COMMENT '最小长度',
  `maxlength` int unsigned NOT NULL DEFAULT '0' COMMENT '最大长度',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `data_source` int unsigned NOT NULL DEFAULT '0' COMMENT '数据源',
  `relation_model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型关联',
  `relation_field` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '展示字段',
  `dict_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典类型',
  `is_add` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可插入',
  `is_edit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可编辑',
  `is_list` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可列表展示',
  `is_search` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可查询',
  `is_sort` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否可排序',
  `search_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '查询类型',
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `sort` int unsigned NOT NULL DEFAULT '0',
  `remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `setup` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '其他设置',
  `group_id` int NOT NULL DEFAULT '0' COMMENT '字段分组',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1495 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-模型字段'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_field_group   */
/******************************************/
CREATE TABLE `tp_field_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `module_id` int NOT NULL DEFAULT '0' COMMENT '所属模块',
  `group_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `sort` int unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-字段分组'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_flow   */
/******************************************/
CREATE TABLE `tp_flow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核流名称',
  `check_type` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '流类型',
  `flow_cate` int unsigned NOT NULL DEFAULT '0' COMMENT '应用审核类型',
  `department_ids` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '应用于角色',
  `copy_uids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '抄送人IDS',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '审核说明',
  `flow_list` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '流程数据序列化',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态 1启用，0禁用',
  `delete_time` int NOT NULL DEFAULT '0' COMMENT '删除时间',
  `delete_user_id` int NOT NULL DEFAULT '0' COMMENT '删除人ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='审批流程表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_flow_record   */
/******************************************/
CREATE TABLE `tp_flow_record` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action_id` int NOT NULL DEFAULT '0' COMMENT '审核内容ID',
  `step_id` int NOT NULL DEFAULT '0' COMMENT '审核步骤ID',
  `check_user_id` int NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `check_time` int NOT NULL COMMENT '审核时间',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0发起审核1审核通过2审核拒绝3撤销',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '审核类型:1普通审批',
  `content` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核意见',
  `is_invalid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审批失效（1标记为无效）',
  `delete_time` int NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='审核记录表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_flow_step   */
/******************************************/
CREATE TABLE `tp_flow_step` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action_id` int NOT NULL COMMENT '审批内容ID',
  `flow_type` tinyint NOT NULL DEFAULT '0' COMMENT '0自由指定,1当前角色负责人，2上一级角色负责人，3指定用户（单用户），4指定用户（多用户）',
  `flow_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '流程名称',
  `flow_uids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审批人ID串1,2,3...',
  `sort` tinyint NOT NULL DEFAULT '0' COMMENT '排序ID',
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '审批类型:1普通审批',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='审批步骤表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_flow_type   */
/******************************************/
CREATE TABLE `tp_flow_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核标识',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核名称',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图标',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='审批类型'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_global_exception_log   */
/******************************************/
CREATE TABLE `tp_global_exception_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '所属应用',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '异常消息',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '异常文件',
  `line` int NOT NULL COMMENT '异常行号',
  `code` int NOT NULL COMMENT '异常代码',
  `trace` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '异常堆栈跟踪',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '异常发生时间',
  `update_time` int NOT NULL DEFAULT '0',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求地址',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14537 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='全局-异常-日志'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_guest   */
/******************************************/
CREATE TABLE `tp_guest` (
  `id` mediumint NOT NULL AUTO_INCREMENT COMMENT '编号',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `update_time` int unsigned DEFAULT '0' COMMENT '更新时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `openid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '小程序openid',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `headimgurl` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信头像',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `idcard` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `nickname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信昵称',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行社ID',
  `mid_sub` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行社分支机构',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mobile` (`mobile`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=1779 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-游客信息'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_guide   */
/******************************************/
CREATE TABLE `tp_guide` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '电话',
  `idcard` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `certificates` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '导游证件',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行社商户ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户表ID',
  PRIMARY KEY (`id`),
  KEY `indexs_tid_mid` (`tid`),
  KEY `index_mids` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=956 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-导游信息'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_line   */
/******************************************/
CREATE TABLE `tp_line` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类',
  `title` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `sellpoint` char(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '卖点',
  `lineday` int NOT NULL DEFAULT '0' COMMENT '线路天数',
  `linenight` int NOT NULL DEFAULT '0' COMMENT '多少晚',
  `sellprice` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格',
  `startcity` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '出发城市',
  `overcity` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '目的城市',
  `linebefore` int NOT NULL DEFAULT '0' COMMENT '提前报名天数',
  `price` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '报价',
  `price_date` int unsigned NOT NULL DEFAULT '0' COMMENT '最新价格时间',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `images` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '封面图片',
  `photo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '相册',
  `photo_count` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '相册图片数量',
  `video` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短视频',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '行程安排',
  `notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '注意事项',
  `feeinclude` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '费用包含',
  `feenotinclude` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '费用不包含',
  `constraints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '出游人限制',
  `contract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '合同条款',
  `access_count` int unsigned NOT NULL DEFAULT '0' COMMENT '访问次数',
  `sales_count` int NOT NULL DEFAULT '0' COMMENT '销售数量',
  `recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '首页推荐（0否, 1是）',
  `status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '运营审核 0=禁用 1=通过 2审核中 3审核失败 4修改资料',
  `tourism_status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT '文旅审核 1=通过 2审核中 3不通过',
  `delete_time` int unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0 未删除, 大于0则是删除时间）',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `tags` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '线路景点',
  `flag` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '所属 1=运营创建   2=旅行社创建',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行社商户',
  PRIMARY KEY (`id`),
  KEY `is_shelves` (`status`),
  KEY `photo_count` (`photo_count`),
  KEY `access_count` (`access_count`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='线路-信息-基表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_line_category   */
/******************************************/
CREATE TABLE `tp_line_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `icon` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '图标',
  `name` char(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `describe` char(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `bg_color` char(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT 'css背景色值',
  `sort` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否启用（0否，1是）',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `is_enable` (`status`),
  KEY `pid` (`pid`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='线路-信息-分类'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_line_record   */
/******************************************/
CREATE TABLE `tp_line_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` int unsigned NOT NULL DEFAULT '0' COMMENT '审核状态',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核人',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '角色',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '审核备注',
  `line_id` int unsigned NOT NULL DEFAULT '0' COMMENT '线路ID',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '审核凭据',
  `step` int unsigned NOT NULL DEFAULT '0' COMMENT '审核阶段',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='线路-信息-旅投审核'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_logistics_information   */
/******************************************/
CREATE TABLE `tp_logistics_information` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '主键值',
  `coupon_issue_user_id` int NOT NULL COMMENT '消费卷领取记录id',
  `tracking_number` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '快递单号',
  `code` int NOT NULL COMMENT '快递响应码',
  `msg` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '响应文言',
  `data` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '快递投递数据',
  `delivery_status` int NOT NULL DEFAULT '0' COMMENT '0：快递收件(揽件)1.在途中 2.正在派件 3.已签收 4.派送失败 5.疑难件 6.退件签收进度',
  `issign` int NOT NULL DEFAULT '0' COMMENT '签收:1是/0否',
  `exp_type` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '快递公司en',
  `exp_name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '快递公司名称',
  `courier` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '快递员',
  `courierPhone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '快递员电话',
  `update_time` int NOT NULL COMMENT '更新时间',
  `create_time` int NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2895 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='物流快递表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_merchant_verification_points   */
/******************************************/
CREATE TABLE `tp_merchant_verification_points` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '软删除',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '位置名称',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '位置负责人',
  `mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '负责人电话',
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '具体位置',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户名称',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='商户-核验-点'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_merchant_verifier   */
/******************************************/
CREATE TABLE `tp_merchant_verifier` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int DEFAULT NULL,
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '电话',
  `openid` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '' COMMENT 'openid',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户名称',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `trust_agreement` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '核验人诚信协议',
  `idcard_front_back` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '身份证正反面',
  `type` enum('coupon','ticket','appt') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '核销类型',
  `account` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '登陆账户',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '登陆密码',
  `salt` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '密码盐',
  `token` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT 'token',
  `logintime` int unsigned NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `loginerror` int unsigned NOT NULL DEFAULT '0' COMMENT '登陆错误次数',
  `token_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `loginlock_time` int unsigned NOT NULL DEFAULT '0' COMMENT '登陆锁定时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=449 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='商户-信息-核验人'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_merchant_verifier_approve   */
/******************************************/
CREATE TABLE `tp_merchant_verifier_approve` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '审核记录ID',
  `mv_id` int unsigned NOT NULL DEFAULT '0' COMMENT '核销员ID',
  `approve` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '审核状态',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '审核备注',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '审核附件',
  `ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '创建IP',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='审核表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_module   */
/******************************************/
CREATE TABLE `tp_module` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `module_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模块名称',
  `table_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表名称',
  `model_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `table_comment` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表注释',
  `table_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表类型',
  `pk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'id' COMMENT '主键',
  `list_fields` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台列表页可调用字段,默认为*,仅用作前台CMS调用时使用',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注',
  `sort` smallint unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_sort` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '排序字段',
  `is_status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态字段',
  `top_button` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'add,edit,del,export' COMMENT '顶部按钮',
  `right_button` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'edit,delete' COMMENT '右侧按钮',
  `is_single` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '单页模式',
  `show_all` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '查看全部',
  `add_param` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '添加参数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-模块配置'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_notice   */
/******************************************/
CREATE TABLE `tp_notice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `hits` int unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  `cate_id` int DEFAULT NULL,
  `keywords` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '描述',
  `template` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '模板',
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '跳转地址',
  `title` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-数据-通知公告'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller   */
/******************************************/
CREATE TABLE `tp_seller` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) DEFAULT '4' COMMENT '状态 0=禁用 1=通过 2审核中 3审核失败 4修改资料',
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录账号',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录密码',
  `login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `image` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'LOGO图标',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_login_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '上次登录IP',
  `loginnum` int NOT NULL DEFAULT '0' COMMENT '登录次数',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系人',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `do_business_time` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业时间',
  `address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商户位置',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '商户描述',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `class_id` tinyint(1) NOT NULL DEFAULT '2' COMMENT '所属分类',
  `cart_number` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '银行卡号',
  `card_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款名称',
  `card_deposit` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `mtype` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '商户类型',
  `credit_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '统一社会信用代码',
  `area` int unsigned NOT NULL DEFAULT '0' COMMENT '所属区域',
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_validated` int unsigned NOT NULL DEFAULT '0' COMMENT '邮箱验证',
  `business_license` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业执照地址',
  `permit_foroperation` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '经营许可证',
  `social_liability_insurance` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT ' 社会责任险',
  `no` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商家编号',
  `idcard_front` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证正面',
  `idcard_back` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证反面',
  `business_license_set` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '营业资质',
  `err_num` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '错误次数',
  `lock_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '锁定时间',
  `verification_scope` int unsigned NOT NULL DEFAULT '0' COMMENT '可核销范围(单位:米)',
  `comment_rate` float(10,0) unsigned NOT NULL DEFAULT '0' COMMENT '评分',
  `comment_num` int unsigned NOT NULL DEFAULT '0' COMMENT '评论总数',
  `appt_open` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否开启预约，1=开启，0=关闭',
  `appt_limit` int unsigned NOT NULL DEFAULT '0' COMMENT '预约人数限制',
  `salt` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '盐值',
  `signpass` varchar(33) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'passwd token',
  `expiry_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `max_num` bigint DEFAULT NULL COMMENT '最大人数[当类型为酒店时不为空)]',
  PRIMARY KEY (`id`),
  UNIQUE KEY `no` (`no`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='商户-信息-基表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller_auth_rule   */
/******************************************/
CREATE TABLE `tp_seller_auth_rule` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '控制器/方法',
  `title` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '权限名称',
  `type` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单状态',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '菜单状态',
  `condition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort` mediumint NOT NULL DEFAULT '0' COMMENT '排序',
  `auth_open` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '验证权限',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图标名称',
  `create_time` int unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `param` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '参数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-数据-规则表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller_child_node   */
/******************************************/
CREATE TABLE `tp_seller_child_node` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) DEFAULT '4' COMMENT '状态 0=禁用 1=通过 2审核中 3审核失败 4修改资料',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录账号',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录密码',
  `login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `nickname` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `image` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'LOGO图标',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `last_login_ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '上次登录IP',
  `loginnum` int NOT NULL DEFAULT '0' COMMENT '登录次数',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '联系人',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `do_business_time` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业时间',
  `address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商户位置',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '商户描述',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `class_id` tinyint(1) NOT NULL DEFAULT '3' COMMENT '所属分类',
  `cart_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '银行卡号',
  `card_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款名称',
  `card_deposit` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `mtype` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '商户类型',
  `credit_code` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '统一社会信用代码',
  `area` int unsigned NOT NULL DEFAULT '0' COMMENT '所属区域',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_validated` int unsigned NOT NULL DEFAULT '0' COMMENT '邮箱验证',
  `business_license` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '营业执照地址',
  `permit_foroperation` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '经营许可证',
  `social_liability_insurance` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT ' 社会责任险',
  `no` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '商家编号',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级商户',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='商户-子商户-基表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller_class   */
/******************************************/
CREATE TABLE `tp_seller_class` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `class_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='商户-信息-分类'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller_mark_qc   */
/******************************************/
CREATE TABLE `tp_seller_mark_qc` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键值',
  `seller_id` int NOT NULL COMMENT '商户ID',
  `day_threshold_value` int NOT NULL COMMENT '每日打卡阈值',
  `qrcode_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '二维码加密串',
  `code_time_expire` int NOT NULL DEFAULT '0' COMMENT '二维码过期时间',
  `qrcode_base` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '二维码的base编码',
  `status` int DEFAULT '0' COMMENT '状态 (0-可用 1-不可用)',
  `update_time` int NOT NULL COMMENT '更新时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `range` int NOT NULL COMMENT '打卡范围',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='商户-打卡二维码表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_seller_mark_qc_user_record   */
/******************************************/
CREATE TABLE `tp_seller_mark_qc_user_record` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '主键值',
  `seller_id` int NOT NULL COMMENT '商户ID',
  `uid` int NOT NULL COMMENT '用户ID',
  `qc_id` int NOT NULL COMMENT '打卡二维码表ID',
  `qrcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '二维码code',
  `mark_location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '打卡位置',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '打卡位置经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '打卡位置纬度',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `class_id` int NOT NULL COMMENT '商户类别ID',
  `coupon_id` int NOT NULL COMMENT '消费卷ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6760 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='商户-用户-二维码打卡-记录表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_slide   */
/******************************************/
CREATE TABLE `tp_slide` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '轮播标题',
  `hits` int unsigned NOT NULL DEFAULT '0' COMMENT '点击次数',
  `tags` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '展示位置',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '内容',
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '跳转地址',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '封面图片',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-广告-轮播图'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_system   */
/******************************************/
CREATE TABLE `tp_system` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站名称',
  `logo` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站LOGO',
  `icp` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备案号',
  `copyright` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '版权信息',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网站地址',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '公司地址',
  `contacts` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系人',
  `tel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `mobile_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号码',
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '传真号码',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱账号',
  `qq` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'QQ',
  `qrcode` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '二维码',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键字',
  `des` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `mobile` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '手机端',
  `code` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '后台验证码',
  `message_code` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '前台验证码',
  `message_send_mail` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否开启领取间隔',
  `template_opening` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '模板修改备份',
  `template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模板目录',
  `html` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Html目录',
  `other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '其他',
  `upload_driver` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '上传驱动',
  `upload_file_size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件限制',
  `upload_file_ext` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件格式',
  `upload_image_size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片限制',
  `upload_image_ext` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片格式',
  `editor` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '编辑器',
  `display_mode` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '运行模式',
  `appid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '小程序AppID',
  `appsecret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'AppSecret',
  `expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `ticket` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '票据',
  `accesstoken` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'AccessToken',
  `app_create_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token创建时间',
  `service` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT ' 服务协议',
  `policy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '隐私政策',
  `act_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '活动规则',
  `screen_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '大屏访问密码',
  `is_open_api` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否开启体验页面',
  `is_safe_ip` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '黑名单IP',
  `is_safe_area` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '黑名单区域',
  `is_effective_start` int unsigned NOT NULL DEFAULT '0' COMMENT '有效时段开始',
  `is_effective_end` int unsigned NOT NULL DEFAULT '0' COMMENT '有效时间结束',
  `is_interval_time` int unsigned NOT NULL DEFAULT '0' COMMENT '领券间隔时间',
  `is_random_number` int unsigned NOT NULL DEFAULT '0' COMMENT '概率',
  `apis_ip_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP定位密钥',
  `is_queue_number` int unsigned NOT NULL DEFAULT '0' COMMENT '排队时间',
  `is_qrcode_number` int unsigned NOT NULL DEFAULT '0' COMMENT '二维码有效时间',
  `is_clock_switch` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否开启打卡',
  `is_random_number_extend` int unsigned NOT NULL DEFAULT '0' COMMENT '随机数返回',
  `app_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '三网认证代码',
  `banned_seller` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '黑名单商户ID',
  `tour_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '旅行团上报开关',
  `tracking_app_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '快递appCode',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='基础-系统-设置'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket   */
/******************************************/
CREATE TABLE `tp_ticket` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属商户',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属票种分类',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '票种名称',
  `cover` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '票种封面图',
  `quota` int unsigned NOT NULL DEFAULT '0' COMMENT '每人每天限购0表示不限',
  `explain_use` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '使用说明',
  `explain_buy` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '购买说明',
  `crossed_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '划线价',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int DEFAULT NULL COMMENT '软删除',
  `quota_order` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '每单限购',
  `rights_num` int unsigned NOT NULL DEFAULT '0' COMMENT '门票权益数（核销次数）',
  `code` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '编号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='门票'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_appt_datetime   */
/******************************************/
CREATE TABLE `tp_ticket_appt_datetime` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属商户',
  `date` date NOT NULL COMMENT '日期',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '开始时间:28800表示:08:00',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '结束时间:36000表示10:00',
  `stock` int unsigned NOT NULL DEFAULT '0' COMMENT '剩余库存',
  `total_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '总库存',
  `use_num` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='预约时间表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_appt_log   */
/******************************************/
CREATE TABLE `tp_ticket_appt_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '预约编号（核销使用）',
  `seller_id` int NOT NULL COMMENT '预约所属商户',
  `user_id` bigint NOT NULL COMMENT '用户ID',
  `date` date DEFAULT NULL,
  `time_start` int DEFAULT NULL COMMENT '预约开始时间',
  `time_end` int DEFAULT NULL COMMENT '预约结束时间',
  `fullname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预约人',
  `idcard` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '预约人身份证号',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '预约联系方式',
  `number` int unsigned NOT NULL DEFAULT '0' COMMENT '预约人数',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态：0未核销,1已核销，2取消预约',
  `writeoff_time` bigint DEFAULT NULL COMMENT '核销时间',
  `writeoff_id` int DEFAULT NULL COMMENT '核销人ID',
  `writeoff_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销人姓名',
  `lat` decimal(10,7) DEFAULT NULL COMMENT '核销纬度',
  `lng` decimal(10,7) DEFAULT NULL COMMENT '经度',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销地址',
  `ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销IP',
  `cancel_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '取消时间',
  `create_time` bigint unsigned DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='预约记录表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_appt_log_tourist   */
/******************************************/
CREATE TABLE `tp_ticket_appt_log_tourist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID编号',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '编号',
  `log_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '预约记录ID',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `date` date DEFAULT NULL COMMENT '预约日期',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '预约开始时间段',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '预约结束时间段',
  `tourist_fullname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '游客姓名',
  `tourist_cert_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '游客证件类型',
  `tourist_cert_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '游客证件号',
  `tourist_mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '游客手机号',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态 0=未核销;1=已核销',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `writeoff_time` bigint NOT NULL COMMENT '核销时间',
  `writeoff_id` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人ID',
  `writeoff_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '核销人姓名',
  `writeoff_lat` decimal(10,7) unsigned DEFAULT NULL COMMENT '核销时纬度',
  `writeoff_lng` decimal(10,7) unsigned DEFAULT NULL COMMENT '核销时经度',
  `writeoff_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '核销时IP',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `seller_id` (`seller_id`,`user_id`,`tourist_fullname`,`tourist_cert_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_appt_rule   */
/******************************************/
CREATE TABLE `tp_ticket_appt_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属商户',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '时段名',
  `time_start` int NOT NULL COMMENT '预约开始时间(单位：秒)',
  `time_end` int NOT NULL COMMENT '预约结束时间(单位：秒)',
  `stock` int NOT NULL COMMENT '当前时间段库存',
  `weeks` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '星期1~7',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='预约时间段'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_appt_write_off   */
/******************************************/
CREATE TABLE `tp_ticket_appt_write_off` (
  `appt_log_id` bigint unsigned NOT NULL COMMENT '预约记录ID',
  `appt_log_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '核销记录编号',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态：0未核销,1已核销',
  `use_time` bigint DEFAULT NULL COMMENT '使用时间',
  `use_man` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销人',
  `use_lat` decimal(10,7) DEFAULT NULL COMMENT '核销纬度',
  `use_lng` decimal(10,7) DEFAULT NULL COMMENT '经度',
  `use_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销地址',
  `use_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销IP',
  PRIMARY KEY (`appt_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_category   */
/******************************************/
CREATE TABLE `tp_ticket_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属商户',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '分类介绍',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `delete_time` int unsigned DEFAULT NULL COMMENT '软删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='门票分类'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_comment   */
/******************************************/
CREATE TABLE `tp_ticket_comment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '评论用户ID',
  `rate` float(3,2) unsigned NOT NULL DEFAULT '5.00' COMMENT '评分 1~5颗星',
  `status` tinyint(1) NOT NULL COMMENT '状态 1显示,0隐藏',
  `ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '来源IP',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '评论内容',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_order   */
/******************************************/
CREATE TABLE `tp_ticket_order` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `openid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '小程序openid',
  `uuid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '下单用户编号uuid',
  `mch_id` int NOT NULL DEFAULT '0' COMMENT '订单所属商户',
  `trade_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单编号 = 内部订单编号',
  `out_trade_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '外部订单编号',
  `channel` enum('online','window','ota_xc','travel','ota_mt','selfservice') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online' COMMENT '来源渠道 online window ota travel',
  `travel_id` int unsigned NOT NULL DEFAULT '0' COMMENT '来源渠道是travel才有值 旅行社ID ',
  `travel_wxapp_qrcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源渠道是travel才有值 旅行社订单小程序码',
  `type` enum('miniapp','mp','wap','app','weixin','alipay','cash','unionpay','ota_xc') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'miniapp' COMMENT '支付渠道',
  `origin_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单原价 = 所有订单详情合计价',
  `amount_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际要去支付的金额=调起微信支付使用的',
  `payment_terminal` tinyint(1) NOT NULL DEFAULT '1' COMMENT '支付配置表主键ID',
  `transaction_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '微信支付订单编号',
  `payment_datetime` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '支付到账时间 = 微信返回',
  `payment_status` tinyint(1) DEFAULT '0' COMMENT '实际支付状态  1=已支付  0=未支付 2=已退款',
  `payment_remark` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付结果描述',
  `payment_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付凭证图片',
  `refund_fee` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `contact_man` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系人',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系人电话',
  `contact_certno` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系人证件号',
  `order_remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单用户备注',
  `cancel_remark` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单取消描述',
  `cancel_datetime` int DEFAULT '0' COMMENT '订单取消时间',
  `order_status` enum('created','paid','used','cancelled','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'created' COMMENT '订单状态 created=已创建未支付 paid=已支付 used=已使用=核销 cancelled=已取消 refunded=已退款(全部退款时修改该状态)',
  `refund_status` enum('not_refunded','partially_refunded','fully_refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'not_refunded' COMMENT '退款状态 not_refunded=未退货 partially_refunded=部分退货 fully_refunded=全部退货',
  `create_lat` double(10,6) DEFAULT NULL COMMENT '创建纬度',
  `create_lng` double(10,6) DEFAULT NULL COMMENT '创建经度',
  `province` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '经纬度转换省份',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '城市',
  `district` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '区域',
  `formatted_address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '格式化地址',
  `create_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '创建地址',
  `create_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '创建IP',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '订单创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '订单更新时间',
  `delete_time` int DEFAULT NULL COMMENT '软删除',
  `pay_id` int NOT NULL DEFAULT '0' COMMENT '支付记录交易数据表ID',
  `writeoff_tourist_num` int unsigned NOT NULL DEFAULT '0' COMMENT '核销游客数量',
  `wirteoff_rights_num` int unsigned NOT NULL DEFAULT '0' COMMENT '核销权益次数（只有多次核销才会有）',
  `settlement_status` enum('unsettled','in_progress','settled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unsettled' COMMENT '结算状态 unsettled = 未结算,in_progress = 结算中,settled = 已结算',
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_trade_no` (`out_trade_no`),
  UNIQUE KEY `trade_no` (`trade_no`),
  KEY `transaction_id` (`transaction_id`),
  KEY `order_status` (`order_status`),
  KEY `refund_status` (`refund_status`),
  KEY `openid` (`openid`),
  KEY `mch_id` (`mch_id`),
  KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=649 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='票务-订单-主表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_order_detail   */
/******************************************/
CREATE TABLE `tp_ticket_order_detail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户UUID',
  `trade_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单内部编号=主表编号',
  `out_trade_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单外部编号',
  `out_refund_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '微信退款订单号',
  `ticket_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '门票编号=自动生成',
  `tourist_fullname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '游客姓名',
  `tourist_cert_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '游客证件类型 1=身份证',
  `tourist_cert_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '游客证件号',
  `tourist_mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '游客手机号',
  `ticket_number` int NOT NULL COMMENT '购票数量',
  `ticket_cate_id` int NOT NULL COMMENT '门票分类ID',
  `ticket_id` int unsigned NOT NULL DEFAULT '0' COMMENT '所属票种ID',
  `ticket_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '门票标题',
  `ticket_date` date NOT NULL COMMENT '门票入园日期',
  `ticket_cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '门票封面图',
  `ticket_price` decimal(10,2) NOT NULL COMMENT '门票价格=单价',
  `ticket_rights_num` int unsigned NOT NULL DEFAULT '0' COMMENT '门票权益数=核销次数',
  `writeoff_rights_num` int unsigned NOT NULL DEFAULT '0' COMMENT '已核销权益数=已经核销次数',
  `explain_use` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `explain_buy` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `enter_time` int NOT NULL DEFAULT '0' COMMENT '入园时间=使用时间',
  `refund_status` enum('not_refunded','fully_refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_refunded' COMMENT '退款状态 not_refunded=未退 fully_refunded=已退',
  `refund_progress` enum('init','pending_review','refuse','approved','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'init' COMMENT '退款进度  init=初始化状态  pending_review=已提交、待审核 refuse=拒绝 approved=通过 completed=完成退款',
  `refund_time` int unsigned DEFAULT NULL COMMENT '退款时间',
  `refund_amount` decimal(10,2) DEFAULT NULL COMMENT '退款金额',
  `refund_id` int DEFAULT NULL COMMENT '退款交易数据表的主键ID',
  `is_full_refund` tinyint(1) DEFAULT NULL COMMENT '是否整单退款  1=是 0=否',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '订单创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '订单更新时间',
  `delete_time` int DEFAULT NULL COMMENT '软删除',
  `settlement_status` enum('unsettled','in_progress','settled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_trade_no` (`out_trade_no`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  UNIQUE KEY `out_refund_no` (`out_refund_no`),
  KEY `refund_status` (`refund_status`),
  KEY `trade_no` (`trade_no`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=847 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='票务-订单-从表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_order_detail_rights   */
/******************************************/
CREATE TABLE `tp_ticket_order_detail_rights` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `order_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `detail_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '订单从表ID',
  `detail_date` date NOT NULL COMMENT '订单从表ticket_date日期',
  `detail_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单从表ticket_code编号',
  `rights_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '权益名称',
  `rights_verifier_ids` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '下单时的权益核验人集合',
  `rights_id` int unsigned NOT NULL DEFAULT '0' COMMENT '权益ID',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态，0=待核销;1=已核销',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '权益编号',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `uuid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'UUID',
  `delete_time` bigint DEFAULT NULL COMMENT '删除时间',
  `writeoff_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '核销时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=788 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='订单游客权益表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_order_ota   */
/******************************************/
CREATE TABLE `tp_ticket_order_ota` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `channel` enum('xc','mt') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '来源',
  `otaOrderId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ota平台订单号',
  `out_trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '系统的外部订单号',
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '原始数据',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建订单',
  PRIMARY KEY (`id`),
  UNIQUE KEY `out_trade_no` (`out_trade_no`)
) ENGINE=InnoDB AUTO_INCREMENT=423 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='门票-ota订单'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_order_ota_item   */
/******************************************/
CREATE TABLE `tp_ticket_order_ota_item` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '项编号',
  `item_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '携程的订单项编号',
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '编号',
  `ota_id` int NOT NULL COMMENT 'ota订单编号',
  `out_trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `detail_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '游客集合',
  `ticket_id` int unsigned NOT NULL DEFAULT '0' COMMENT '票种ID',
  `ticket_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '票种编号',
  `quantity` int unsigned NOT NULL DEFAULT '0' COMMENT '购买数量',
  `date` date DEFAULT NULL COMMENT '购买日期',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '备注信息',
  `price` decimal(10,2) DEFAULT NULL COMMENT '售价',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '结算价=实际到账金额',
  `stock` int unsigned NOT NULL DEFAULT '0' COMMENT '下单时剩余库存',
  `voucher_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '核销凭证数据',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `ticket_code` (`ticket_code`,`out_trade_no`)
) ENGINE=InnoDB AUTO_INCREMENT=371 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='门票-ota订单-项目'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_pay   */
/******************************************/
CREATE TABLE `tp_ticket_pay` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '交易ID',
  `trade_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单ID',
  `uuid` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '用户ID',
  `openid` varchar(38) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `body` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '描述',
  `money` varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '价格 单位：分',
  `payip` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '支付IP',
  `appid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '小程序ID',
  `mch_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '商户号',
  `result_code` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '业务结果',
  `trade_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '交易类型:JSAPI、NATIVE、APP',
  `total_fee` int DEFAULT NULL COMMENT '订单金额',
  `transaction_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '微信支付订单号',
  `time_end` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '支付完成时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待支付 1 支付成功 2支付失败',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  `delete_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uuid`),
  KEY `trade_sn` (`trade_no`,`money`,`status`,`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='基础-支付-交易数据'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_pay_notify   */
/******************************************/
CREATE TABLE `tp_ticket_pay_notify` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '消费ID',
  `appid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '小程序ID',
  `mch_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户号',
  `result_code` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '业务结果',
  `openid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '用户标识',
  `trade_type` varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '交易类型',
  `total_fee` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单金额',
  `transaction_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信支付订单号',
  `trade_no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户订单号',
  `time_end` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '支付完成时间',
  `msg` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '类型说明',
  `ip` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `status` tinyint unsigned NOT NULL COMMENT '支付状态',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  `delete_time` int DEFAULT NULL,
  `update_time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`mch_id`),
  KEY `creat_at` (`appid`),
  KEY `type` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='票务-支付回调-验证记录表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_price   */
/******************************************/
CREATE TABLE `tp_ticket_price` (
  `ticket_id` int NOT NULL COMMENT '所属票种',
  `seller_id` int NOT NULL COMMENT '商户ID',
  `date` date NOT NULL COMMENT '日期',
  `online_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '线上价',
  `casual_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '散客价',
  `team_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '团体票价',
  `stock` int unsigned NOT NULL DEFAULT '0' COMMENT '剩余库存',
  `total_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '总库存',
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  UNIQUE KEY `ticket_id` (`ticket_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_refunds   */
/******************************************/
CREATE TABLE `tp_ticket_refunds` (
  `id` mediumint unsigned NOT NULL AUTO_INCREMENT COMMENT '交易ID',
  `uuid` varchar(49) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'uuid',
  `appid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '小程序ID',
  `mch_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户号',
  `trade_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单主表编号',
  `order_detail_no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '订单详情编号',
  `out_refund_no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '外部退款编号详情',
  `total_fee` varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单金额 ',
  `refund_fee` varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '申请退款金额',
  `refund_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款原因',
  `refund_ip` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '退款IP',
  `return_code` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '返回状态码',
  `result_code` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '业务结果',
  `transaction_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信支付订单号',
  `refund_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '微信退款单号',
  `settlement_refund_fee` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '退款金额',
  `refund_status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '退款状态',
  `success_time` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '退款成功时间',
  `refund_recv_accout` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '退款入账账户',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 0待退款 1 退款成功 2退款失败',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  `delete_time` int DEFAULT NULL COMMENT '软删除',
  `refuse_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '拒绝理由',
  PRIMARY KEY (`id`),
  KEY `trade_sn` (`trade_no`,`total_fee`,`status`,`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='票务-退款-交易数据'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_refunds_notify   */
/******************************************/
CREATE TABLE `tp_ticket_refunds_notify` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '消费ID',
  `appid` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '小程序ID',
  `mch_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户号',
  `req_info` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '加密信息',
  `return_code` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '返回状态码',
  `return_msg` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '返回信息',
  `transaction_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信支付订单号',
  `out_trade_no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户订单号',
  `refund_id` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '微信退款单号',
  `out_refund_no` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '商户退款单号',
  `refund_fee` int NOT NULL COMMENT '申请退款金额',
  `settlement_refund_fee` int NOT NULL COMMENT '退款金额',
  `refund_status` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款状态',
  `success_time` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `refund_recv_accout` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '退款入账账户',
  `msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '类型说明',
  `ip` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '操作IP',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '添加时间',
  `delete_time` int DEFAULT NULL,
  `update_time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`mch_id`),
  KEY `creat_at` (`appid`),
  KEY `type` (`return_msg`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='票务-退款回调-验证记录表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_rights   */
/******************************************/
CREATE TABLE `tp_ticket_rights` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '权益名称',
  `verifier_ids` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '核验人IP',
  `ticket_id` int unsigned NOT NULL DEFAULT '0' COMMENT '票种ID',
  `seller_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='门票权益（门票多次核销+核销人绑定）'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_settlement   */
/******************************************/
CREATE TABLE `tp_ticket_settlement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '结算单ID',
  `uuno` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '结算自定义编码',
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '结算标题',
  `mid` int NOT NULL DEFAULT '0' COMMENT '商户ID',
  `nickname` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '商户名称',
  `class_id` tinyint(1) DEFAULT NULL COMMENT '商户分类',
  `area` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '商户所属区域',
  `period` enum('week','month') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'week' COMMENT '结算周期 week=周结算  month=月结算',
  `start_date` datetime NOT NULL COMMENT '结算开始日期',
  `ent_date` datetime NOT NULL COMMENT '结算截至日期',
  `amount` decimal(10,2) NOT NULL COMMENT '结算金额',
  `order_numbers` int NOT NULL COMMENT '订单数=结算记录单总数',
  `ticiet_numbers` int NOT NULL COMMENT '订单内门票总数',
  `status` enum('pending','in_progress','settled','cancelled','exception') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'pending' COMMENT '结算状态 pending = 待申请,in_progress = 结算中,settled = 已结算,cancelled = 已取消,exception = 异常',
  `audit_status` enum('pending','pass','fail','uploaded') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'uploaded' COMMENT '审核状态 pending = 待审核,pass = 通过,fail = 未通过 uploaded=待上传资料',
  `update_time` int NOT NULL COMMENT '更新时间',
  `create_time` int NOT NULL COMMENT '创建时间',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '结算备注',
  `card_name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '收款账号',
  `card_deposit` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '开户行',
  `cart_number` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '卡号',
  `data_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '申请材料明细文件地址',
  `data_detail` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '申请资料明细url pdf生成地址',
  `enstr` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '所有订单id串M5D，用于唯一性校验',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uuno` (`uuno`),
  UNIQUE KEY `idx_enstr` (`enstr`),
  KEY `idx_mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='门票-订单-结算'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_settlement_audit   */
/******************************************/
CREATE TABLE `tp_ticket_settlement_audit` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '审核iD',
  `uuno` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '结算编码',
  `admin_id` int NOT NULL COMMENT '审核人',
  `group_id` int NOT NULL COMMENT '审核人角色',
  `create_time` int NOT NULL COMMENT '审核时间',
  `status` enum('pass','fail') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '审核结果 pass=通过  fail=未通过',
  `view` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '审核意见',
  `update_time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='门票-结算-审核记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_settlement_records   */
/******************************************/
CREATE TABLE `tp_ticket_settlement_records` (
  `uuno` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '结算编码',
  `trade_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '订单主表编码',
  `slave_trade_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '订单从表编号',
  `create_time` int NOT NULL DEFAULT '0',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  UNIQUE KEY `uu_no_idx` (`uuno`,`trade_no`,`slave_trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='门票-结算-记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_user   */
/******************************************/
CREATE TABLE `tp_ticket_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `uuid` varchar(48) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int DEFAULT NULL,
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `username` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `salt` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '盐值',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '姓名',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机',
  `login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录IP',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户名称',
  `trust_agreement` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '售票员诚信协议',
  `idcard_front_back` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '身份证正反面',
  `loginnum` int NOT NULL COMMENT '登录次数',
  `err_num` int NOT NULL COMMENT '错误次数',
  `lock_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '锁定时间',
  `signpass` varchar(33) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'passwd token',
  `expiry_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mid_uname_mobile` (`mid`,`name`,`mobile`),
  UNIQUE KEY `uname` (`username`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='票务-商户-售票员'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_user_tourist   */
/******************************************/
CREATE TABLE `tp_ticket_user_tourist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `fullname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `cert_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件类型：身份证：驾驶证等',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `cert_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '证件号码',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '证件认证状态，0为认证，1已认证',
  `user_id` int unsigned NOT NULL COMMENT '所属用户',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='同行游客表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_ticket_write_off   */
/******************************************/
CREATE TABLE `tp_ticket_write_off` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `order_detail_id` bigint unsigned NOT NULL COMMENT '门票ID',
  `order_detail_rights_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '门票权益ID',
  `ticket_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '门票编号',
  `use_device` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '核销设备',
  `writeoff_id` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人ID',
  `writeoff_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '核销人姓名',
  `use_lat` decimal(10,7) DEFAULT NULL COMMENT '核销时纬度',
  `use_lng` decimal(10,7) DEFAULT NULL COMMENT '核销时经度',
  `use_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销时详细地址',
  `use_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '核销时IP',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '核销状态',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '核销时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour   */
/******************************************/
CREATE TABLE `tp_tour` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态 1=审核中  2=不通过  3=通过  4=确定团 5=旅行团结束',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅行团名称',
  `no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '团号',
  `term` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '团期',
  `numbers` int unsigned NOT NULL DEFAULT '0' COMMENT '人数',
  `planner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '计调人',
  `mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '联系电话',
  `line_info` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '线路信息',
  `travel_id` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行消费券ID',
  `spot_ids` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '景区消费券IDS',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团商户ID',
  `is_to_ckeck` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否需要旅投审核',
  `over_time` int unsigned NOT NULL DEFAULT '0' COMMENT '团结束时间',
  `tour_accounting_id` int unsigned NOT NULL DEFAULT '0' COMMENT '是否核算',
  `accounting_time` int unsigned NOT NULL DEFAULT '0' COMMENT '核算时间',
  `invoice` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '旅行团发票',
  `photos` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '旅行团合影',
  `term_start` int unsigned NOT NULL DEFAULT '0' COMMENT '团期开始日期',
  `term_end` int unsigned NOT NULL DEFAULT '0' COMMENT '团期结束日期',
  `ht_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '合同保单上传类型1=团客=整团用一个保单合同 2=散拼分组',
  `is_locking` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否锁定游客加入  1=是 0=否',
  `dining` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '就餐发票',
  `travelling_expenses` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '交通发票',
  `modif_numbers` int unsigned NOT NULL DEFAULT '0' COMMENT '发票合影修改次数',
  `area_id` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '客源地',
  `brief` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '简要说明',
  `guide_name` varchar(75) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '导游姓名',
  `guide_certificate` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '导游证号',
  `team_type` varchar(128) DEFAULT NULL COMMENT '团类型',
  PRIMARY KEY (`id`),
  KEY `no` (`no`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=1065 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-基表管理'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_accounting   */
/******************************************/
CREATE TABLE `tp_tour_accounting` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `data_detail` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅行社结算申请资料明细',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '运营审核   1=通过  0=待上传资料  2=不通过 3=待审核',
  `write_off_ids` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '核销记录ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '备注信息',
  `sum_coupon_price` decimal(10,0) unsigned NOT NULL DEFAULT '0' COMMENT '核算金额',
  `writeoff_total` int unsigned NOT NULL DEFAULT '0' COMMENT '核算记录数',
  `card_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '收款账号',
  `card_deposit` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '开户行',
  `cart_number` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '卡号',
  `tour_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '文旅审核  1=通过 0=待审核 2=不通过',
  `sup_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '财政审核  1=通过  0=待审核  2=不通过',
  `back_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '银行打款        1=已付款  0=待付款  2=拒绝付款',
  `sup_card` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '监管单位上传附件地址',
  `back_card` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '银行打款凭据地址',
  `class_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '商家分类',
  `area` int unsigned NOT NULL DEFAULT '0' COMMENT '所属区域',
  `nickname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `data_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '申请材料明细文件地址',
  `no` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '结算单号',
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-消费券-核算'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_audit_record   */
/******************************************/
CREATE TABLE `tp_tour_audit_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `step` int unsigned NOT NULL DEFAULT '0' COMMENT '审核阶段',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '审核人',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '角色',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '审核备注',
  `image` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '文件凭据',
  `aid` int unsigned NOT NULL DEFAULT '0' COMMENT '核算记录ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1570 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-消费券-核算审核记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_coupon_group   */
/******************************************/
CREATE TABLE `tp_tour_coupon_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `receive_time` int unsigned NOT NULL DEFAULT '0' COMMENT '领取时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否核销  1=是 2=已过期 0=否',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `coupon_issue_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `is_receive` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否领取',
  `cid` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券分类ID',
  `enstr_salt` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '数据加密串',
  `qrcode_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '二维码加密串',
  `code_time_create` int unsigned NOT NULL DEFAULT '0' COMMENT '二维码创建时间',
  `code_time_expire` int unsigned NOT NULL DEFAULT '0' COMMENT '二维码过期时间',
  `write_use` int unsigned NOT NULL DEFAULT '0' COMMENT '核销时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8900 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-消费券【注册团体后生成】'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_guest   */
/******************************************/
CREATE TABLE `tp_tour_guest` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号 = 散客核销记录主键ID',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '核销时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `coupon_issue_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '领取记录ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户名称',
  `userid` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '核销备注',
  `orderid` int unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `enstr_salt` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '核销加密串',
  `coupon_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券名称',
  `coupon_price` decimal(10,0) unsigned NOT NULL DEFAULT '0' COMMENT '消费券面额',
  `use_min_price` decimal(10,0) unsigned NOT NULL DEFAULT '0' COMMENT '最低消费多少可使用优惠券',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券开启时间',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券结束时间',
  `qrcode_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '领取二维码图片地址',
  `uuno` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券编号',
  `coupon_issue_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `accounting_id` int unsigned NOT NULL DEFAULT '0' COMMENT '是否核算',
  `is_allow_settlement` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否允许结算  1=是  0=否',
  `is_uploads_cert` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否上传保单合同  1=是 0=否',
  `contract` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅游合同',
  `insurance` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅游保单',
  `is_transfer` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否转移  1=是  0=申请中',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '核销的用户ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-散客核销记录转游客'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_hotel   */
/******************************************/
CREATE TABLE `tp_tour_hotel` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '酒店名称',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3721 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-酒店关联表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_hotel_sign   */
/******************************************/
CREATE TABLE `tp_tour_hotel_sign` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '记录编号  用于展示',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '记录备注',
  `need_numbers` int unsigned NOT NULL DEFAULT '0' COMMENT '打卡次数',
  `tourist_numbers` int NOT NULL DEFAULT '0' COMMENT '游客数',
  `longitude` double(10,6) NOT NULL COMMENT '经度',
  `latitude` double(10,6) NOT NULL COMMENT '纬度',
  `hotel_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '酒店名称',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=1799 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-导游-生成酒店打卡记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_hotel_user_record   */
/******************************************/
CREATE TABLE `tp_tour_hotel_user_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0',
  `sign_id` int unsigned NOT NULL DEFAULT '0' COMMENT '酒店记录ID',
  `is_clock` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否完成打卡',
  `clock_time` int unsigned NOT NULL DEFAULT '0' COMMENT '打卡时间',
  `spot_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '酒店打卡名称',
  `images` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '酒店照片',
  `address` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '打卡位置',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `descs` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '打卡留言',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '团ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `guid` int DEFAULT NULL COMMENT '导游用户ID',
  `gid` int unsigned NOT NULL DEFAULT '0' COMMENT '导游代打卡',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sign_id` (`sign_id`),
  KEY `guid` (`guid`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=85743 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-游客-酒店打卡记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_issue_user   */
/******************************************/
CREATE TABLE `tp_tour_issue_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '领取时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `issue_coupon_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `coupon_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券标题',
  `coupon_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费券面额',
  `use_min_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最低消费多少可使用优惠券',
  `coupon_create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券创建时间',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券开启时间',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券结束时间',
  `time_use` int unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  `status` int unsigned NOT NULL DEFAULT '0' COMMENT '状态（0：未使用，1：已使用, 2:已过期）',
  `is_fail` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '是否有效 ',
  `is_limit_total` tinyint(1) NOT NULL COMMENT '是否限制领取  1=是 0=否',
  `issue_coupon_class_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券种类',
  `enstr_salt` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '数据加密串',
  `is_clock` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否完成打卡',
  `clock_time` int unsigned NOT NULL DEFAULT '0' COMMENT '打卡时间',
  `spot_name` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '打卡景点名称',
  `images` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '景区照片',
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '打卡位置',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1=旅行券 2=景区券',
  `expire_time` int unsigned NOT NULL DEFAULT '0' COMMENT '团体用户消费券过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_lianhe` (`tid`,`uid`,`issue_coupon_id`),
  KEY `tid_idx` (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=143868 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-游客消费券领取记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tour_write_off   */
/******************************************/
CREATE TABLE `tp_tour_write_off` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `tour_issue_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '游客领取ID',
  `tour_coupon_group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '团体券ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户ID',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `userid` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人ID',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '核销备注',
  `orderid` int unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `enstr_salt` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '核销加密串',
  `coupon_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券名称',
  `coupon_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费券面额',
  `use_min_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最低消费多少可使用优惠券',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券开启时间',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券结束时间',
  `uuno` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券编号',
  `coupon_issue_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `accounting_id` int unsigned NOT NULL DEFAULT '0' COMMENT '是否核算',
  `type` tinyint(1) NOT NULL COMMENT '1=旅行券   2=景区券',
  `is_clock` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否完成打卡',
  `clock_time` int unsigned NOT NULL DEFAULT '0' COMMENT '打卡时间',
  `spot_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '打卡景点名称',
  `images` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '景区照片',
  `address` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '打卡位置',
  `longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '经度',
  `latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '纬度',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户表ID',
  `descs` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '打卡留言',
  `gid` int unsigned NOT NULL DEFAULT '0' COMMENT '导游代打卡',
  `uw_longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '用户核销时经度',
  `uw_latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '用户核销时纬度',
  `he_longitude` double(10,6) DEFAULT NULL COMMENT '核验人经度',
  `he_latitude` double(10,6) DEFAULT NULL COMMENT '核验人纬度',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tour_issue_user_id` (`tour_issue_user_id`),
  KEY `mid` (`mid`),
  KEY `userid` (`userid`),
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=129532 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-团体-消费券核销记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_tourist   */
/******************************************/
CREATE TABLE `tp_tourist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '电话',
  `idcard` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `tid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行团ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行社商户ID',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `contract` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅游合同',
  `insurance` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '旅游保单',
  `tour_receive_time` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行券领取时间',
  `tour_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '旅行券面额',
  `numbers` int unsigned NOT NULL DEFAULT '0' COMMENT '打卡次数=景区打卡批次',
  `tour_writeoff_time` int unsigned NOT NULL DEFAULT '0' COMMENT '旅行券核销时间',
  `card_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '证件类型',
  `card_file` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '证件照片',
  `is_authenticated` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否一键认证',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idcard` (`idcard`,`tid`),
  UNIQUE KEY `idx_mtm` (`mobile`,`tid`),
  KEY `mobile` (`mobile`),
  KEY `tid` (`tid`,`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=63967 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='旅行社-游客-信息'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_users   */
/******************************************/
CREATE TABLE `tp_users` (
  `id` mediumint NOT NULL AUTO_INCREMENT COMMENT '编号',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `sex` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `last_login_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `qq` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'QQ',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '手机',
  `mobile_validated` tinyint DEFAULT '0' COMMENT '验证手机:1=验证,0=未验证',
  `email_validated` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '邮箱验证',
  `type_id` tinyint DEFAULT '0' COMMENT '所属分组',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  `create_ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '注册IP',
  `update_time` int unsigned DEFAULT '0' COMMENT '更新时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `openid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '小程序openid',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `headimgurl` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信头像',
  `idcard` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '身份证号',
  `age` int unsigned NOT NULL DEFAULT '0' COMMENT '年龄',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信昵称',
  `uuid` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '唯一标识',
  `salt` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '加密盐',
  `starsign` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '星座',
  `zodiac` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生肖',
  `birthday` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '生日',
  `province` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '省份',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '城市',
  `district` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '区县',
  `signpass` char(33) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'passwd token',
  `expiry_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `card_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '证件类型 1身份证 2护照 3台湾通行证 4港澳通行证 5回乡证',
  `credit_score` char(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '信用值',
  `credit_rating` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '信用等级',
  `update_credit` int unsigned NOT NULL DEFAULT '0' COMMENT '桃花分最后更新时间',
  `auth_status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否认证',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `index_idcard` (`idcard`),
  UNIQUE KEY `idx_openid` (`openid`),
  KEY `credit_score` (`credit_score`),
  KEY `signpass` (`signpass`),
  KEY `idx_age` (`age`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=260161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='用户-信息-基表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_users_auth_log   */
/******************************************/
CREATE TABLE `tp_users_auth_log` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '访问ID',
  `uid` int NOT NULL COMMENT '用户ID',
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '姓名',
  `idcard` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户身份证号',
  `mobile` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '用户手机号',
  `create_time` int DEFAULT NULL COMMENT '认证时间',
  `order_no` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '订单',
  `status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '1=成功  0=失败',
  `result` tinyint(1) NOT NULL COMMENT '认证结果 0-一致，1-不一致，2-无记录',
  `msg` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消息',
  `return_data` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '第三方接口返回',
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=158358 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='用户-认证-记录'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_users_sms_log   */
/******************************************/
CREATE TABLE `tp_users_sms_log` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '访问ID',
  `uid` int NOT NULL COMMENT '用户ID',
  `mobile` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '用户手机号',
  `sms_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '验证码',
  `template` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '发送内容',
  `create_time` int DEFAULT NULL COMMENT '发送时间',
  `smsid` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '发送id',
  `code` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '1=成功  0=失败',
  `balance` int NOT NULL COMMENT '剩余',
  `msg` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消息',
  `expire_time` int DEFAULT NULL COMMENT '验证码过期时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68794 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='用户-短信-发送认证'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_users_tourist   */
/******************************************/
CREATE TABLE `tp_users_tourist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `cert_type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '证件类型 1身份证 2护照 3台湾通行证 4港澳通行证 5回乡证',
  `cert_id` varbinary(20) NOT NULL COMMENT '证件号',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '证件认证状态;0=已认证;1=未认证',
  `create_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` bigint unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='同行游客表'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_users_type   */
/******************************************/
CREATE TABLE `tp_users_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '描述',
  `sort` int unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='用户-信息-分组'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_verify_accounting_record   */
/******************************************/
CREATE TABLE `tp_verify_accounting_record` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL COMMENT '状态',
  `no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '项目结算单号',
  `project_name` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '清爽榆林消费券' COMMENT '项目名称',
  `cycle_start` int NOT NULL DEFAULT '0' COMMENT '项目结算周期开始',
  `cycle_end` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '项目结算周期结束',
  `sum_total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '结算总金额',
  `seller_numbers` int NOT NULL DEFAULT '0' COMMENT '渠道数量=商家数量',
  `tourist_numbers` int NOT NULL DEFAULT '0' COMMENT '游客数量',
  `file_url` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '文件地址',
  `receipt` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '回单地址',
  `cycle` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '结算日期',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='账单-阶段性结算-对账单'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_verify_collect   */
/******************************************/
CREATE TABLE `tp_verify_collect` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `create_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL COMMENT '状态',
  `vid` int NOT NULL DEFAULT '0' COMMENT '对账单ID',
  `class_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '商家分类ID',
  `class_name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '商家名称',
  `mid` int NOT NULL DEFAULT '0' COMMENT '商家ID',
  `seller_no` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '商家编号',
  `name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '商家联系人',
  `mobile` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '商家联系电话',
  `writeoff_total` int NOT NULL DEFAULT '0' COMMENT '核销总数',
  `sum_coupon_price` decimal(10,2) NOT NULL COMMENT '核销总金额',
  `card_name` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '收款账号',
  `card_deposit` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '开户行',
  `cart_number` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '卡号',
  `accounting_create_time` int NOT NULL COMMENT '结算申请时间',
  `accounting_no` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '结算单号',
  `accounting_data_detail` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL COMMENT '材料申请附件地址',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '运营审核人ID',
  `audit_time` int unsigned NOT NULL DEFAULT '0' COMMENT '运营审核时间',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `cycle` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '' COMMENT '结算周期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='账单-阶段性结算-对账单汇总记录【各商家核算申请记录】'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_write_error_log   */
/******************************************/
CREATE TABLE `tp_write_error_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人',
  `isd` int NOT NULL DEFAULT '0' COMMENT '领取ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '错误提醒',
  `ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求IP',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User-Agent',
  `uw_longitude` double(10,6) DEFAULT NULL COMMENT '用户核销时经度',
  `uw_latitude` double(10,6) DEFAULT NULL COMMENT '用户核销时纬度',
  `he_longitude` double(10,6) DEFAULT NULL COMMENT '核销人经度',
  `he_latitude` double(10,6) DEFAULT NULL COMMENT '核销人纬度',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91744 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='散客-核销-日志'
;

/******************************************/
/*   DatabaseName = xfq_v2   */
/*   TableName = tp_write_off   */
/******************************************/
CREATE TABLE `tp_write_off` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '编号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '核销时间',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` mediumint DEFAULT '50' COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  `coupon_issue_user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '领取记录ID',
  `mid` int unsigned NOT NULL DEFAULT '0' COMMENT '商户名称',
  `userid` int unsigned NOT NULL DEFAULT '0' COMMENT '核销人',
  `he_longitude` double(10,6) NOT NULL DEFAULT '0.000000' COMMENT '核销人核销时经度',
  `he_latitude` double(10,6) NOT NULL DEFAULT '0.000000' COMMENT '核销人核销时纬度',
  `remark` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT '核销备注',
  `orderid` int unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `enstr_salt` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '核销加密串',
  `coupon_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券名称',
  `coupon_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费券面额',
  `use_min_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最低消费多少可使用优惠券',
  `time_start` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券开启时间',
  `time_end` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券结束时间',
  `qrcode_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '领取二维码图片地址',
  `uuno` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '消费券编号',
  `coupon_issue_id` int unsigned NOT NULL DEFAULT '0' COMMENT '消费券ID',
  `accounting_id` int unsigned NOT NULL DEFAULT '0' COMMENT '是否核算',
  `is_allow_settlement` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否允许结算  1=是  0=否',
  `is_uploads_cert` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否上传保单合同  1=是 0=否',
  `uw_longitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '用户核销时经度',
  `uw_latitude` double(10,6) unsigned NOT NULL DEFAULT '0.000000' COMMENT '用户核销时纬度',
  `uid` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `poi_longitude` double(10,6) NOT NULL COMMENT '点经度',
  `poi_latitude` double(10,6) NOT NULL COMMENT '点纬度',
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_issue_user_id` (`coupon_issue_user_id`),
  KEY `mid` (`mid`),
  KEY `coupon_issue_id` (`coupon_issue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=202838 DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='消费券-散客-核销记录'
;
