BACKEND_ROOT := xfq.ylbigdata.com/xfq.ylbigdata.com

P0_QUALITY_BATCH1_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch1
P0_QUALITY_BATCH2_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch2
P0_QUALITY_BATCH3_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch3
P0_QUALITY_BATCH4_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch4
P0_QUALITY_BATCH5_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch5
P0_QUALITY_BATCH6_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch6
P0_QUALITY_BATCH7_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch7
P0_QUALITY_BATCH8_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch8
P0_QUALITY_BATCH9_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch9
P0_QUALITY_BATCH10_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch10
P0_QUALITY_BATCH11_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch11
P0_QUALITY_BATCH12_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch12
P0_QUALITY_BATCH13_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch13
P0_QUALITY_BATCH14_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch14
P0_QUALITY_BATCH15_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch15
P0_QUALITY_BATCH16_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch16
P0_QUALITY_BATCH17_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_quality_batch17
P0_SUCCESS_BATCH18_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH19_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH20_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH21_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH22_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH23_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH24_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH25_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH26_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success
P0_SUCCESS_BATCH27_CASES_DIR := $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success

P0_QUALITY_BATCH1_CASES := \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-get-api-appt-getDetail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-get-api-ticket-getOrderDetail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-seller-detail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-user-index-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-user-tour_coupon_group-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-appt-cancelAppt-001.json

P0_QUALITY_BATCH1_DIFF_CASES := \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-get-api-appt-getDetail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-get-api-ticket-getOrderDetail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-seller-detail-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-user-index-001.json \
	--case $(P0_QUALITY_BATCH1_CASES_DIR)/miniapp/p0-miniapp-post-api-user-tour_coupon_group-001.json

P0_QUALITY_BATCH2_CASES := \
	--case $(P0_QUALITY_BATCH2_CASES_DIR)/miniapp/p0-miniapp-post-api-coupon-receive-001.json \
	--case $(P0_QUALITY_BATCH2_CASES_DIR)/miniapp/p0-miniapp-post-api-user-writeoff_tour-001.json

P0_QUALITY_BATCH3_CASES := \
	--case $(P0_QUALITY_BATCH3_CASES_DIR)/api/p0-quality3-post-api-upload-index-001.json \
	--case $(P0_QUALITY_BATCH3_CASES_DIR)/api/p0-quality3-post-api-webupload-index-001.json \
	--case $(P0_QUALITY_BATCH3_CASES_DIR)/selfservice/p0-quality3-post-selfservice-upload-index-001.json \
	--case $(P0_QUALITY_BATCH3_CASES_DIR)/selfservice/p0-quality3-post-selfservice-webupload-index-001.json

P0_QUALITY_BATCH4_CASES := \
	--case $(P0_QUALITY_BATCH4_CASES_DIR)/meituan/p0-quality4-post-meituan-webupload-index-001.json \
	--case $(P0_QUALITY_BATCH4_CASES_DIR)/xc/p0-quality4-post-xc-webupload-index-001.json

P0_QUALITY_BATCH5_CASES := \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-index-get_area_info-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-pay-OrderRefund-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-pay-regressionStock-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-ticket-OrderRefund-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-ticket-OrderRefundDetail-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-notify-create_file-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-ticket-create_file-001.json \
	--case $(P0_QUALITY_BATCH5_CASES_DIR)/api/p0-quality5-post-api-seller-search-001.json

P0_QUALITY_BATCH6_CASES := \
	--case $(P0_QUALITY_BATCH6_CASES_DIR)/api/p0-quality6-get-api-user-getTouristList-001.json \
	--case $(P0_QUALITY_BATCH6_CASES_DIR)/api/p0-quality6-post-api-coupon-writeoff-001.json

P0_QUALITY_BATCH7_CASES := \
	--case $(P0_QUALITY_BATCH7_CASES_DIR)/api/p0-quality7-post-api-user-getTouristList-001.json

P0_QUALITY_BATCH8_CASES := \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-coupon-getIssueCouponList-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-screen-index-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-notify-pay_async_notice-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-notify-refund-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-ticket-notify_pay-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/api/p0-quality8-post-api-ticket-notify_refund-001.json \
	--case $(P0_QUALITY_BATCH8_CASES_DIR)/selfservice/p0-quality8-post-selfservice-ticket-getTravelWxappQrcode-001.json

P0_QUALITY_BATCH9_CASES := \
	--case $(P0_QUALITY_BATCH9_CASES_DIR)/selfservice/p0-quality9-get-selfservice-index-captcha-001.json \
	--case $(P0_QUALITY_BATCH9_CASES_DIR)/api/p0-quality9-post-api-system-XdataSummary-001.json \
	--case $(P0_QUALITY_BATCH9_CASES_DIR)/api/p0-quality9-post-api-test-tokenTaohua-001.json \
	--case $(P0_QUALITY_BATCH9_CASES_DIR)/xc/p0-quality9-get-xc-order-testGetOrder-001.json \
	--case $(P0_QUALITY_BATCH9_CASES_DIR)/xc/p0-quality9-post-xc-order-testGetOrder-001.json

P0_QUALITY_BATCH10_CASES := \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-index-regeo-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-index-set_user_info-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-pay-aaa-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-system-rollback_remain_count-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-system-rollback_remain_count_extend-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-system-rollback_set_data-001.json \
	--case $(P0_QUALITY_BATCH10_CASES_DIR)/api/p0-quality10-post-api-system-set_tour_invalid-001.json

P0_QUALITY_BATCH11_CASES := \
	--case $(P0_QUALITY_BATCH11_CASES_DIR)/api/p0-quality11-post-api-test-rsyncTaohua-001.json

P0_QUALITY_BATCH12_CASES := \
	--case $(P0_QUALITY_BATCH12_CASES_DIR)/api/p0-quality12-post-api-upload-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH12_CASES_DIR)/api/p0-quality12-post-api-webupload-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH12_CASES_DIR)/selfservice/p0-quality12-post-selfservice-upload-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH12_CASES_DIR)/selfservice/p0-quality12-post-selfservice-webupload-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH12_CASES_DIR)/meituan/p0-quality12-post-meituan-webupload-index-legacy500-001.json

