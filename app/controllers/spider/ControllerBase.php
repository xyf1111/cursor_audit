<?php

namespace CursorAudit\Controllers\Spider;

use Phalcon\Mvc\Controller;

/**
 * Spider 模块基类控制器
 */
class ControllerBase extends Controller
{
    /**
     * 输出 JSON 响应并终止
     *
     * @param array $data
     * @return void
     */
    protected function echoJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
