<?php

$autoloader = __DIR__.'/../vendor/autoload.php';

if(!file_exists($autoloader)) {
    die( 'You must run `composer install` in the sample app directory' );
}

require $autoloader;

use Slim\Slim;
use ICanBoogie\Storage\FileStorage;

use OpenTok\OpenTok;
use OpenTok\MediaMode;

// PHP CLI webserver compatibility, serving static files
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

// Verify that the API Key and API Secret are defined
if (!(getenv('TOKBOX_API_KEY') && getenv('TOKBOX_SECRET'))) {
    die('You must define an TOKBOX_API_KEY and TOKBOX_SECRET in the run-demo file');
}

// Instantiate a Slim app
$app = new Slim(array(
    'log.enabled' => true,
    'templates.path' => __DIR__.'/../templates'
));

// IMPORTANT: storage is a variable that associates room names with unique unique sesssion IDs. 
// For simplicty, we use a extension called FileStorage to implement this logic.
// Generally speaking, a production application chooses a database system like MySQL, MongoDB, or Redis etc.
// The FileStorage transforms into a file where the name is a room name and its value is session ID.
$app->container->singleton('storage', function() use ($app) {
    return new FileStorage('storage');
});

// Initialize OpenTok instance, store it in the app contianer
$app->container->singleton('opentok', function () {
    return new OpenTok(getenv('TOKBOX_API_KEY'), getenv('TOKBOX_SECRET'));
});

// Store the API Key in the app container
$app->apiKey = getenv('TOKBOX_API_KEY');

$app->get('/', 'cors', function () use ($app) {
    $app->render('home.php');
});

/**
 * GET /session redirects to /room/session
 */
$app->get('/session', 'cors', function () use ($app) { 
    $app->redirect('/room/session');
});

/**
 * GET /room/:name
 */
$app->get('/room/:name', 'cors', function($name) use ($app) {

    // if a room name is already associated with a session ID
    if ($app->storage->exists($name)) {

        // fetch the sessionId from local storage
        $app->sessionId = $app->storage[$name];

        // generate token
        $token = $app->opentok->generateToken($app->sessionId);
        $responseData = array(
            'apiKey' => $app->apiKey,
            'sessionId' => $app->sessionId,
            'token'=>$token
        );

        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($responseData);
    }
    else {
        $session = $app->opentok->createSession(array(
            'mediaMode' => MediaMode::ROUTED
        ));

        // store the sessionId into local
        $app->storage[$name] = $session->getSessionId();
        
        // generate token
        $token = $app->opentok->generateToken($session->getSessionId());
        $responseData = array(
            'apiKey' => $app->apiKey,
            'sessionId' => $session->getSessionId(),
            'token'=>$token
        );

        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($responseData);
    }
});

/**
 * POST /archive/start
 */
$app->post('/archive/start', 'cors', function () use ($app) {
    $json = $app->request->getBody();
    $data = json_decode($json, true);
    $sessionId = $data['sessionId'];
    $archive = $app->opentok->startArchive($sessionId, 'Getting Started Sample Archive');
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

/**
 * POST /archive/:archiveId/stop
 */
$app->post('/archive/:archiveId/stop', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->stopArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

/**
 * GET /archive/:archiveId/view
 */
$app->get('/archive/:archiveId/view', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);
    if ($archive->status=='available') {
        $app->redirect($archive->url);
    }
    else {
        $app->render('view.php');
    }
});

/**
 * GET /archive/:archiveId
 */
$app->get('/archive/:archiveId', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

/**
 * GET /archive
 */
$app->get('/archive', 'cors', function() use ($app) {
    $offset = $app->request->get('offset') != null ? intval($app->request->get('offset')) : 0;
    $count = $app->request->get('count') != null ? intval($app->request->get('count')) : 1000;
    $archiveList = $app->opentok->listArchives($offset, $count);
    $archives = $archiveList->getItems();

    $result = array();
    foreach ($archives as $archive) {
        array_push($result, $archive->toJson());
    }
    echo json_encode($result);
});

// Enable CORS functionality
function cors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
}

// return HTTP 200 for HTTP OPTIONS requests
$app->map('/:routes+', 'cors', function($routes) {
    http_response_code( 200 );
})->via('OPTIONS');

// TODO: route to clear storage
$app->post('/session/clear', 'cors', function() use ($app) {
    if ($app->storage instanceof APCStorage) {
        $app->storage->clear();
    }
});

$app->run();