P0_QUALITY_BATCH13_CASES := \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-index-get_area_info-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-pay-OrderRefund-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-pay-regressionStock-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-ticket-OrderRefund-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-ticket-OrderRefundDetail-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-notify-create_file-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/api/p0-quality13-post-api-ticket-create_file-legacy500-001.json \
	--case $(P0_QUALITY_BATCH13_CASES_DIR)/miniapp/p0-quality13-post-api-seller-search-legacy-empty-001.json

P0_QUALITY_BATCH14_CASES := \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/api/p0-quality14-post-api-user-getTouristList-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-get-api-appt-getDetail-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-get-api-ticket-getOrderDetail-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-post-api-appt-cancelAppt-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-post-api-coupon-writeoff-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-post-api-seller-detail-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-post-api-user-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/miniapp/p0-quality14-post-api-user-tour_coupon_group-legacy500-001.json \
	--case $(P0_QUALITY_BATCH14_CASES_DIR)/xc/p0-quality14-post-xc-webupload-index-legacy500-001.json

P0_QUALITY_BATCH15_CASES := \
	--case $(P0_QUALITY_BATCH15_CASES_DIR)/api/p0-quality15-post-api-user-getTouristList-legacy500-001.json \
	--case $(P0_QUALITY_BATCH15_CASES_DIR)/miniapp/p0-quality15-post-api-user-writeoff_tour-legacy500-001.json

P0_QUALITY_BATCH16_CASES := \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-index-regeo-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-index-set_user_info-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-pay-aaa-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-system-rollback_remain_count-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-system-rollback_remain_count_extend-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-system-rollback_set_data-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-system-set_tour_invalid-legacy-empty-001.json \
	--case $(P0_QUALITY_BATCH16_CASES_DIR)/api/p0-quality16-post-api-test-rsyncTaohua-legacy-empty-001.json

P0_QUALITY_BATCH17_CASES := \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-coupon-getIssueCouponList-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/miniapp/p0-quality17-post-api-coupon-receive-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-notify-pay_async_notice-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-notify-refund-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-screen-index-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-system-XdataSummary-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-test-tokenTaohua-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-ticket-notify_pay-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/api/p0-quality17-post-api-ticket-notify_refund-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/selfservice/p0-quality17-get-selfservice-index-captcha-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/selfservice/p0-quality17-post-selfservice-ticket-getTravelWxappQrcode-legacy500-001.json \
	--case $(P0_QUALITY_BATCH17_CASES_DIR)/xc/p0-quality17-post-xc-order-testGetOrder-legacy500-001.json

P0_SUCCESS_BATCH18_CASES := \
	--case $(P0_SUCCESS_BATCH18_CASES_DIR)/p0-success-post-api-coupon-index-001.json \
	--case $(P0_SUCCESS_BATCH18_CASES_DIR)/p0-success-post-api-index-get_area_info-001.json \
	--case $(P0_SUCCESS_BATCH18_CASES_DIR)/p0-success-post-api-seller-search-001.json \
	--case $(P0_SUCCESS_BATCH18_CASES_DIR)/p0-success-post-api-user-delTourist-001.json

P0_SUCCESS_BATCH19_CASES := \
	--case $(P0_SUCCESS_BATCH19_CASES_DIR)/p0-success-post-api-coupon-tempApi-001.json \
	--case $(P0_SUCCESS_BATCH19_CASES_DIR)/p0-success-post-api-user-getCertTypeList-001.json \
	--case $(P0_SUCCESS_BATCH19_CASES_DIR)/p0-success-get-api-ticket-getCommentList-001.json \
	--case $(P0_SUCCESS_BATCH19_CASES_DIR)/p0-success-get-api-ticket-getOrderList-001.json \
	--case $(P0_SUCCESS_BATCH19_CASES_DIR)/p0-success-get-api-ticket-getRefundLogList-001.json

P0_SUCCESS_BATCH20_CASES := \
	--case $(P0_SUCCESS_BATCH20_CASES_DIR)/p0-success-post-api-user-coupon_issue_user-001.json \
	--case $(P0_SUCCESS_BATCH20_CASES_DIR)/p0-success-post-api-user-collection_action-add-001.json \
	--case $(P0_SUCCESS_BATCH20_CASES_DIR)/p0-success-post-api-user-collection_action-del-001.json \
	--case $(P0_SUCCESS_BATCH20_CASES_DIR)/p0-success-post-api-system-tableTohtml-001.json

P0_SUCCESS_BATCH21_CASES := \
	--case $(P0_SUCCESS_BATCH21_CASES_DIR)/p0-success-post-api-user-get_user_coupon_id-001.json \
	--case $(P0_SUCCESS_BATCH21_CASES_DIR)/p0-success-post-api-user-coupon_order-001.json \
	--case $(P0_SUCCESS_BATCH21_CASES_DIR)/p0-success-post-api-user-coupon_order_detail-001.json \
	--case $(P0_SUCCESS_BATCH21_CASES_DIR)/p0-success-get-api-ticket-getScenicList-001.json \
	--case $(P0_SUCCESS_BATCH21_CASES_DIR)/p0-success-post-api-system-tableTohtml1-001.json

P0_SUCCESS_BATCH22_CASES := \
	--case $(P0_SUCCESS_BATCH22_CASES_DIR)/p0-success-get-api-appt-getDatetime-001.json \
	--case $(P0_SUCCESS_BATCH22_CASES_DIR)/p0-success-get-api-appt-getList-001.json \
	--case $(P0_SUCCESS_BATCH22_CASES_DIR)/p0-success-get-api-index-note_detail-001.json \
	--case $(P0_SUCCESS_BATCH22_CASES_DIR)/p0-success-post-api-index-note_detail-001.json \
	--case $(P0_SUCCESS_BATCH22_CASES_DIR)/p0-success-post-api-index-note_list-001.json

P0_SUCCESS_BATCH23_CASES := \
	--case $(P0_SUCCESS_BATCH23_CASES_DIR)/p0-success-post-api-index-jia-001.json \
	--case $(P0_SUCCESS_BATCH23_CASES_DIR)/p0-success-post-api-index-jie-001.json \
	--case $(P0_SUCCESS_BATCH23_CASES_DIR)/p0-success-post-api-index-login-001.json \
	--case $(P0_SUCCESS_BATCH23_CASES_DIR)/p0-success-post-api-index-note_index-001.json \
	--case $(P0_SUCCESS_BATCH23_CASES_DIR)/p0-success-post-api-index-system-001.json

