<?php

namespace app\meituan\listener;

use app\common\model\WriteErrorLog;

class ErrorReportEvent
{
    public function handle($paramData)
    {
        // 事件监听处理
        WriteErrorLog::ErrorReportLog($paramData);
    }
}