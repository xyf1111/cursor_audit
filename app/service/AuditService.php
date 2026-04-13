<?php

namespace CursorAudit\Service;

use AiAuditLog;
use AiUserDailyStat;
use Lib\Vendor\DingtalkNotice;

/**
 * 审计业务服务
 *
 * @author GPT-5.4
 * @date 2026-03-27
 */
class AuditService
{
    /**
     * 保存 prompt 审计记录
     *
     * @param array $params 请求参数
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function savePromptLog(array $params): array
    {
        $data = [
            'trace_id' => $this->buildTraceId($params),
            'session_id' => $this->sanitizeStr($params['sessionId'] ?? '', 128),
            'cursor_trace_id' => $this->sanitizeStr($params['cursorTraceId'] ?? '', 128),
            'machine_id' => $this->sanitizeStr($params['machineId'] ?? '', 100),
            'user_name' => $this->sanitizeStr($params['userName'] ?? '', 200),
            'timestamp' => $this->sanitizeStr($params['timestamp'] ?? gmdate('Y-m-d\TH:i:s\Z'), 50),
            'event_type' => $this->sanitizeStr($params['eventType'] ?? 'chat_prompt', 50),
            'prompt' => trim((string) ($params['prompt'] ?? '')),
            'file_path' => $this->sanitizeStr($params['filePath'] ?? '', 500),
            'project_name' => $this->sanitizeStr($params['projectName'] ?? '', 200),
            'model_name' => $this->sanitizeStr($params['modelName'] ?? '', 200),
            'input_tokens' => (int) ($params['inputTokens'] ?? 0),
            'output_tokens' => (int) ($params['outputTokens'] ?? 0),
        ];

        $log = AiAuditLog::createPrompt($data);
        if (empty($log)) {
            return [
                'status' => 'error',
                'msg' => '保存失败',
            ];
        }

        return [
            'status' => 'success',
            'msg' => 'ok',
            'data' => [
                'audit_id' => (int) $log->id,
                'trace_id' => $log->trace_id,
            ],
        ];
    }

    /**
     * 回填 response 审计记录
     *
     * @param array $params 请求参数
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function saveResponseLog(array $params): array
    {
        $audit_id = (int) ($params['auditId'] ?? 0);
        if ($audit_id <= 0) {
            $cursor_trace_id = $this->sanitizeStr((string) ($params['cursorTraceId'] ?? ''), 128);
            if ($cursor_trace_id === '') {
                return [
                    'status' => 'error',
                    'msg' => 'auditId 与 cursorTraceId 至少填写一项',
                ];
            }

            $found = AiAuditLog::findLatestByCursorTraceId($cursor_trace_id);
            if (empty($found)) {
                return [
                    'status' => 'error',
                    'msg' => '未找到 cursorTraceId 对应记录',
                ];
            }

            $audit_id = (int) $found->id;
        }

        $response_at = $this->sanitizeStr($params['responseAt'] ?? date('Y-m-d H:i:s'), 50);
        $request_finished_at = $this->sanitizeStr($params['requestFinishedAt'] ?? $response_at, 50);
        $data = [
            'response' => trim((string) ($params['response'] ?? '')),
            'response_event_type' => $this->sanitizeStr($params['responseEventType'] ?? 'chat_response', 50),
            'response_status' => $this->sanitizeStr($params['responseStatus'] ?? 'success', 50),
            'output_tokens' => (int) ($params['outputTokens'] ?? 0),
            'response_at' => $response_at,
            'request_finished_at' => $request_finished_at,
        ];

        $log = AiAuditLog::updateResponseById($audit_id, $data);
        if (empty($log)) {
            return [
                'status' => 'error',
                'msg' => '记录不存在或更新失败',
            ];
        }

        return [
            'status' => 'success',
            'msg' => 'ok',
            'data' => [
                'audit_id' => (int) $log->id,
            ],
        ];
    }

    /**
     * 生成每日用户统计
     *
     * @param string|null $date 统计日期
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function generateDailyUserStats(?string $date = null): array
    {
        $stat_date = $date ?: date('Y-m-d');
        $stat_time = strtotime($stat_date);
        if ($stat_time === false) {
            return [
                'status' => 'error',
                'msg' => 'date 参数格式错误',
            ];
        }

        $start_time = date('Y-m-d 00:00:00', $stat_time);
        if ($stat_date === date('Y-m-d')) {
            $end_time = date('Y-m-d H:i:s');
        } else {
            $end_time = date('Y-m-d 23:59:59', $stat_time);
        }

        $rows = AiAuditLog::summarizeUserDailyStats($stat_date, $start_time, $end_time);
        $success = AiUserDailyStat::syncByStatDate($stat_date, $rows);
        if (!$success) {
            return [
                'status' => 'error',
                'msg' => '统计落库失败',
            ];
        }

        return [
            'status' => 'success',
            'msg' => 'ok',
            'data' => [
                'stat_date' => $stat_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'row_count' => count($rows),
                'rows' => $rows,
            ],
        ];
    }

    /**
     * 将每日统计结果发送到钉钉群
     *
     * @param array  $stats_result      统计结果
     * @param string $detail_base_url   管理端用户日明细入口基址（可选，会写入正文）
     * @return array
     */
    public function sendDailyUserStatsToDingTalk(array $stats_result, string $detail_base_url = ''): array
    {
        if (($stats_result['status'] ?? '') !== 'success') {
            return [
                'status' => 'error',
                'msg' => '统计结果不可用，无法发送钉钉通知',
            ];
        }

        $keyword = DingtalkNotice::AI_STAT_KEYWORD;
        $title = $keyword . ' 每日用户统计';
        $message = $this->buildDailyStatsDingTalkMessage($stats_result, $keyword, $detail_base_url);
        $response = DingtalkNotice::sendAiStatMarkdownNotice($title, $message);
        if ($response === false || $response === '') {
            return [
                'status' => 'error',
                'msg' => '钉钉通知失败',
            ];
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded) || (int) ($decoded['errcode'] ?? -1) !== 0) {
            return [
                'status' => 'error',
                'msg' => '钉钉通知失败',
                'data' => [
                    'response' => $response,
                ],
            ];
        }

