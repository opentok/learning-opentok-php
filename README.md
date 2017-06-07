![logo](./tokbox-logo.png)

# OpenTok Getting Started Sample App

A simple server that uses the [OpenTok](https://tokbox.com/developer/)
[PHP SDK](https://github.com/opentok/Opentok-PHP-SDK) to create sessions,
generate tokens for those sessions, archive (or record) sessions, and download
those archives.

## Quick deploy to Heroku

Heroku is a PaaS (Platform as a Service) that can be used to deploy simple and small applications
for free. To easily deploy this repository to Heroku, sign up for a Heroku account and click this
button:

<a href="https://heroku.com/deploy?template=https://github.com/opentok/learning-opentok-php" target="_blank">
  <img src="https://www.herokucdn.com/deploy/button.png" alt="Deploy">
</a>

Heroku will prompt you to add your OpenTok API key and OpenTok API secret, which you can
obtain at the [TokBox Dashboard](https://dashboard.tokbox.com/keys).

## Requirements

- [Composer](https://getcomposer.org/)
- [Slim](https://www.slimframework.com/)

## Installation & Running on localhost

  1. Clone the app by running the command
  
		  git clone git@github.com:opentok/learning-opentok-php.git

  2. `cd` to the root directory.
  3. Run `composer install --ignore-platform-reqs` command to fetch and install all dependecies.
  4. Next, input your own API Key and API Secret into the `run-demo` script file:

      ```
      export TOKBOX_API_KEY=0000000
      export TOKBOX_SECRET=abcdef1234567890abcdef01234567890abcdef
      ```

  5. The run-demo file starts the PHP CLI development server (requires PHP >= 5.4) on port 8080. Start the server using the
run-demo script:

    `$ ./run-demo`

  6. Visit the URL <http://localhost:8080/session> in your browser. You should see a JSON response
containing the OpenTok API key, session ID, and token.

# Exploring the code

The `web/index.php` file contains routing for the web service. The rest of this tutorial discusses code in this file.

In order to navigate clients to a designated meeting spot, we associate the Session ID to a room name which is easier for people to recognize and pass. For simplicity, we use a local associated array to implement the association where the room name is the key and the Session ID is the value. For production applications, you may want to configure a persistence (such as a database) to achieve this functionality.

### Generate a Session and Token

The `GET /room/:name` route associates an OpenTok session with a "room" name. This route handles the passed room name and performs a check to determine whether the app should generate a new session ID or retrieve a session ID from the [PHP Session](http://php.net/manual/en/reserved.variables.session.php). Then, it generates an OpenTok token for that session ID. Once the API key, session ID, and token are ready, it sends a response with the body set to a JSON object containing the information.

```php
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
```

The `GET /session` routes generates a convenient session for fast establishment of communication.

```php
$app->get('/session', 'cors', function () use ($app) { 
    $app->redirect('/room/session');
});
```

### Start an [Archive](https://tokbox.com/developer/guides/archiving/)

A `POST` request to the `/archive/start` route starts an archive recording of an OpenTok session.
The session ID OpenTok session is passed in as JSON data in the body of the request

```php
// Start Archiving and return the Archive
$app->post('/archive/start', 'cors', function () use ($app) {
    $sessionId = $app->request->post('sessionId');
    $archive = $app->opentok->startArchive($sessionId, 'Getting Started Sample Archive');
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});
```

You can only create an archive for sessions that have at least one client connected. Otherwise,
the app will respond with an error.

### Stop an Archive
    
A `POST` request to the `/archive:archiveId/stop` route stops an archive recording.
The archive ID is returned by call to the `archive/start` endpoint.

```php
// Stop Archiving and return the Archive
$app->post('/archive/:archiveId/stop', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->stopArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});
```

### View an Archive

A `GET` request to `'/archive/:archiveId/view'` redirects the requested clients to
a URL where the archive gets played.

```php
// Download the archive
$app->get('/archive/:archiveId/view', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);
    if ($archive->status=='available') {
        $app->redirect($archive->url);
    }
    else {
        $app->render('view.php');
    }
});
```

### Get Archive information

A `GET` request to `/archive/:archiveId` returns a JSON object that contains all archive properties, including `status`, `url`, `duration`, etc. For more information, see [here](https://tokbox.com/developer/sdks/node/reference/Archive.html).

```php
$app->get('/archive/:archiveId', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($archive->toJson());
});
```

### Fetch multiple Archives

A `GET` request to `/archive` with optional `count` and `offset` params returns a list of JSON archive objects. For more information, please check [here](https://tokbox.com/developer/sdks/node/reference/OpenTok.html#listArchives).

Examples:
```php
GET /archive // fetch up to 1000 archive objects
GET /archive?count=10  // fetch the first 10 archive objects
GET /archive?offset=10  // fetch archives but first 10 archive objetcs
GET /archive?count=10&offset=10 // fetch 10 archive objects starting from 11st
```

## More information

This sample app does not provide client-side OpenTok functionality
(for connecting to OpenTok sessions and for publishing and subscribing to streams).
It is intended to be used with the OpenTok tutorials for Web, iOS, iOS-Swift, or Android:

* [Web](https://tokbox.com/developer/tutorials/web/basic-video-chat/)
* [iOS](https://tokbox.com/developer/tutorials/ios/basic-video-chat/)
* [iOS-Swift](https://tokbox.com/developer/tutorials/ios/swift/basic-video-chat/)
* [Android](https://tokbox.com/developer/tutorials/android/basic-video-chat/)
