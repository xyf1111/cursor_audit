<?php

namespace Lib\Vendor;

/**
 * 钉钉通知工具
 */
class DingtalkNotice
{
    const ROBOT_WEBHOOK = 'https://oapi.dingtalk.com/robot/send';
    // 测试 9895044bdbdc5f3e16e481321e484aad36daa028671385f4f5a295d8f98a102d
    // 正式 203ed6ebb91875c8a9e49d57b9140df768e31e47e6613ad734dd9b54e8beddbb
    const AI_STAT_TOKEN = '203ed6ebb91875c8a9e49d57b9140df768e31e47e6613ad734dd9b54e8beddbb';
    const AI_STAT_KEYWORD = 'AI统计';

    /**
     * 发送机器人请求
     *
     * @param array  $data 发送数据
     * @param string $access_token 机器人 token
     * @return string|false
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    public static function sendRobotRequest(array $data, string $access_token)
    {
        $url = sprintf('%s?access_token=%s', self::ROBOT_WEBHOOK, $access_token);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=utf-8']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

    /**
     * 根据 access_token 发送文本消息
     *
     * @param string $text
     * @param string $access_token
     * @param array  $at
     * @return string|false
     */
    public static function sendRebotUtil($text, $access_token, $at = [])
    {
        $data = [
            'msgtype' => 'text',
            'text' => [
                'content' => $text,
            ],
        ];

        if (!empty($at)) {
            $data['at'] = ['atMobiles' => $at];
        }

        return self::sendRobotRequest($data, $access_token);
    }

    /**
     * 根据 access_token 发送 markdown 消息
     *
     * @param string $title 标题
     * @param string $text 内容
     * @param string $access_token 机器人 token
     * @param array  $at at 手机号
     * @return string|false
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    public static function sendMarkdownUtil($title, $text, $access_token, $at = [])
    {
        $data = [
            'msgtype' => 'markdown',
            'markdown' => [
                'title' => $title,
                'text' => $text,
            ],
        ];

        if (!empty($at)) {
            $data['at'] = ['atMobiles' => $at];
        }

        return self::sendRobotRequest($data, $access_token);
    }

    /**
     * AI 统计通知
     *
     * @param string $text
     * @param array  $at
     * @return string|false
     */
    public static function sendAiStatNotice($text, $at = [])
    {
        return self::sendRebotUtil($text, self::AI_STAT_TOKEN, $at);
    }

    /**
     * AI 统计 markdown 通知
     *
     * @param string $title 标题
     * @param string $text 内容
     * @param array  $at at 手机号
     * @return string|false
     * @author chenjinhuang<chenjinhuang@zhibo8.com>
     * @date 2026-04-02
     */
    public static function sendAiStatMarkdownNotice($title, $text, $at = [])
    {
        return self::sendMarkdownUtil($title, $text, self::AI_STAT_TOKEN, $at);
    }
}
