<?php

namespace CursorAudit\Service;

use AiAuditLog;
use AiUserDailyStat;
use Lib\Vendor\DingtalkNotice;
use Phalcon\Di;
use Phalcon\Di;
use Phalcon\Di;
use Phalcon\Di;

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
            return [
                'status' => 'error',
                'msg' => 'auditId 非法',
            ];
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
     * @param array $stats_result 统计结果
     * @return array
     */
    public function sendDailyUserStatsToDingTalk(array $stats_result): array
    {
        if (($stats_result['status'] ?? '') !== 'success') {
            return [
                'status' => 'error',
                'msg' => '统计结果不可用，无法发送钉钉通知',
            ];
        }

        $config = Di::getDefault()->getShared('config');
        $robot_token = trim((string) ($config->dingtalk->robotToken ?? ''));
        $keyword = trim((string) ($config->dingtalk->keyword ?? 'AI统计'));
        $webhook = trim((string) ($config->dingtalk->webhook ?? 'https://oapi.dingtalk.com/robot/send'));

        if ($robot_token === '') {
            return [
                'status' => 'error',
                'msg' => '钉钉机器人 token 未配置',
            ];
        }

        $message = $this->buildDailyStatsDingTalkMessage($stats_result, $keyword);
        if ($webhook !== 'https://oapi.dingtalk.com/robot/send') {
            return [
                'status' => 'error',
                'msg' => '当前仅支持默认钉钉机器人 webhook',
            ];
        }

        $response = DingtalkNotice::sendRebotUtil($message, $robot_token);
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
     * 构建钉钉通知内容
     *
     * @param array  $stats_result 统计结果
     * @param string $keyword 关键词
     * @return string
     */
    private function buildDailyStatsDingTalkMessage(array $stats_result, string $keyword): string
    {
        $data = $stats_result['data'] ?? [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $lines = [
            $keyword . ' 每日用户统计',
            '统计日期：' . ($data['stat_date'] ?? ''),
            '统计区间：' . ($data['start_time'] ?? '') . ' ~ ' . ($data['end_time'] ?? ''),
            '用户数：' . (int) ($data['row_count'] ?? count($rows)),
        ];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '%s：%d次，输入%d，输出%d，总计%d',
                (string) ($row['user_name'] ?? 'unknown'),
                (int) ($row['request_count'] ?? 0),
                (int) ($row['input_tokens'] ?? 0),
                (int) ($row['output_tokens'] ?? 0),
                (int) ($row['total_tokens'] ?? 0)
            );
        }

        return implode("\n", $lines);
    }
}
