<?php

$autoloader = __DIR__.'/../vendor/autoload.php';

if(!file_exists($autoloader)) {
    die( 'You must run `composer install` in the sample app directory' );
}

require $autoloader;

use Slim\Slim;

use ICanBoogie\Storage\APCStorage;
use ICanBoogie\Storage\FileStorage;

use OpenTok\OpenTok;
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

// Instantiate a Slim app
$app = new Slim(array(
    'log.enabled' => true,
    'templates.path' => __DIR__.'/../templates'
));

// Return an info page for the root path
$app->get('/', cors, function () use ($app) {
  $app->render('home.php');
});

// Intialize storage interface wrapper, store it in a singleton
$app->container->singleton('storage', function() use ($app) {
    // If the SLIM_MODE environment variable is set to 'production' (like on Heroku) the APC is used as 
    // the storage backed. Otherwise (like running locally) the filesystem is used as the storage 
    // backend.
    $storage = null;
    $mode = $app->config('mode');
    if ($mode === 'production') {
        $storage = new APCStorage();
    } else {
        $storage = new FileStorage('storage');
    }
    return $storage;
});

// Initialize OpenTok instance, store it in the app contianer
$app->container->singleton('opentok', function () {
        return new OpenTok(getenv('API_KEY'), getenv('API_SECRET'));
});

// Store the API Key in the app container
$app->apiKey = getenv('API_KEY');

// If a sessionId has already been created, retrieve it from the storage 
$app->container->singleton('sessionId', function() use ($app) { 
    if ($app->storage->exists('sessionId')) { 
        return $app->storage->retrieve('sessionId'); 
    } 
 
    $session = $app->opentok->createSession(array( 
        'mediaMode' => MediaMode::ROUTED 
    )); 
    $app->storage->store('sessionId', $session->getSessionId()); 
    return $session->getSessionId(); 
}); 


$app->get('/session', 'cors', function () use ($app) { 
 
    $token = $app->opentok->generateToken($app->sessionId); 
 
    $responseData = array( 
        'apiKey' => $app->apiKey, 
        'sessionId' => $app->sessionId, 
        'token'=>$token 
    ); 
 
    $app->response->headers->set('Content-Type', 'application/json'); 
    echo json_encode($responseData); 
});

// Route to return the SessionID and token as a json
// GET /room/:name
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

// Start Archiving and return the archive
// POST /archive/start
$app->post('/archive/start', 'cors', function () use ($app) {
    $json = json_decode($app->request->getBody());
    $sessionId = $json->sessionId;
    $archive = $app->opentok->startArchive($sessionId, 'Getting Started Sample Archive');
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

// Stop Archiving and return the archive
// POST /archive/:/stop
$app->post('/archive/:archiveId/stop', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->stopArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

// View the archive
// GET /archive/:archiveId/view
$app->get('/archive/:archiveId/view', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);

    if ($archive->status=='available') {
        $app->redirect($archive->url);
    }
    else {
        $app->render('view.php');
    }
});

// Fetch an archive info specified by an archive ID
// GET /archive/:archiveId
$app->get('/archive/:archiveId', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});

// Enable CORS functionality
function cors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
}

// return HTTP 200 for HTTP OPTIONS requests
$app->map('/:x+', function($x) {
        http_response_code( 200 );
})->via('OPTIONS');

// TODO: route to clear storage
$app->post('/session/clear', function() use ($app) {
    if ($app->storage instanceof APCStorage) {
        $app->storage->clear();
    }
});

$app->run();
