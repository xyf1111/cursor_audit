<?php

use Phalcon\Mvc\Model;

/**
 * 每日每人 AI 使用统计模型
 */
class AiUserDailyStat extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $stat_date;

    /**
     * @var string
     */
    public $user_name;

    /**
     * @var int
     */
    public $request_count;

    /**
     * @var int
     */
    public $input_tokens;

    /**
     * @var int
     */
    public $output_tokens;

    /**
     * @var int
     */
    public $total_tokens;

    /**
     * @var string|null
     */
    public $first_used_at;

    /**
     * @var string|null
     */
    public $last_used_at;

    /**
     * @var string
     */
    public $created_at;

    /**
     * @var string
     */
    public $updated_at;

    /**
     * 初始化模型配置
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->setSource('ai_user_daily_stat');
    }

    /**
     * 查询指定日期的统计数据
     *
     * @param string $statDate
     * @return mixed
     */
    public static function findByStatDate(string $statDate)
    {
        return self::find([
            'conditions' => 'stat_date = :stat_date:',
            'bind' => [
                'stat_date' => $statDate,
            ],
        ]);
    }

    /**
     * 按日期同步统计结果
     *
     * @param string $statDate
     * @param array  $rows
     * @return bool
     */
    public static function syncByStatDate(string $statDate, array $rows): bool
    {
        $validUserNames = [];

        foreach ($rows as $row) {
            $validUserNames[] = $row['user_name'];

            if (!self::syncOneSummary($row)) {
                return false;
            }
        }

        return self::deleteStaleByStatDate($statDate, $validUserNames);
    }

    /**
     * 使用统计汇总数据填充模型
     *
     * @param array  $data
     * @param string $updatedAt
     * @return void
     */
    public function fillFromSummary(array $data, string $updatedAt): void
    {
        $this->stat_date = $data['stat_date'];
        $this->user_name = $data['user_name'];
        $this->request_count = (int) $data['request_count'];
        $this->input_tokens = (int) $data['input_tokens'];
        $this->output_tokens = (int) $data['output_tokens'];
        $this->total_tokens = (int) $data['total_tokens'];
        $this->first_used_at = $data['first_used_at'];
        $this->last_used_at = $data['last_used_at'];
        $this->updated_at = $updatedAt;
    }

    /**
     * 查询指定日期指定用户的统计数据
     *
     * @param string $statDate
     * @param string $userName
     * @return AiUserDailyStat|false
     */
    public static function findFirstByDateAndUserName(string $statDate, string $userName)
    {
        return self::findFirst([
            'conditions' => 'stat_date = :stat_date: AND user_name = :user_name:',
            'bind' => [
                'stat_date' => $statDate,
                'user_name' => $userName,
            ],
        ]);
    }

    /**
     * 同步单个用户的统计汇总
     *
     * @param array $row
     * @return bool
     */
    public static function syncOneSummary(array $row): bool
    {
        $now = date('Y-m-d H:i:s');
        $stat = self::findFirstByDateAndUserName($row['stat_date'], $row['user_name']);

        if (empty($stat)) {
            $stat = new self();
            $stat->created_at = $now;
        }

        $stat->fillFromSummary($row, $now);

        return (bool) $stat->save();
    }

    /**
     * 删除指定日期下不在有效用户名列表内的历史统计
     *
     * @param string $statDate
     * @param array  $validUserNames
     * @return bool
     */
    public static function deleteStaleByStatDate(string $statDate, array $validUserNames): bool
    {
        $stats = self::findByStatDate($statDate);
        $validUserNames = array_unique($validUserNames);

        foreach ($stats as $stat) {
            if (in_array($stat->user_name, $validUserNames, true)) {
                continue;
            }

            if (!$stat->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 导出统计结果数组
     *
     * @return array
     */
    public function toSummaryArray(): array
    {
        return [
            'stat_date' => $this->stat_date,
            'user_name' => $this->user_name,
            'request_count' => (int) $this->request_count,
            'input_tokens' => (int) $this->input_tokens,
            'output_tokens' => (int) $this->output_tokens,
            'total_tokens' => (int) $this->total_tokens,
            'first_used_at' => $this->first_used_at,
            'last_used_at' => $this->last_used_at,
        ];
    }
}