P0_SUCCESS_BATCH24_CASES := \
	--case $(P0_SUCCESS_BATCH24_CASES_DIR)/p0-success-post-api-seller-cate-001.json \
	--case $(P0_SUCCESS_BATCH24_CASES_DIR)/p0-success-post-api-seller-list-001.json \
	--case $(P0_SUCCESS_BATCH24_CASES_DIR)/p0-success-post-api-screen-list-001.json \
	--case $(P0_SUCCESS_BATCH24_CASES_DIR)/p0-success-post-api-user-auth_info-001.json \
	--case $(P0_SUCCESS_BATCH24_CASES_DIR)/p0-success-post-api-user-getTouristList-001.json

P0_SUCCESS_BATCH25_CASES := \
	--case $(P0_SUCCESS_BATCH25_CASES_DIR)/p0-success-post-api-system-queryArea-001.json \
	--case $(P0_SUCCESS_BATCH25_CASES_DIR)/p0-success-post-api-system-cleanDb-001.json \
	--case $(P0_SUCCESS_BATCH25_CASES_DIR)/p0-success-post-api-system-notification-001.json

P0_SUCCESS_BATCH26_CASES := \
	--case $(P0_SUCCESS_BATCH26_CASES_DIR)/p0-success-post-api-webupload-index-001.json \
	--case $(P0_SUCCESS_BATCH26_CASES_DIR)/p0-success-post-selfservice-upload-index-001.json \
	--case $(P0_SUCCESS_BATCH26_CASES_DIR)/p0-success-post-selfservice-webupload-index-001.json

P0_SUCCESS_BATCH27_CASES := \
	--case $(P0_SUCCESS_BATCH27_CASES_DIR)/p0-success-post-meituan-index-system-html-001.json \
	--case $(P0_SUCCESS_BATCH27_CASES_DIR)/p0-success-post-meituan-webupload-index-001.json \
	--case $(P0_SUCCESS_BATCH27_CASES_DIR)/p0-success-post-xc-index-system-html-001.json \
	--case $(P0_SUCCESS_BATCH27_CASES_DIR)/p0-success-post-xc-webupload-index-001.json