        return [
            'status' => 'success',
            'msg' => 'ok',
            'data' => [
                'response' => $decoded,
            ],
        ];
    }

    /**
     * 清洗字符串字段
     *
     * @param string $str 原始字符串
     * @param int    $max_len 最大长度
     * @return string
     * @author GPT-5.4
     * @date 2026-03-27
     */
    private function sanitizeStr(string $str, int $max_len = 500): string
    {
        return mb_substr(trim($str), 0, $max_len);
    }

    /**
     * 生成 trace_id
     *
     * @param array $params 请求参数
     * @return string
     * @author GPT-5.4
     * @date 2026-03-27
     */
    private function buildTraceId(array $params): string
    {
        if (!empty($params['traceId'])) {
            return $this->sanitizeStr((string) $params['traceId'], 64);
        }

        return md5(uniqid('audit_', true));
    }

    /**
     * 构建钉钉 markdown 通知内容
     *
     * @param array  $stats_result      统计结果
     * @param string $keyword           关键词
     * @param string $detail_base_url   明细入口基址（非空则追加一行）
     * @return string
     */
    private function buildDailyStatsDingTalkMessage(
        array $stats_result,
        string $keyword,
        string $detail_base_url = ''
    ): string {
        $data = $stats_result['data'] ?? [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $stat_date = (string) ($data['stat_date'] ?? '');
        $start_time = (string) ($data['start_time'] ?? '');
        $end_time = (string) ($data['end_time'] ?? '');

        $lines = [
            '# ' . $keyword . ' 每日用户统计',
            sprintf(
                '统计日期：%s 统计区间：%s ~ %s 用户数：%d',
                $stat_date,
                $start_time,
                $end_time,
                (int) ($data['row_count'] ?? count($rows))
            ),
        ];

        foreach ($rows as $row) {
            $user_name = (string) ($row['user_name'] ?? 'unknown');
            $lines[] = sprintf(
                '- %s：%d次，输入%d，输出%d，总计%d',
                $user_name,
                (int) ($row['request_count'] ?? 0),
                (int) ($row['input_tokens'] ?? 0),
                (int) ($row['output_tokens'] ?? 0),
                (int) ($row['total_tokens'] ?? 0)
            );

            $detail_url = $this->buildUserDayDetailUrl($detail_base_url, $stat_date, $user_name);
            if ($detail_url !== '') {
                $lines[] = '> **[点击查看该用户当日详情](' . $detail_url . ')**';
            }
        }

        return implode("\n\n", $lines);
    }

    /**
     * 构建用户日详情链接
     *
     * @param string $detail_base_url 详情页基址
     * @param string $stat_date       统计日期
     * @param string $user_name       用户名
     * @return string
     */
    private function buildUserDayDetailUrl(string $detail_base_url, string $stat_date, string $user_name): string
    {
        $detail_base_url = trim($detail_base_url);
        if ($detail_base_url === '' || $stat_date === '' || $user_name === '') {
            return '';
        }

        $query = http_build_query([
            'stat_date' => $stat_date,
            'user_name' => $user_name,
        ]);

        return $detail_base_url . (strpos($detail_base_url, '?') === false ? '?' : '&') . $query;
    }
}
