<?php

namespace CursorAudit\Controllers\Admin;

use AiAuditLog;

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
}
