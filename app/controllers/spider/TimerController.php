<?php

namespace CursorAudit\Controllers\Spider;

use CursorAudit\Service\AuditService;

/**
 * 定时任务控制器
 */
class TimerController extends ControllerBase
{
    const USER_DAY_DETAIL_BASE_URL = 'http://192.168.99.201:8193/admin/audit/userDayDetail';

    /**
     * 每天 18 点统计当天每个人的 AI 使用情况
     *
     * 支持通过 date 参数手动重跑指定日期，格式：Y-m-d
     *
     * @return void
     */
    public function dailyUserStatsAction(): void
    {
        $date = trim((string) $this->request->get('date', null, ''));
        $notify = (int) $this->request->get('notify', 'int', 1);
        $detail_base_url = $this->buildUserDayDetailBaseUrl();

        $service = new AuditService();
        $result = $service->generateDailyUserStats($date ?: null);

        if ($notify === 1 && ($result['status'] ?? '') === 'success') {
            $notify_result = $service->sendDailyUserStatsToDingTalk($result, $detail_base_url);
            $result['notify'] = $notify_result;

            if (($notify_result['status'] ?? '') !== 'success') {
                $result['status'] = 'error';
                $result['msg'] = '统计成功，但钉钉通知失败';
            }
        }

        $this->echoJson($result);
    }

    /**
     * 构建用户日详情地址
     *
     * @return string
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    private function buildUserDayDetailBaseUrl(): string
    {
        return self::USER_DAY_DETAIL_BASE_URL;
    }
}