.PHONY: help
help:
	@echo "Common targets:"
	@echo "  miniapp-tsv          Refresh miniapp API usage TSV"
	@echo "  golden-miniapp       Generate miniapp golden cases"
	@echo "  golden-p0-stubs      Generate missing P0 golden stubs"
	@echo "  golden-p0-check      Check P0 golden coverage"
	@echo "  golden-audit         Audit golden quality issues"
	@echo "  system-tasks         Generate SYSTEM_TASK inventory"
	@echo "  rewrite-status       Generate migration board + status report"
	@echo "  seed-scan            Scan PHP hardcoded seed deps"
	@echo "  seed-business        Load minimal business seed"
	@echo "  seed-p0-quality-batch1  Load optional seed for P0 quality batch#1"
	@echo "  seed-p0-quality-batch2  Load optional seed for P0 quality batch#2"
	@echo "  seed-p0-quality-batch3  Load optional seed for P0 quality batch#3"
	@echo "  seed-p0-quality-batch4  Load optional seed for P0 quality batch#4"
	@echo "  seed-p0-quality-batch5  Load optional seed for P0 quality batch#5"
	@echo "  seed-p0-quality-batch6  Load optional seed for P0 quality batch#6"
	@echo "  seed-p0-quality-batch7  Load optional seed for P0 quality batch#7"
	@echo "  seed-p0-quality-batch8  Load optional seed for P0 quality batch#8"
	@echo "  seed-p0-quality-batch9  Load optional seed for P0 quality batch#9"
	@echo "  seed-p0-quality-batch10 Load optional seed for P0 quality batch#10"
	@echo "  seed-p0-quality-batch11 Load optional seed for P0 quality batch#11"
	@echo "  seed-p0-quality-batch12 Load optional seed for P0 quality batch#12"
	@echo "  seed-p0-quality-batch13 Load optional seed for P0 quality batch#13"
	@echo "  seed-p0-quality-batch14 Load optional seed for P0 quality batch#14"
	@echo "  seed-p0-quality-batch15 Load optional seed for P0 quality batch#15"
	@echo "  seed-p0-quality-batch16 Load optional seed for P0 quality batch#16"
	@echo "  seed-p0-quality-batch17 Load optional seed for P0 quality batch#17"
	@echo "  db-reset-minimal     Drop+recreate DB (schema+minimal+auth)"
	@echo "  db-reset-success     Drop+recreate DB (+success seed)"
	@echo "  db-reset-p0-quality-batch1  Reset DB(minimal)+seed batch#1"
	@echo "  db-reset-p0-quality-batch2  Reset DB(minimal)+seed batch#2"
	@echo "  db-reset-p0-quality-batch3  Reset DB(minimal)+seed batch#3"
	@echo "  db-reset-p0-quality-batch4  Reset DB(minimal)+seed batch#4"
	@echo "  db-reset-p0-quality-batch5  Reset DB(minimal)+seed batch#5"
	@echo "  db-reset-p0-quality-batch6  Reset DB(minimal)+seed batch#6"
	@echo "  db-reset-p0-quality-batch7  Reset DB(minimal)+seed batch#7"
	@echo "  db-reset-p0-quality-batch8  Reset DB(minimal)+seed batch#8"
	@echo "  db-reset-p0-quality-batch9  Reset DB(minimal)+seed batch#9"
	@echo "  db-reset-p0-quality-batch10 Reset DB(minimal)+seed batch#10"
	@echo "  db-reset-p0-quality-batch11 Reset DB(minimal)+seed batch#11"
	@echo "  db-reset-p0-quality-batch12 Reset DB(minimal)+seed batch#12"
	@echo "  db-reset-p0-quality-batch13 Reset DB(minimal)+seed batch#13"
	@echo "  db-reset-p0-quality-batch14 Reset DB(minimal)+seed batch#14"
	@echo "  db-reset-p0-quality-batch15 Reset DB(minimal)+seed batch#15"
	@echo "  db-reset-p0-quality-batch16 Reset DB(minimal)+seed batch#16"
	@echo "  db-reset-p0-quality-batch17 Reset DB(minimal)+seed batch#17"
	@echo "  db-reset-p0-success-batch18 Reset DB(success) for success batch#18"
	@echo "  db-reset-p0-success-batch19 Reset DB(success) for success batch#19"
	@echo "  db-reset-p0-success-batch20 Reset DB(success) for success batch#20"
	@echo "  db-reset-p0-success-batch21 Reset DB(success) for success batch#21"
	@echo "  db-reset-p0-success-batch22 Reset DB(success) for success batch#22"
	@echo "  db-reset-p0-success-batch23 Reset DB(success) for success batch#23"
	@echo "  db-reset-p0-success-batch24 Reset DB(success) for success batch#24"
	@echo "  db-reset-p0-success-batch25 Reset DB(success) for success batch#25"
	@echo "  db-reset-p0-success-batch26 Reset DB(success) for success batch#26"
	@echo "  db-reset-p0-success-batch27 Reset DB(success) for success batch#27"
	@echo "  db-up                Start MySQL+Redis via compose"
	@echo "  db-down              Stop MySQL+Redis via compose"
	@echo "  legacy-up            Start legacy PHP service"
	@echo "  legacy-down          Stop legacy PHP service"
	@echo "  legacy-record-p0     Record P0 baselines"
	@echo "  legacy-record-p0-dev Record P0 baselines (dev auth env)"
	@echo "  legacy-record-p0-dev-minimal  Reset DB(minimal)+record P0"
	@echo "  legacy-record-xc     Record XC baselines"
	@echo "  legacy-record-p0-success  Record P0 success baselines"
	@echo "  legacy-record-p0-success-dev  Record P0 success (dev auth env)"
	@echo "  legacy-record-p0-success-dev-reset  Reset DB(success)+record P0 success"
	@echo "  legacy-record-p0-success-batch18   Record 4 success batch#18 cases from legacy"
	@echo "  legacy-record-p0-success-batch19   Record 5 success batch#19 cases from legacy"
	@echo "  legacy-record-p0-success-batch20   Record 4 success batch#20 cases from legacy"
	@echo "  legacy-record-p0-success-batch21   Record 5 success batch#21 cases from legacy"
	@echo "  legacy-record-p0-success-batch22   Record 5 success batch#22 cases from legacy"
	@echo "  legacy-record-p0-success-batch23   Record 5 success batch#23 cases from legacy"
	@echo "  legacy-record-p0-success-batch24   Record 5 success batch#24 cases from legacy"
	@echo "  legacy-record-p0-success-batch25   Record 3 success batch#25 cases from legacy"
	@echo "  legacy-record-p0-success-batch26   Record 3 success batch#26 cases from legacy"
	@echo "  legacy-record-p0-success-batch27   Record 4 success batch#27 cases from legacy"
	@echo "  py-up                Start Python rewrite (stub)"
	@echo "  py-down              Stop Python rewrite (stub)"
	@echo "  py-check-p0          Run P0 against Python"
	@echo "  py-check-p0-dev      Run P0 against Python (dev auth env)"
	@echo "  py-check-p0-dev-minimal  Reset DB(minimal)+run P0"
	@echo "  py-diff-p0           Diff Python vs legacy"
	@echo "  py-check-p0-success  Run P0 success against Python"
	@echo "  py-check-p0-success-dev  Run P0 success (dev auth env)"
	@echo "  py-check-p0-success-dev-reset  Reset DB(success)+run P0 success"
	@echo "  py-check-p0-success-batch18      Check same 4 success batch#18 cases on rewrite"
	@echo "  py-diff-p0-success-batch18       Diff rewrite vs legacy for same 4 success batch#18 cases"
	@echo "  py-check-p0-success-batch19      Check same 5 success batch#19 cases on rewrite"
	@echo "  py-diff-p0-success-batch19       Diff rewrite vs legacy for same 5 success batch#19 cases"
	@echo "  py-check-p0-success-batch20      Check same 4 success batch#20 cases on rewrite"
	@echo "  py-diff-p0-success-batch20       Diff rewrite vs legacy for same 4 success batch#20 cases"
	@echo "  py-check-p0-success-batch21      Check same 5 success batch#21 cases on rewrite"
	@echo "  py-diff-p0-success-batch21       Diff rewrite vs legacy for same 5 success batch#21 cases"
	@echo "  py-check-p0-success-batch22      Check same 5 success batch#22 cases on rewrite"
	@echo "  py-diff-p0-success-batch22       Diff rewrite vs legacy for same 5 success batch#22 cases"
	@echo "  py-check-p0-success-batch23      Check same 5 success batch#23 cases on rewrite"
	@echo "  py-diff-p0-success-batch23       Diff rewrite vs legacy for same 5 success batch#23 cases"
	@echo "  py-check-p0-success-batch24      Check same 5 success batch#24 cases on rewrite"
	@echo "  py-diff-p0-success-batch24       Diff rewrite vs legacy for same 5 success batch#24 cases"
	@echo "  py-check-p0-success-batch25      Check same 3 success batch#25 cases on rewrite"
	@echo "  py-diff-p0-success-batch25       Diff rewrite vs legacy for same 3 success batch#25 cases"
	@echo "  py-check-p0-success-batch26      Check same 3 success batch#26 cases on rewrite"
	@echo "  py-diff-p0-success-batch26       Diff rewrite vs legacy for same 3 success batch#26 cases"
	@echo "  py-check-p0-success-batch27      Check same 4 success batch#27 cases on rewrite"
	@echo "  py-diff-p0-success-batch27       Diff rewrite vs legacy for same 4 success batch#27 cases"
	@echo "  legacy-record-p0-quality-batch1  Record 6 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch1       Check same 6 cases on rewrite"
	@echo "  py-diff-p0-quality-batch1        Diff rewrite vs legacy for same 6 cases"
	@echo "  legacy-record-p0-quality-batch2  Record 2 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch2       Check same 2 cases on rewrite"
	@echo "  py-diff-p0-quality-batch2        Diff rewrite vs legacy for same 2 cases"
	@echo "  legacy-record-p0-quality-batch3  Record 4 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch3       Check same 4 cases on rewrite"
	@echo "  py-diff-p0-quality-batch3        Diff rewrite vs legacy for same 4 cases"
	@echo "  legacy-record-p0-quality-batch4  Record 2 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch4       Check same 2 cases on rewrite"
	@echo "  py-diff-p0-quality-batch4        Diff rewrite vs legacy for same 2 cases"
	@echo "  legacy-record-p0-quality-batch5  Record 8 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch5       Check same 8 cases on rewrite"
	@echo "  py-diff-p0-quality-batch5        Diff rewrite vs legacy for same 8 cases"
	@echo "  legacy-record-p0-quality-batch6  Record 2 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch6       Check same 2 cases on rewrite"
	@echo "  py-diff-p0-quality-batch6        Diff rewrite vs legacy for same 2 cases"
	@echo "  legacy-record-p0-quality-batch7  Record 1 P0 quality case from legacy"
	@echo "  py-check-p0-quality-batch7       Check same 1 case on rewrite"
	@echo "  py-diff-p0-quality-batch7        Diff rewrite vs legacy for same 1 case"
	@echo "  legacy-record-p0-quality-batch8  Record 7 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch8       Check same 7 cases on rewrite"
	@echo "  py-diff-p0-quality-batch8        Diff rewrite vs legacy for same 7 cases"
	@echo "  legacy-record-p0-quality-batch9  Record 5 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch9       Check same 5 cases on rewrite"
	@echo "  py-diff-p0-quality-batch9        Diff rewrite vs legacy for same 5 cases"
	@echo "  legacy-record-p0-quality-batch10 Record 7 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch10      Check same 7 cases on rewrite"
	@echo "  py-diff-p0-quality-batch10       Diff rewrite vs legacy for same 7 cases"
	@echo "  legacy-record-p0-quality-batch11 Record 1 P0 quality case from legacy"
	@echo "  py-check-p0-quality-batch11      Check same 1 case on rewrite"
	@echo "  py-diff-p0-quality-batch11       Diff rewrite vs legacy for same 1 case"
	@echo "  legacy-record-p0-quality-batch12 Record 5 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch12      Check same 5 cases on rewrite"
	@echo "  py-diff-p0-quality-batch12       Diff rewrite vs legacy for same 5 cases"
	@echo "  legacy-record-p0-quality-batch13 Record 8 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch13      Check same 8 cases on rewrite"
	@echo "  py-diff-p0-quality-batch13       Diff rewrite vs legacy for same 8 cases"
	@echo "  legacy-record-p0-quality-batch14 Record 9 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch14      Check same 9 cases on rewrite"
	@echo "  py-diff-p0-quality-batch14       Diff rewrite vs legacy for same 9 cases"
	@echo "  legacy-record-p0-quality-batch15 Record 2 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch15      Check same 2 cases on rewrite"
	@echo "  py-diff-p0-quality-batch15       Diff rewrite vs legacy for same 2 cases"
	@echo "  legacy-record-p0-quality-batch16 Record 8 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch16      Check same 8 cases on rewrite"
	@echo "  py-diff-p0-quality-batch16       Diff rewrite vs legacy for same 8 cases"
	@echo "  legacy-record-p0-quality-batch17 Record 12 P0 quality cases from legacy"
	@echo "  py-check-p0-quality-batch17      Check same 12 cases on rewrite"
	@echo "  py-diff-p0-quality-batch17       Diff rewrite vs legacy for same 12 cases"

