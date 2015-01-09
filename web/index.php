<?php

$autoloader = __DIR__.'/../vendor/autoload.php';

if(!file_exists($autoloader)) {
    die( 'You must run `composer install` in the sample app directory' );
}

require $autoloader;

use Slim\Slim;
use Gregwar\Cache\Cache;


use OpenTok\OpenTok;
use OpenTok\Role;
use OpenTok\MediaMode;

// PHP CLI webserver compatibility, serving static files
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

// Verify that the API Key and API Secret are defined
if (!(getenv('API_KEY') && getenv('API_SECRET'))) {
    die('You must define an API_KEY and API_SECRET in the run-demo file');
}

// Instantiate a slim app
$app = new Slim(array(
        'templates.path' => __DIR__.'/../templates',
        'view' => new \Slim\Views\Twig()
));

// Intialize a cache, store it in the app container
$app->container->singleton('cache', function() {
        return new Cache;
});

// Initialize OpenTok instance, store it in the app contianer
$app->container->singleton('opentok', function () {
        return new OpenTok(getenv('API_KEY'), getenv('API_SECRET'));
});

// Store the API Key in the app container
$app->apiKey = getenv('API_KEY');

// If a sessionId has already been created, retrieve it from the cache
$sessionId = $app->cache->getOrCreate('sessionId', array(), function () use ($app) {
        // If the sessionId hasn't been created, create it now and store it
        $session = $app->opentok->createSession(array(
                'mediaMode' => MediaMode::ROUTED
        ));
        return $session->getSessionId();
});

// Route to return the SessionID and token as a json
$app->get('/session', 'cors', function () use ($app, $sessionId) {

        $token = $app->opentok->generateToken($sessionId);

        $responseData = array(
            'apiKey' => $app->apiKey,
            'sessionId' => $sessionId,
            'token'=>$token
        );

        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($responseData);
});

// Start Archiving and return the Archive ID
$app->post('/start/:sessionId', 'cors', function ($sessionId) use ($app) {
        $archive = $app->opentok->startArchive($sessionId, "Getting Started Sample Archive");
        $app->response->headers->set('Content-Type', 'application/json');

        $responseData = array('archive' => $archive);
        echo json_encode($responseData);
});

// Stop Archiving and return the Archive ID
$app->post('/stop/:archiveId', 'cors', function ($archiveId) use ($app) {
        $archive = $app->opentok->stopArchive($archiveId);
        $app->response->headers->set('Content-Type', 'application/json');

        $responseData = array('archive' => $archive);
        echo json_encode($responseData);
});


// Download the archive
$app->get('/view/:archiveId', 'cors', function ($archiveId) use ($app) {
        $archive = $app->opentok->getArchive($archiveId);

        if ($archive->status=='available')
            $app->redirect($archive->url);
        else {
            $app->render('view.html', array(
                    'id' => $archive->id,
                    'status' => $archive->status,
                    'url' => $archive->url
            ));
        }
});


// Enable CORS functionality
function cors() {
    // Allow from any origin
    if (isset( $_SERVER['HTTP_ORIGIN'])) {
        header( "Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}" );
        header( 'Access-Control-Allow-Credentials: true' );
        header( 'Access-Control-Max-Age: 86400' );    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    }
}

// return HTTP 200 for HTTP OPTIONS requests
$app->map('/:x+', function($x) {
        http_response_code( 200 );
})->via('OPTIONS');

$app->run();
