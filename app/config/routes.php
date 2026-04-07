<?php

$router = new Phalcon\Mvc\Router(false);

$router->add('/api/audit', [
    'namespace' => 'CursorAudit\Controllers\Api',
    'controller' => 'audit',
    'action' => 'index',
]);

$router->add('/api/audit/prompt', [
    'namespace' => 'CursorAudit\Controllers\Api',
    'controller' => 'audit',
    'action' => 'prompt',
]);

$router->add('/api/audit/response', [
    'namespace' => 'CursorAudit\Controllers\Api',
    'controller' => 'audit',
    'action' => 'response',
]);

$router->add('/api/:controller/:action/:params', [
    'namespace' => 'CursorAudit\Controllers\Api',
    'controller' => 1,
    'action' => 2,
    'params' => 3,
]);

$router->add('/api/:controller/:action', [
    'namespace' => 'CursorAudit\Controllers\Api',
    'controller' => 1,
    'action' => 2,
]);

$router->add('/spider/:controller/:action/:params', [
    'namespace' => 'CursorAudit\Controllers\Spider',
    'controller' => 1,
    'action' => 2,
    'params' => 3,
]);

$router->add('/spider/:controller/:action', [
    'namespace' => 'CursorAudit\Controllers\Spider',
    'controller' => 1,
    'action' => 2,
]);

$router->add('/admin/:controller/:action/:params', [
    'namespace' => 'CursorAudit\Controllers\Admin',
    'controller' => 1,
    'action' => 2,
    'params' => 3,
]);

$router->add('/admin/:controller/:action', [
    'namespace' => 'CursorAudit\Controllers\Admin',
    'controller' => 1,
    'action' => 2,
]);

$router->add('/admin/:controller', [
    'namespace' => 'CursorAudit\Controllers\Admin',
    'controller' => 1,
    'action' => 'index',
]);

$router->add('/', [
    'namespace' => 'CursorAudit\Controllers\Admin',
    'controller' => 'audit',
    'action' => 'index',
]);

return $router;
