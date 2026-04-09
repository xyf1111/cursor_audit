<?php

use Phalcon\Mvc\Model;

/**
 * AI 审计日志模型
 *
 * @author GPT-5.4
 * @date 2026-03-27
 */
class AiAuditLog extends Model
{
    public $id;
    public $trace_id;
    public $session_id;
    public $machine_id;
    public $user_name;
    public $timestamp;
    public $event_type;
    public $prompt;
    public $file_path;
    public $project_name;
    public $model_name;
    public $input_tokens;
    public $output_tokens;
    public $response;
    public $response_event_type;
    public $response_status;
    public $response_at;
    public $request_finished_at;
    public $created_at;
    public $updated_at;

    /**
     * 初始化模型
     *
     * @return void
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function initialize(): void
    {
        $this->setSource('ai_audit_log');
    }

    /**
     * 创建 prompt 审计记录
     *
     * @param array $data 审计数据
     * @return AiAuditLog|false
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function createPrompt(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $log = new self();
        $log->trace_id = $data['trace_id'];
        $log->session_id = $data['session_id'];
        $log->machine_id = $data['machine_id'];
        $log->user_name = $data['user_name'];
        $log->timestamp = $data['timestamp'];
        $log->event_type = $data['event_type'];
        $log->prompt = $data['prompt'];
        $log->file_path = $data['file_path'];
        $log->project_name = $data['project_name'];
        $log->model_name = $data['model_name'];
        $log->input_tokens = (int) $data['input_tokens'];
        $log->output_tokens = (int) $data['output_tokens'];
        $log->response = '';
        $log->response_event_type = '';
        $log->response_status = 'pending';
        $log->response_at = null;
        $log->request_finished_at = null;
        $log->created_at = $now;
        $log->updated_at = $now;

        if ($log->save()) {
            return $log;
        }

        return false;
    }

    /**
     * 根据 ID 更新 response 内容
     *
     * @param int   $audit_id 审计 ID
     * @param array $data 回填数据
     * @return AiAuditLog|false
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function updateResponseById(int $audit_id, array $data)
    {
        $log = self::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $audit_id,
            ],
        ]);

        if (empty($log)) {
            return false;
        }

        $log->response = $data['response'];
        $log->response_event_type = $data['response_event_type'];
        $log->response_status = $data['response_status'];
        $log->output_tokens = (int) $data['output_tokens'];
        $log->response_at = $data['response_at'];
        $log->request_finished_at = $data['request_finished_at'];
        $log->updated_at = date('Y-m-d H:i:s');

        if ($log->save()) {
            return $log;
        }

        return false;
    }

    /**
     * 查询详情
     *
     * @param int $id 记录 ID
     * @return AiAuditLog|false
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function findDetailById(int $id)
    {
        return self::findFirst([
            'conditions' => 'id = :id:',
            'bind' => [
                'id' => $id,
            ],
        ]);
    }

    /**
     * 后台分页查询
     *
     * @param array $filters 筛选条件
     * @param int   $page 页码
     * @param int   $page_size 每页大小
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function paginateByFilters(array $filters, int $page = 1, int $page_size = 20): array
    {
        $page = max(1, $page);
        $page_size = max(1, $page_size);
        $options = self::buildFilterOptions($filters);
        $offset = ($page - 1) * $page_size;

        $find_options = [
            'order' => 'id DESC',
            'limit' => $page_size,
            'offset' => $offset,
        ];

        $count_options = [];
        if (!empty($options['conditions'])) {
            $find_options['conditions'] = $options['conditions'];
            $find_options['bind'] = $options['bind'];
            $count_options['conditions'] = $options['conditions'];
            $count_options['bind'] = $options['bind'];
        }

        $list = self::find($find_options);
        $total = (int) self::count($count_options);
        $total_pages = (int) ceil($total / $page_size);
        if ($total_pages < 1) {
            $total_pages = 1;
        }

        return [
            'list' => $list,
            'page' => $page,
            'pageSize' => $page_size,
            'total' => $total,
            'totalPages' => $total_pages,
        ];
    }

    /**
     * 查询时间范围内的 prompt 审计记录
     *
     * @param string $start_time 开始时间
     * @param string $end_time 结束时间
     * @return mixed
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function findByCreatedAtRange(string $start_time, string $end_time)
    {
        return self::find([
            'conditions' => 'created_at >= :start_time: AND created_at <= :end_time: AND event_type = :event_type:',
            'bind' => [
                'start_time' => $start_time,
                'end_time' => $end_time,
                'event_type' => 'chat_prompt',
            ],
            'order' => 'id ASC',
        ]);
    }

    /**
     * 查询指定日期范围内指定用户的 prompt 审计记录
     *
     * @param string $user_name 用户名
     * @param string $start_time 开始时间
     * @param string $end_time 结束时间
     * @return mixed
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    public static function findByUserNameAndCreatedAtRange(string $user_name, string $start_time, string $end_time)
    {
        $conditions = [
            'created_at >= :start_time:',
            'created_at <= :end_time:',
            'event_type = :event_type:',
        ];
        $bind = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'event_type' => 'chat_prompt',
        ];

        if ($user_name === 'unknown') {
            $conditions[] = '(user_name = :user_name: OR user_name = :empty_user_name: OR user_name IS NULL)';
            $bind['user_name'] = $user_name;
            $bind['empty_user_name'] = '';
        } else {
            $conditions[] = 'user_name = :user_name:';
            $bind['user_name'] = $user_name;
        }

        return self::find([
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => 'id DESC',
        ]);
    }

    /**
     * 汇总指定日期用户统计
     *
     * @param string $stat_date 统计日期
     * @param string $start_time 开始时间
     * @param string $end_time 结束时间
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public static function summarizeUserDailyStats(string $stat_date, string $start_time, string $end_time): array
    {
        $logs = self::findByCreatedAtRange($start_time, $end_time);
        $summary_map = [];

        foreach ($logs as $log) {
            $user_name = trim((string) $log->user_name);
            if ($user_name === '') {
                $user_name = 'unknown';
            }

            if (!isset($summary_map[$user_name])) {
                $summary_map[$user_name] = [
                    'stat_date' => $stat_date,
                    'user_name' => $user_name,
                    'request_count' => 0,
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'total_tokens' => 0,
                    'first_used_at' => $log->created_at,
                    'last_used_at' => $log->created_at,
                ];
            }

            $summary_map[$user_name]['request_count']++;
            $summary_map[$user_name]['input_tokens'] += (int) $log->input_tokens;
            $summary_map[$user_name]['output_tokens'] += (int) $log->output_tokens;
            $summary_map[$user_name]['total_tokens'] += (int) $log->input_tokens + (int) $log->output_tokens;

            if ($log->created_at < $summary_map[$user_name]['first_used_at']) {
                $summary_map[$user_name]['first_used_at'] = $log->created_at;
            }
            if ($log->created_at > $summary_map[$user_name]['last_used_at']) {
                $summary_map[$user_name]['last_used_at'] = $log->created_at;
            }
        }

        return array_values($summary_map);
    }

    /**
     * 构建后台筛选条件
     *
     * @param array $filters 筛选条件
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    private static function buildFilterOptions(array $filters): array
    {
        $conditions = [];
        $bind = [];

        if (!empty($filters['user_name'])) {
            $conditions[] = 'user_name = :user_name:';
            $bind['user_name'] = $filters['user_name'];
        }

        if (!empty($filters['event_type'])) {
            $conditions[] = 'event_type = :event_type:';
            $bind['event_type'] = $filters['event_type'];
        }

        if (!empty($filters['project_name'])) {
            $conditions[] = 'project_name = :project_name:';
            $bind['project_name'] = $filters['project_name'];
        }

        if (!empty($filters['model_name'])) {
            $conditions[] = 'model_name = :model_name:';
            $bind['model_name'] = $filters['model_name'];
        }

        if (!empty($filters['start_date'])) {
            $conditions[] = 'created_at >= :start_date:';
            $bind['start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $conditions[] = 'created_at <= :end_date:';
            $bind['end_date'] = $filters['end_date'] . ' 23:59:59';
        }

        if (!empty($filters['keyword'])) {
            $conditions[] = '(prompt LIKE :keyword: OR response LIKE :keyword:)';
            $bind['keyword'] = '%' . $filters['keyword'] . '%';
        }

        return [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
        ];
    }
}