.PHONY: miniapp-tsv
miniapp-tsv:
	python3 $(BACKEND_ROOT)/scripts/extract_miniapp_api_usage.py --repo-root $(CURDIR)

.PHONY: golden-miniapp
golden-miniapp: miniapp-tsv
	python3 $(BACKEND_ROOT)/scripts/generate_miniapp_golden_cases.py --repo-root $(CURDIR)
	python3 $(BACKEND_ROOT)/scripts/check_golden_miniapp_coverage.py --repo-root $(BACKEND_ROOT)

.PHONY: golden-p0-stubs
golden-p0-stubs:
	python3 $(BACKEND_ROOT)/scripts/generate_p0_golden_stubs.py --repo-root $(BACKEND_ROOT)

.PHONY: golden-p0-check
golden-p0-check:
	python3 $(BACKEND_ROOT)/scripts/check_golden_coverage.py --repo-root $(BACKEND_ROOT) --tier p0

.PHONY: golden-audit
golden-audit:
	python3 $(BACKEND_ROOT)/scripts/audit_golden_quality.py --project-root $(BACKEND_ROOT)

.PHONY: system-tasks
system-tasks:
	python3 $(BACKEND_ROOT)/scripts/generate_system_tasks_inventory.py --repo-root $(BACKEND_ROOT)

.PHONY: rewrite-status
rewrite-status:
	python3 $(BACKEND_ROOT)/scripts/generate_rewrite_backlog.py --project-root $(BACKEND_ROOT)
	python3 $(BACKEND_ROOT)/scripts/generate_rewrite_status_report.py --project-root $(BACKEND_ROOT)

.PHONY: seed-scan
seed-scan:
	python3 $(BACKEND_ROOT)/scripts/scan_seed_dependencies.py --repo-root $(BACKEND_ROOT)

.PHONY: seed-business
seed-business:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/04-seed-minimal-business.sql

.PHONY: seed-p0-quality-batch1
seed-p0-quality-batch1:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/06-seed-p0-quality-batch1.sql

.PHONY: seed-p0-quality-batch2
seed-p0-quality-batch2:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/07-seed-p0-quality-batch2.sql

.PHONY: seed-p0-quality-batch3
seed-p0-quality-batch3:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/08-seed-p0-quality-batch3.sql

.PHONY: seed-p0-quality-batch4
seed-p0-quality-batch4:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/09-seed-p0-quality-batch4.sql

.PHONY: seed-p0-quality-batch5
seed-p0-quality-batch5:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/10-seed-p0-quality-batch5.sql

.PHONY: seed-p0-quality-batch6
seed-p0-quality-batch6:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/11-seed-p0-quality-batch6.sql

.PHONY: seed-p0-quality-batch7
seed-p0-quality-batch7:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/12-seed-p0-quality-batch7.sql

.PHONY: seed-p0-quality-batch8
seed-p0-quality-batch8:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/13-seed-p0-quality-batch8.sql

.PHONY: seed-p0-quality-batch9
seed-p0-quality-batch9:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/14-seed-p0-quality-batch9.sql

.PHONY: seed-p0-quality-batch10
seed-p0-quality-batch10:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/15-seed-p0-quality-batch10.sql

.PHONY: seed-p0-quality-batch11
seed-p0-quality-batch11:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/16-seed-p0-quality-batch11.sql

.PHONY: seed-p0-quality-batch12
seed-p0-quality-batch12:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/17-seed-p0-quality-batch12.sql

