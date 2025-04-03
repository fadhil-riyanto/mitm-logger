<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

$app = AppFactory::create();
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/template');
$twig = new \Twig\Environment($loader);
$db_wrapper = new DB_WRAPPER();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/assets/{type}/{filename}', function (Request $request, Response $response, $args) {
    $filepath = __DIR__ . '/assets/' . $args['type'] . '/' . $args['filename'];

    if (file_exists($filepath) == false) {
        return $response->withStatus(404, 'File Not Found');
    }

    switch (pathinfo($filepath, PATHINFO_EXTENSION)) {
        case 'css':
            $response = $response->withHeader('Content-Type', 'text/css');
            break;
        case 'js':
            $response = $response->withHeader('Content-Type', 'application/javascript');
            break;
        default:
            $response = $response->withHeader('Content-Type', 'application/octet-stream');
            break;
    }

    $response->getBody()->write(file_get_contents($filepath));
    return $response;
});

$app->get('/', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $response->getBody()->write(
        $twig->render('index.html.twig', [
            'datas' => $db_wrapper->recv_all_recent_links()
        ])
    );
    return $response;
});

$app->get('/blocklist_fqdn', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $response->getBody()->write(
        $twig->render('blocklist_fqdn.html.twig', [
            'datas' => $db_wrapper->recv_all_blocked_fqdn()
        ])
    );
    return $response;
});

$app->get('/blocklist_path', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $response->getBody()->write(
        $twig->render('blocklist_path.html.twig', [
            'datas' => $db_wrapper->recv_all_blocked_path()
        ])
    );
    return $response;
});

$app->get('/server_stats', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $response->getBody()->write(
        $twig->render('server_stats.html.twig', [
            'datas' => $db_wrapper->recv_all_blocked_path()
        ])
    );
    return $response;
});

$app->get('/api/get_server_stats', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {

    $ret = $db_wrapper->get_db_state();

    $response->getBody()->write(json_encode($ret));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/mitm_log_history_changestate', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $data = $request->getQueryParams();

    if ($data["mode"] == 'block') {
        $ret = $db_wrapper->block_mitm_log_history_by($data['mitm_log_history_id']);
    } else {
        $ret = $db_wrapper->unblock_mitm_log_history_by($data['mitm_log_history_id']);
    }
    $response->getBody()->write(json_encode([
        'status' => $ret,
        'mitm_log_history_id' => $data['mitm_log_history_id'],
        'mode' => $data['mode']
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/fqdn_changestate', function (Request $request, Response $response, $args) use ($twig, $db_wrapper) {
    $data = $request->getQueryParams();

    if ($data["mode"] == 'block') {
        $ret = $db_wrapper->block_fqdn_by($data['fqdn_id']);
    } else {
        $ret = $db_wrapper->unblock_fqdn_by($data['fqdn_id']);
    }

    $response->getBody()->write(json_encode([
        'status' => $ret,
        'fqdn_id' => $data['fqdn_id'],
        'mode' => $data['mode']
    ]));
    return $response->withHeader('Content-Type', 'application/json');;
});



$app->run();
