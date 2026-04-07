<?php

namespace CursorAudit\Controllers\Admin;

use AiAuditLog;
use AiUserDailyStat;

/**
 * 审计后台页面
 */
class AuditController extends ControllerBase
{
    /**
     * 请求列表页
     *
     * @return void
     */
    public function indexAction(): void
    {
        $page = (int) $this->request->getQuery('page', 'int', 1);
        $pageSize = (int) $this->request->getQuery('page_size', 'int', 20);

        $filters = [
            'user_name' => trim((string) $this->request->getQuery('user_name', null, '')),
            'event_type' => trim((string) $this->request->getQuery('event_type', null, '')),
            'project_name' => trim((string) $this->request->getQuery('project_name', null, '')),
            'model_name' => trim((string) $this->request->getQuery('model_name', null, '')),
            'start_date' => trim((string) $this->request->getQuery('start_date', null, '')),
            'end_date' => trim((string) $this->request->getQuery('end_date', null, '')),
            'keyword' => trim((string) $this->request->getQuery('keyword', null, '')),
        ];

        $result = \AiAuditLog::paginateByFilters($filters, $page, $pageSize);

        $this->view->filters = $filters;
        $this->view->result = $result;
        $this->view->pick('admin/audit/index');
    }

    /**
     * 请求详情页
     *
     * @return void
     */
    public function detailAction(): void
    {
        $id = (int) $this->request->getQuery('id', 'int', 0);
        $detail = $id > 0 ? AiAuditLog::findDetailById($id) : false;

        if (empty($detail)) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        $this->view->detail = $detail;
        $this->view->pick('admin/audit/detail');
    }

    /**
     * 用户日明细页
     *
     * @return void
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    public function userDayDetailAction(): void
    {
        $stat_date = trim((string) $this->request->getQuery('stat_date', null, ''));
        $user_name = trim((string) $this->request->getQuery('user_name', null, ''));
        $summary = [];
        $detail_list = [];

        if ($stat_date !== '' && $user_name !== '' && $this->isValidStatDate($stat_date)) {
            $time_range = $this->buildStatDateRange($stat_date);
            $detail_records = AiAuditLog::findByUserNameAndCreatedAtRange($user_name, $time_range['start_time'], $time_range['end_time']);
            foreach ($detail_records as $detail) {
                $detail_list[] = $detail;
            }

            $daily_stat = AiUserDailyStat::findFirstByDateAndUserName($stat_date, $user_name);
            if (!empty($daily_stat)) {
                $summary = $daily_stat->toSummaryArray();
            } else {
                $summary = $this->buildSummaryFromLogs($detail_list, $stat_date, $user_name);
            }
        }

        if (empty($summary) && empty($detail_list)) {
            $this->response->setStatusCode(404, 'Not Found');
        }

        $this->view->stat_date = $stat_date;
        $this->view->user_name = $user_name;
        $this->view->summary = $summary;
        $this->view->detail_list = $detail_list;
        $this->view->pick('admin/audit/user_day_detail');
    }

    /**
     * 校验统计日期格式
     *
     * @param string $stat_date 统计日期
     * @return bool
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    private function isValidStatDate(string $stat_date): bool
    {
        $time = strtotime($stat_date);
        if ($time === false) {
            return false;
        }

        return date('Y-m-d', $time) === $stat_date;
    }

    /**
     * 构建统计日期查询区间
     *
     * @param string $stat_date 统计日期
     * @return array
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    private function buildStatDateRange(string $stat_date): array
    {
        $time = strtotime($stat_date);

        return [
            'start_time' => date('Y-m-d 00:00:00', $time),
            'end_time' => date('Y-m-d 23:59:59', $time),
        ];
    }

    /**
     * 根据明细构建汇总数据
     *
     * @param mixed  $detail_list 明细列表
     * @param string $stat_date 统计日期
     * @param string $user_name 用户名
     * @return array
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    private function buildSummaryFromLogs($detail_list, string $stat_date, string $user_name): array
    {
        $summary = [
            'stat_date' => $stat_date,
            'user_name' => $user_name,
            'request_count' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'first_used_at' => '',
            'last_used_at' => '',
        ];

        foreach ($detail_list as $detail) {
            $summary['request_count']++;
            $summary['input_tokens'] += (int) $detail->input_tokens;
            $summary['output_tokens'] += (int) $detail->output_tokens;
            $summary['total_tokens'] += (int) $detail->input_tokens + (int) $detail->output_tokens;

            if ($summary['first_used_at'] === '' || $detail->created_at < $summary['first_used_at']) {
                $summary['first_used_at'] = $detail->created_at;
            }
            if ($summary['last_used_at'] === '' || $detail->created_at > $summary['last_used_at']) {
                $summary['last_used_at'] = $detail->created_at;
            }
        }

        return $summary;
    }
}
