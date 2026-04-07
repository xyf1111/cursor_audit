<?php

date_default_timezone_set('Asia/Shanghai');

define('INDEX_START_TIME', microtime(true));

use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\View;

ini_set('display_errors', 'On');
error_reporting(E_ALL ^ E_DEPRECATED);

$debug = empty($_REQUEST['_debug']) ? '' : $_REQUEST['_debug'];

try {
    define('APP_PATH', realpath('..') . '/app/');

    $config = new ConfigIni(APP_PATH . 'config/config.ini');

    $loader = new Loader();
    $loader->registerDirs([
        $config->phalcon->controllersDir,
        $config->phalcon->modelsDir,
        $config->phalcon->serviceDir,
    ]);

    $loader->registerNamespaces([
        'Lib' => APP_PATH . 'lib',
        'CursorAudit\Controllers' => APP_PATH . 'controllers',
        'CursorAudit\Controllers\Api' => APP_PATH . 'controllers/api',
        'CursorAudit\Controllers\Spider' => APP_PATH . 'controllers/spider',
        'CursorAudit\Controllers\Admin' => APP_PATH . 'controllers/admin',
        'CursorAudit\Service' => APP_PATH . 'service',
    ]);
    $loader->register();

    $di = new FactoryDefault();

    $di->set('router', function () {
        return require APP_PATH . 'config/routes.php';
    }, true);

    $di->set('db', function () use ($config) {
        return new DbAdapter([
            'host' => $config->database->host,
            'username' => $config->database->username,
            'password' => $config->database->password,
            'dbname' => $config->database->dbname,
            'charset' => 'utf8mb4',
        ]);
    });

    $di->set('config', function () use ($config) {
        return $config;
    });

    $di->set('view', function () use ($config) {
        $view = new View();
        $view->setViewsDir($config->phalcon->viewsDir);
        return $view;
    });

    $application = new Application($di);
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $response = $application->handle($request_uri);

    if ($response !== false) {
        $response->send();
    }
} catch (\Exception $e) {
    if (!empty($debug)) {
        echo json_encode([
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'timestamp' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => 'Server Error',
        ], JSON_UNESCAPED_UNICODE);
    }
}
