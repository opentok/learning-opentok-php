# OpenTok Getting Started Sample App

A simple server that uses the OpenTok PHP SDK to create sessions, generate tokens for those
sessions, archive (or record) sessions and download those archives.

## Automatic deployment to Heroku

Heroku is a PaaS (Platform as a Service) that can be used to deploy simple and small applications
for free. To easily deploy this repository to Heroku, sign up for a Heroku account and click this
button:

<a href="https://heroku.com/deploy?template=https://github.com/opentok/learning-opentok-php" target="_blank">
  <img src="https://www.herokucdn.com/deploy/button.png" alt="Deploy">
</a>

Heroku will prompt you to add your OpenTok API key and OpenTok API secret, which you can
obtain at the [TokBox Dashboard](https://dashboard.tokbox.com/keys).

You can also install this repository on your own PHP server (see the next section).

## Installation

1. Clone this repository.

2. Use [Composer](https://getcomposer.org/) to install the dependencies:

    `$ composer install --ignore-platform-reqs`

3. Next, input your own API Key and API Secret into the `run-demo` script file:

    ```
    export API_KEY=0000000
    export API_SECRET=abcdef1234567890abcdef01234567890abcdef
    ```

4. The run-demo file starts the PHP CLI development server (requires PHP >= 5.4) on port 8080. If
you want to run your server on another port, edit the file. Finally, start the server using the
run-demo script:

    `$ ./run-demo`

5. Visit the URL <http://localhost:8080/session> in your browser. You should see a JSON response
containing the OpenTok API key, session ID, and token. Read through the sections below to understand
how the server has been implemented.

# Walkthrough

This demo application uses the [Slim PHP micro-framework](http://www.slimframework.com/) and a
light-weight storage library (depending on your environment, the [library](https://github.com/ICanBoogie/Storage)
will either use your filesystem or PHP's APC cache). These are similar to many other
popular web frameworks and data persistence technologies. These are not a requirement for using
OpenTok, but they simplify the code in this sample application.

## Main Controller (web/index.php)

The first thing done in this file is to require the autoloader, which pulls in all the dependencies
that were installed by Composer. We now have the Slim framework, the storage library, and most
importantly the OpenTok SDK available.

```php
require $autoloader;

use Slim\Slim;

use ICanBoogie\Storage\APCStorage;
use ICanBoogie\Storage\FileStorage;

use OpenTok\OpenTok;
use OpenTok\Role;
use OpenTok\MediaMode;
```

Next the controller performs some basic checks on the environment, initializes the Slim application
(`$app`), and sets up the storage to be available in the application's container (`$app->storage`).

The first thing that we do with OpenTok is to initialize an instance and store it in the application
container. At the same time, we also store the OpenTok API key separately so that the app can access
it on its own.

Notice that the app gets the `API_KEY` and `API_SECRET` from the environment variables.


```php
// Initialize OpenTok instance, store it in the app contianer
$app->container->singleton('opentok', function () {
    return new OpenTok( getenv('API_KEY'), getenv('API_SECRET'));
});

$app->apiKey = getenv('API_KEY');
```

The sample app uses a single session ID to demonstrate the video chat, archiving, and signaling
functionality. It does not generate a new session ID for each call made to the server. Rather,
it generates one session ID and stores it. In other applications, it would be common
to save the session ID in a database table. If a session ID was not previously stored, like
on the first run of the application, we use the stored OpenTok instance to create a Session. The 
`opentok->createSession()` method returns a Session object. Then after
`$session->getSessionId()` returns the session ID, it will be stored for later use.

```php
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
```

Now we are ready to configure the HTTP routes for our sample app. We need four routes for our app:

1. Generate a Session and Token
2. Start an archive
3. Stop an archive
4. View an archive

### 1. Generate a Session and Token

The route handler for generating a session and token is shown below. The session ID is retrieved
from the storage and used to generate a new token.

```php
// Route to return the SessionID and token as a json
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
```

Inside the route handler, we generate a token, so the client has permission to connect to that
session. Finally, we return the OpenTok API key, session ID, and token as a JSON-encoded string
so that the client can connect to a OpenTok session.

### 2. Start an Archive

The handler for starting an archive is shown below. The session ID for which the archive is to be
started on is sent as a URL parameter by the client application. Inside the handler, the
`startArchive()` method of the opentok instance is called with the session ID belonging to the
session that needs to be archived. The optional second argument is the archive name, which is
stored with the archive and can be read later. 

```php
// Start Archiving and return the Archive ID
$app->post('/start/:sessionId', 'cors', function ($sessionId) use ($app) {
    $archive = $app->opentok->startArchive($sessionId, 'Getting Started Sample Archive');
    $app->response->headers->set('Content-Type', 'application/json');

    $responseData = array('archive' => $archive);
    echo json_encode($responseData);
});
```

This causes the recording to begin. The response sent back to the client's request will be the
JSON-encoded string describing the Archive object.


### 3. Stop an Archive
    
Next we move on to the handler for stopping an Archive:

```php
// Stop Archiving and return the Archive ID
$app->post('/stop/:archiveId', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->stopArchive($archiveId);
    $app->response->headers->set('Content-Type', 'application/json');

    $responseData = array('archive' => $archive);
    echo json_encode($responseData);
});
```

This handler is similar to the handler for starting an archive. It takes the ID of the archive that
needs to be stopped as a URL parameter. Inside the handler, it makes a call to the `stopArchive()`
method of the opentok instance which takes the archive ID as an argument. 

### 4. View an Archive

The code for the handler to view an archive is shown below:

```php
// Download the archive
$app->get('/view/:archiveId', 'cors', function ($archiveId) use ($app) {
    $archive = $app->opentok->getArchive($archiveId);

    if ($archive->status=='available')
        $app->redirect($archive->url);
    else {
        $app->render('view.php');
    }
});
```

Similar to the other archive handlers, this handler receives the ID of the archive to be downloaded
(viewed) as a URL parameter. It makes a call to the `getArchive()` method of the opentok instance
which takes the archive ID as the parameter. 

We then check if the archive is available for viewing. If it is available, the client application is
redirected to the archive's URL. Note that this is temporary storage and the archive status will
change to `'expired'` after 72 hours.

If the archive is not yet available, we load a template file `templates/view.html` to which we pass
the archive parameters (ID, status, and URL) . This template file checks if the archive is available.
If not, it again requests the `/view/:archiveId` page.

## Appendix -- Deploying to Heroku

Heroku is a PaaS (Platform as a Service) that can be used to deploy simple and small applications
for free. For that reason, you may choose to experiment with this code and deploy it using Heroku.

Use the button at the top of the README to deploy to Heroku in one click!

If you'd like to deploy manually, here is some additional information:

*  The provided `Procfile` describes a web process which can launch this application.

*  Use Heroku config to set the following keys:

   -  `OPENTOK_KEY` -- Your OpenTok API Key
   -  `OPENTOK_SECRET` -- Your OpenTok API Secret
   -  `SLIM_MODE` -- Set this to `production` when the environment variables should be used to
      configure the application. The Slim application will only start reading its Heroku config when
      its mode is set to `'production'`

   You should avoid committing configuration and secrets to your code, and instead use Heroku's
   config functionality.
