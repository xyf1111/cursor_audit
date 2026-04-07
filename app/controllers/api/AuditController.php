<?php

namespace CursorAudit\Controllers\Api;

use CursorAudit\Service\AuditService;

/**
 * 审计接口控制器
 *
 * @property \Phalcon\Http\Request $request
 *
 * @author GPT-5.4
 * @date 2026-03-27
 */
class AuditController extends ControllerBase
{
    /**
     * 兼容旧路由，默认按 prompt 创建
     *
     * @return void
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function indexAction(): void
    {
        $this->promptAction();
    }

    /**
     * 创建 prompt 审计记录
     *
     * @return void
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function promptAction(): void
    {
        if (!$this->request->isPost()) {
            $this->echoJson([
                'status' => 'error',
                'msg' => '仅支持 POST 请求',
            ]);
        }

        $params = $this->getPostBody();
        if (empty($params['machineId']) || empty($params['prompt'])) {
            $this->echoJson([
                'status' => 'error',
                'msg' => 'machineId 和 prompt 为必填项',
            ]);
        }

        $service = new AuditService();
        $this->echoJson($service->savePromptLog($params));
    }

    /**
     * 回填 response 审计内容
     *
     * @return void
     * @author GPT-5.4
     * @date 2026-03-27
     */
    public function responseAction(): void
    {
        if (!$this->request->isPost()) {
            $this->echoJson([
                'status' => 'error',
                'msg' => '仅支持 POST 请求',
            ]);
        }

        $params = $this->getPostBody();
        if (empty($params['auditId'])) {
            $this->echoJson([
                'status' => 'error',
                'msg' => 'auditId 为必填项',
            ]);
        }

        $service = new AuditService();
        $this->echoJson($service->saveResponseLog($params));
    }
}