.PHONY: seed-p0-quality-batch13
seed-p0-quality-batch13:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/18-seed-p0-quality-batch13.sql

.PHONY: seed-p0-quality-batch14
seed-p0-quality-batch14:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/19-seed-p0-quality-batch14.sql

.PHONY: seed-p0-quality-batch15
seed-p0-quality-batch15:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/20-seed-p0-quality-batch15.sql

.PHONY: seed-p0-quality-batch16
seed-p0-quality-batch16:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/21-seed-p0-quality-batch16.sql

.PHONY: seed-p0-quality-batch17
seed-p0-quality-batch17:
	docker exec -i xfq-rewrite-mysql mysql -uxfq -pxfq -D xfq_v2 < $(BACKEND_ROOT)/infra/mysql/22-seed-p0-quality-batch17.sql

.PHONY: db-reset-minimal
db-reset-minimal:
	@echo "Resetting MySQL database xfq_v2 (DROP+CREATE+IMPORT)..."
	docker exec -i xfq-rewrite-mysql mysql -uroot -proot -e "DROP DATABASE IF EXISTS xfq_v2; CREATE DATABASE xfq_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
	docker exec -i xfq-rewrite-mysql mysql -uroot -proot xfq_v2 < DDL_xfq_v2.sql
	docker exec -i xfq-rewrite-mysql mysql -uroot -proot xfq_v2 < $(BACKEND_ROOT)/infra/mysql/02-seed-minimal.sql
	docker exec -i xfq-rewrite-mysql mysql -uroot -proot xfq_v2 < $(BACKEND_ROOT)/infra/mysql/03-seed-dev-auth.sql

.PHONY: db-reset-success
db-reset-success: db-reset-minimal
	docker exec -i xfq-rewrite-mysql mysql -uroot -proot xfq_v2 < $(BACKEND_ROOT)/infra/mysql/05-seed-success.sql

.PHONY: db-reset-p0-quality-batch1
db-reset-p0-quality-batch1: db-reset-minimal seed-p0-quality-batch1

.PHONY: db-reset-p0-quality-batch2
db-reset-p0-quality-batch2: db-reset-minimal seed-p0-quality-batch2

.PHONY: db-reset-p0-quality-batch3
db-reset-p0-quality-batch3: db-reset-minimal seed-p0-quality-batch3

.PHONY: db-reset-p0-quality-batch4
db-reset-p0-quality-batch4: db-reset-minimal seed-p0-quality-batch4

.PHONY: db-reset-p0-quality-batch5
db-reset-p0-quality-batch5: db-reset-minimal seed-p0-quality-batch5

.PHONY: db-reset-p0-quality-batch6
db-reset-p0-quality-batch6: db-reset-minimal seed-p0-quality-batch6

.PHONY: db-reset-p0-quality-batch7
db-reset-p0-quality-batch7: db-reset-minimal seed-p0-quality-batch7

.PHONY: db-reset-p0-quality-batch8
db-reset-p0-quality-batch8: db-reset-minimal seed-p0-quality-batch8

.PHONY: db-reset-p0-quality-batch9
db-reset-p0-quality-batch9: db-reset-minimal seed-p0-quality-batch9

.PHONY: db-reset-p0-quality-batch10
db-reset-p0-quality-batch10: db-reset-minimal seed-p0-quality-batch10

.PHONY: db-reset-p0-quality-batch11
db-reset-p0-quality-batch11: db-reset-minimal seed-p0-quality-batch11

.PHONY: db-reset-p0-quality-batch12
db-reset-p0-quality-batch12: db-reset-minimal seed-p0-quality-batch12

.PHONY: db-reset-p0-quality-batch13
db-reset-p0-quality-batch13: db-reset-minimal seed-p0-quality-batch13

.PHONY: db-reset-p0-quality-batch14
db-reset-p0-quality-batch14: db-reset-minimal seed-p0-quality-batch14

.PHONY: db-reset-p0-quality-batch15
db-reset-p0-quality-batch15: db-reset-minimal seed-p0-quality-batch15

.PHONY: db-reset-p0-quality-batch16
db-reset-p0-quality-batch16: db-reset-minimal seed-p0-quality-batch16

.PHONY: db-reset-p0-quality-batch17
db-reset-p0-quality-batch17: db-reset-minimal seed-p0-quality-batch17

.PHONY: db-reset-p0-success-batch18
db-reset-p0-success-batch18: db-reset-success

.PHONY: db-reset-p0-success-batch19
db-reset-p0-success-batch19: db-reset-success

.PHONY: db-reset-p0-success-batch20
db-reset-p0-success-batch20: db-reset-success

.PHONY: db-reset-p0-success-batch21
db-reset-p0-success-batch21: db-reset-success

.PHONY: db-reset-p0-success-batch22
db-reset-p0-success-batch22: db-reset-success

.PHONY: db-reset-p0-success-batch23
db-reset-p0-success-batch23: db-reset-success

.PHONY: db-reset-p0-success-batch24
db-reset-p0-success-batch24: db-reset-success

.PHONY: db-reset-p0-success-batch25
db-reset-p0-success-batch25: db-reset-success

.PHONY: db-reset-p0-success-batch26
db-reset-p0-success-batch26: db-reset-success

.PHONY: db-reset-p0-success-batch27
db-reset-p0-success-batch27: db-reset-success

.PHONY: db-up
db-up:
	docker compose -f docker-compose.rewrite.yml up -d mysql redis

.PHONY: db-down
db-down:
	docker compose -f docker-compose.rewrite.yml down

.PHONY: legacy-up
legacy-up:
	DOCKER_BUILDKIT=0 docker compose -f docker-compose.rewrite.yml up -d --build mysql redis legacy-php

.PHONY: legacy-down
legacy-down:
	docker compose -f docker-compose.rewrite.yml stop legacy-php

.PHONY: legacy-record-p0
legacy-record-p0:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0 \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-dev
legacy-record-p0-dev:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	$(MAKE) legacy-record-p0

.PHONY: legacy-record-p0-dev-minimal
legacy-record-p0-dev-minimal: db-reset-minimal legacy-record-p0-dev

.PHONY: legacy-record-xc
legacy-record-xc:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0/stubs/xc \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success
legacy-record-p0-success:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-dev
legacy-record-p0-success-dev:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	$(MAKE) legacy-record-p0-success

