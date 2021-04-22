<?php

$autoloader = __DIR__.'/../vendor/autoload.php';

if(!file_exists($autoloader)) {
    die( 'You must run `composer install` in the sample app directory' );
}

require $autoloader;

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use OTHelloWorld\Action\JoinAction;
use OTHelloWorld\Action\RoomAction;
use OTHelloWorld\Action\IndexAction;
use OTHelloWorld\Action\SessionAction;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Dotenv\Exception\InvalidPathException;
use OTHelloWorld\Action\Archive\GetAction;
use OTHelloWorld\Action\Archive\ListAction;
use OTHelloWorld\Action\Archive\StopAction;
use OTHelloWorld\Action\Archive\ViewAction;
use OTHelloWorld\Action\Archive\StartAction;

try {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (InvalidPathException $e) {
    // No-op, user is allowed to set things via a real environment variable as well
}


// PHP CLI webserver compatibility, serving static files
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config/global.php');
AppFactory::setContainer($builder->build());
$app = AppFactory::create();

$app->get('/', IndexAction::class)->setName('index');
$app->get('/session', SessionAction::class)->setName('session');
$app->get('/room/{name}', RoomAction::class)->setName('room');
$app->get('/room/{name}/join', JoinAction::class)->setName('room.join');
$app->get('/archive', ListAction::class)->setName('archive.list');
$app->map(['GET', 'POST'], '/archive/start', StartAction::class)->setName('archive.start');
$app->map(['GET', 'POST'], '/archive/{archiveId}', GetAction::class)->setName('archive.get');
$app->map(['GET', 'POST'], '/archive/{archiveId}/stop', StopAction::class)->setName('archive.stop');
$app->map(['GET', 'POST'], '/archive/{archiveId}/view', ViewAction::class)->setName('archive.view');

// return HTTP 200 for HTTP OPTIONS requests
$app->options('/:routes+', function(RequestInterface $request, ResponseInterface $response) {
    return $response;
});
$app->add(function (RequestInterface $request, $handler) use ($app) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
    ;
});

$app->run();
