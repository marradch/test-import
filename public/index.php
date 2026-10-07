<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\JsonResponse;
use FastRoute\RouteCollector;
use App\Service\ImportService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$required = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];

foreach ($required as $key) {
    if (empty($_ENV[$key])) {
        $response = new JsonResponse(['error' => 'Missing required environment variable'], 500);
        http_response_code($response->getStatusCode());
        echo $response->getBody();
        die;
    }
}

// Создание PSR-7 запроса
$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

// Настройка маршрутов
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', ['App\Controller\ImportController', 'index']);
});

// Парсинг URL
$httpMethod = $request->getMethod();
$uri = $request->getUri()->getPath();
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $response = new JsonResponse(['error' => 'Not Found'], 404);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $response = new JsonResponse(['error' => 'Method Not Allowed'], 405);
        break;

    case FastRoute\Dispatcher::FOUND:
        $pdo = new PDO(
            sprintf(
                '%s:host=%s;port=%s;dbname=%s',
                $_ENV['DB_CONNECTION'],
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            ),
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $service = new ImportService($pdo);

        [$class, $method] = $routeInfo[1];
        $vars = $routeInfo[2];
        $controller = new $class($service);
        $response = $controller->$method($request, $vars);
        break;
}