.PHONY: legacy-record-p0-success-dev-reset
legacy-record-p0-success-dev-reset: db-reset-success legacy-record-p0-success-dev

.PHONY: legacy-record-p0-success-batch18
legacy-record-p0-success-batch18:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH18_CASES_DIR) \
	  $(P0_SUCCESS_BATCH18_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch19
legacy-record-p0-success-batch19:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH19_CASES_DIR) \
	  $(P0_SUCCESS_BATCH19_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch20
legacy-record-p0-success-batch20:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH20_CASES_DIR) \
	  $(P0_SUCCESS_BATCH20_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch21
legacy-record-p0-success-batch21:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH21_CASES_DIR) \
	  $(P0_SUCCESS_BATCH21_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch22
legacy-record-p0-success-batch22:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH22_CASES_DIR) \
	  $(P0_SUCCESS_BATCH22_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch23
legacy-record-p0-success-batch23:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH23_CASES_DIR) \
	  $(P0_SUCCESS_BATCH23_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch24
legacy-record-p0-success-batch24:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH24_CASES_DIR) \
	  $(P0_SUCCESS_BATCH24_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch25
legacy-record-p0-success-batch25:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH25_CASES_DIR) \
	  $(P0_SUCCESS_BATCH25_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch26
legacy-record-p0-success-batch26:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH26_CASES_DIR) \
	  $(P0_SUCCESS_BATCH26_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-success-batch27
legacy-record-p0-success-batch27:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH27_CASES_DIR) \
	  $(P0_SUCCESS_BATCH27_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch1
legacy-record-p0-quality-batch1:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH1_CASES_DIR) \
	  $(P0_QUALITY_BATCH1_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch2
legacy-record-p0-quality-batch2:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH2_CASES_DIR) \
	  $(P0_QUALITY_BATCH2_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch3
legacy-record-p0-quality-batch3:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH3_CASES_DIR) \
	  $(P0_QUALITY_BATCH3_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch4
legacy-record-p0-quality-batch4:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH4_CASES_DIR) \
	  $(P0_QUALITY_BATCH4_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch5
legacy-record-p0-quality-batch5:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH5_CASES_DIR) \
	  $(P0_QUALITY_BATCH5_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch6
legacy-record-p0-quality-batch6:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH6_CASES_DIR) \
	  $(P0_QUALITY_BATCH6_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch7
legacy-record-p0-quality-batch7:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH7_CASES_DIR) \
	  $(P0_QUALITY_BATCH7_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch8
legacy-record-p0-quality-batch8:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH8_CASES_DIR) \
	  $(P0_QUALITY_BATCH8_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch9
legacy-record-p0-quality-batch9:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH9_CASES_DIR) \
	  $(P0_QUALITY_BATCH9_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch10
legacy-record-p0-quality-batch10:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH10_CASES_DIR) \
	  $(P0_QUALITY_BATCH10_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch11
legacy-record-p0-quality-batch11:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH11_CASES_DIR) \
	  $(P0_QUALITY_BATCH11_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch12
legacy-record-p0-quality-batch12:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH12_CASES_DIR) \
	  $(P0_QUALITY_BATCH12_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch13
legacy-record-p0-quality-batch13:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH13_CASES_DIR) \
	  $(P0_QUALITY_BATCH13_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch14
legacy-record-p0-quality-batch14:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH14_CASES_DIR) \
	  $(P0_QUALITY_BATCH14_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch15
legacy-record-p0-quality-batch15:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH15_CASES_DIR) \
	  $(P0_QUALITY_BATCH15_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch16
legacy-record-p0-quality-batch16:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH16_CASES_DIR) \
	  $(P0_QUALITY_BATCH16_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: legacy-record-p0-quality-batch17
legacy-record-p0-quality-batch17:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH17_CASES_DIR) \
	  $(P0_QUALITY_BATCH17_CASES) \
	  --base-url http://127.0.0.1:18080 \
	  --record

.PHONY: py-up
py-up:
	DOCKER_BUILDKIT=0 docker compose -f docker-compose.rewrite.yml up -d --build rewrite-py

.PHONY: py-down
py-down:
	docker compose -f docker-compose.rewrite.yml stop rewrite-py

.PHONY: py-check-p0
py-check-p0:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0 \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-dev
py-check-p0-dev:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	$(MAKE) py-check-p0

.PHONY: py-check-p0-dev-minimal
py-check-p0-dev-minimal: db-reset-minimal py-check-p0-dev

.PHONY: py-check-p0-success
py-check-p0-success:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0_success \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-dev
py-check-p0-success-dev:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	$(MAKE) py-check-p0-success

.PHONY: py-check-p0-success-dev-reset
py-check-p0-success-dev-reset: db-reset-success py-check-p0-success-dev

