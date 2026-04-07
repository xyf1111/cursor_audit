<?php

namespace CursorAudit\Controllers\Api;

use Phalcon\Mvc\Controller;

/**
 * API 控制器基类
 *
 * @property \Phalcon\Http\Request $request
 *
 * @author GPT-5.4
 * @date 2026-03-27
 */
class ControllerBase extends Controller
{
    /**
     * 输出 JSON 响应并终止
     *
     * @param array $data 响应数据
     * @return void
     * @author GPT-5.4
     * @date 2026-03-27
     */
    protected function echoJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 读取 POST 请求体
     *
     * @return array
     * @author GPT-5.4
     * @date 2026-03-27
     */
    protected function getPostBody(): array
    {
        $content_type = (string) $this->request->getHeader('Content-Type');

        if (strpos($content_type, 'application/json') !== false) {
            $data = json_decode((string) $this->request->getRawBody(), true);
            return is_array($data) ? $data : [];
        }

        $data = $this->request->getPost();
        return is_array($data) ? $data : [];
    }
}
