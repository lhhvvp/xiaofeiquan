<?php

namespace app\selfservice\listener;

use app\common\model\WriteErrorLog;

class ErrorReportEvent
{
    public function handle($paramData)
    {
        // 事件监听处理
        WriteErrorLog::ErrorReportLog($paramData);
    }
}