.PHONY: py-check-p0-success-batch18
py-check-p0-success-batch18:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH18_CASES_DIR) \
	  $(P0_SUCCESS_BATCH18_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch19
py-check-p0-success-batch19:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH19_CASES_DIR) \
	  $(P0_SUCCESS_BATCH19_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch20
py-check-p0-success-batch20:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH20_CASES_DIR) \
	  $(P0_SUCCESS_BATCH20_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch21
py-check-p0-success-batch21:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH21_CASES_DIR) \
	  $(P0_SUCCESS_BATCH21_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch22
py-check-p0-success-batch22:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH22_CASES_DIR) \
	  $(P0_SUCCESS_BATCH22_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch23
py-check-p0-success-batch23:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH23_CASES_DIR) \
	  $(P0_SUCCESS_BATCH23_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch24
py-check-p0-success-batch24:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH24_CASES_DIR) \
	  $(P0_SUCCESS_BATCH24_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch25
py-check-p0-success-batch25:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH25_CASES_DIR) \
	  $(P0_SUCCESS_BATCH25_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch26
py-check-p0-success-batch26:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH26_CASES_DIR) \
	  $(P0_SUCCESS_BATCH26_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-success-batch27
py-check-p0-success-batch27:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH27_CASES_DIR) \
	  $(P0_SUCCESS_BATCH27_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch1
py-check-p0-quality-batch1:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH1_CASES_DIR) \
	  $(P0_QUALITY_BATCH1_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch2
py-check-p0-quality-batch2:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH2_CASES_DIR) \
	  $(P0_QUALITY_BATCH2_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch3
py-check-p0-quality-batch3:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH3_CASES_DIR) \
	  $(P0_QUALITY_BATCH3_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch4
py-check-p0-quality-batch4:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH4_CASES_DIR) \
	  $(P0_QUALITY_BATCH4_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch5
py-check-p0-quality-batch5:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH5_CASES_DIR) \
	  $(P0_QUALITY_BATCH5_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch6
py-check-p0-quality-batch6:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH6_CASES_DIR) \
	  $(P0_QUALITY_BATCH6_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch7
py-check-p0-quality-batch7:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH7_CASES_DIR) \
	  $(P0_QUALITY_BATCH7_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch8
py-check-p0-quality-batch8:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH8_CASES_DIR) \
	  $(P0_QUALITY_BATCH8_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch9
py-check-p0-quality-batch9:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH9_CASES_DIR) \
	  $(P0_QUALITY_BATCH9_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch10
py-check-p0-quality-batch10:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH10_CASES_DIR) \
	  $(P0_QUALITY_BATCH10_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch11
py-check-p0-quality-batch11:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH11_CASES_DIR) \
	  $(P0_QUALITY_BATCH11_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch12
py-check-p0-quality-batch12:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH12_CASES_DIR) \
	  $(P0_QUALITY_BATCH12_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch13
py-check-p0-quality-batch13:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH13_CASES_DIR) \
	  $(P0_QUALITY_BATCH13_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch14
py-check-p0-quality-batch14:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH14_CASES_DIR) \
	  $(P0_QUALITY_BATCH14_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch15
py-check-p0-quality-batch15:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH15_CASES_DIR) \
	  $(P0_QUALITY_BATCH15_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch16
py-check-p0-quality-batch16:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH16_CASES_DIR) \
	  $(P0_QUALITY_BATCH16_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-check-p0-quality-batch17
py-check-p0-quality-batch17:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH17_CASES_DIR) \
	  $(P0_QUALITY_BATCH17_CASES) \
	  --base-url http://127.0.0.1:28080

.PHONY: py-diff-p0
py-diff-p0:
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(BACKEND_ROOT)/docs/rewrite/golden/cases/p0 \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch18
py-diff-p0-success-batch18:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH18_CASES_DIR) \
	  $(P0_SUCCESS_BATCH18_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch19
py-diff-p0-success-batch19:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH19_CASES_DIR) \
	  $(P0_SUCCESS_BATCH19_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch20
py-diff-p0-success-batch20:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH20_CASES_DIR) \
	  $(P0_SUCCESS_BATCH20_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch21
py-diff-p0-success-batch21:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH21_CASES_DIR) \
	  $(P0_SUCCESS_BATCH21_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch22
py-diff-p0-success-batch22:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH22_CASES_DIR) \
	  $(P0_SUCCESS_BATCH22_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch23
py-diff-p0-success-batch23:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH23_CASES_DIR) \
	  $(P0_SUCCESS_BATCH23_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch24
py-diff-p0-success-batch24:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH24_CASES_DIR) \
	  $(P0_SUCCESS_BATCH24_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch25
py-diff-p0-success-batch25:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH25_CASES_DIR) \
	  $(P0_SUCCESS_BATCH25_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch26
py-diff-p0-success-batch26:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH26_CASES_DIR) \
	  $(P0_SUCCESS_BATCH26_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-success-batch27
py-diff-p0-success-batch27:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_SUCCESS_BATCH27_CASES_DIR) \
	  $(P0_SUCCESS_BATCH27_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch1
py-diff-p0-quality-batch1:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH1_CASES_DIR) \
	  $(P0_QUALITY_BATCH1_DIFF_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch2
py-diff-p0-quality-batch2:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH2_CASES_DIR) \
	  $(P0_QUALITY_BATCH2_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch3
py-diff-p0-quality-batch3:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH3_CASES_DIR) \
	  $(P0_QUALITY_BATCH3_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch4
py-diff-p0-quality-batch4:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH4_CASES_DIR) \
	  $(P0_QUALITY_BATCH4_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch5
py-diff-p0-quality-batch5:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH5_CASES_DIR) \
	  $(P0_QUALITY_BATCH5_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch6
py-diff-p0-quality-batch6:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH6_CASES_DIR) \
	  $(P0_QUALITY_BATCH6_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch7
py-diff-p0-quality-batch7:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH7_CASES_DIR) \
	  $(P0_QUALITY_BATCH7_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch8
py-diff-p0-quality-batch8:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH8_CASES_DIR) \
	  $(P0_QUALITY_BATCH8_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch9
py-diff-p0-quality-batch9:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH9_CASES_DIR) \
	  $(P0_QUALITY_BATCH9_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch10
py-diff-p0-quality-batch10:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH10_CASES_DIR) \
	  $(P0_QUALITY_BATCH10_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch11
py-diff-p0-quality-batch11:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH11_CASES_DIR) \
	  $(P0_QUALITY_BATCH11_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch12
py-diff-p0-quality-batch12:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH12_CASES_DIR) \
	  $(P0_QUALITY_BATCH12_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch13
py-diff-p0-quality-batch13:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH13_CASES_DIR) \
	  $(P0_QUALITY_BATCH13_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch14
py-diff-p0-quality-batch14:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH14_CASES_DIR) \
	  $(P0_QUALITY_BATCH14_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch15
py-diff-p0-quality-batch15:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH15_CASES_DIR) \
	  $(P0_QUALITY_BATCH15_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch16
py-diff-p0-quality-batch16:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH16_CASES_DIR) \
	  $(P0_QUALITY_BATCH16_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080

.PHONY: py-diff-p0-quality-batch17
py-diff-p0-quality-batch17:
	API_TOKEN=dev.dev.dev API_USERID=1 \
	WINDOW_TOKEN=dev.dev.dev WINDOW_UUID=dev-window-uuid \
	SELFSERVICE_TOKEN=dev.dev.dev SELFSERVICE_NO=dev-selfservice-no \
	python3 $(BACKEND_ROOT)/scripts/run_golden_cases.py \
	  --cases-dir $(P0_QUALITY_BATCH17_CASES_DIR) \
	  $(P0_QUALITY_BATCH17_CASES) \
	  --base-url http://127.0.0.1:28080 \
	  --diff-against http://127.0.0.1:18080
