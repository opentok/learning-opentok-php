# OpenTok Getting Started Sample App

<img src="https://assets.tokbox.com/img/vonage/Vonage_VideoAPI_black.svg" height="48px" alt="Tokbox is now known as Vonage" />

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
- [PHP 7.3 or higher](https://php.net)

## Installation & Running on localhost

  1. Clone the app by running the command
  
          git clone git@github.com:opentok/learning-opentok-php.git

  2. `cd` to the root directory.

  3. Run `composer install` command to fetch and install all dependencies.

  4. Next, copy the `.env.dist` file to `.env` and edit to add your API Key and Secret:

      ```
      TOKBOX_API_KEY=0000000
      TOKBOX_SECRET=abcdef1234567890abcdef01234567890abcdef
      ```

      *Important:* The archiving sample application uses archives that are stored in the OpenTok
      cloud. In your [OpenTok Account page](https://tokbox.com/account/), ensure that the OpenTok
      project you use (corresponding to the API key and API secret you use here) is *not* set
      up to use cloud storage on Microsoft Azure or Amazon S3. However, in a production
      application, you will want to use an OpenTok project that has archive file cloud storage
      on Microsoft Azure or Amazon S3 enabled, since archives stored on the OpenTok cloud are
      only available for 72 hours.

  5. Start the server using composer:

    `$ composer run --timeout 0 serve`

  6. Visit the URL <http://localhost:3000/session> in your browser. You should see a JSON response
containing the OpenTok API key, session ID, and token.

# Exploring the code

The `web/index.php` file contains setup and routing for the web service. The logic for each route is stored in `src/Action/`. The rest of this tutorial discusses code in these files.

In order to navigate clients to a designated meeting spot, we associate the Session ID to a room name which is easier for people to recognize and pass. For simplicity, we use a local file storage to implement the association where the room name is the file name and the Session ID is the contents. For production applications, you may want to configure a persistence (such as a database) to achieve this functionality.

### Generate a Session and Token

The `GET /room/:name` route associates an OpenTok session with a "room" name. This route handles the passed room name and performs a check to determine whether the app should generate a new session ID or retrieve a session ID from the local file storage. Then, it generates an OpenTok token for that session ID. Once the API key, session ID, and token are ready, it sends a response with the body set to a JSON object containing the information.

```php
$name = $args['name'];
// if a room name is already associated with a session ID
if ($this->storage->exists($name)) {
    // fetch the sessionId from local storage
    $sessionId = $this->storage[$name];

    // generate token
    $token = $this->opentok->generateToken($sessionId);
    $responseData = [
        'apiKey' => $this->apiKey,
        'sessionId' => $sessionId,
        'token'=>$token
    ];

    return new JsonResponse($responseData);
} else { // Generate a new session and store it off
    $session = $this->opentok->createSession([
        'mediaMode' => MediaMode::ROUTED
    ]);

    // store the sessionId into local
    $this->storage[$name] = $session->getSessionId();
    
    // generate token
    $token = $this->opentok->generateToken($session->getSessionId());
    $responseData = [
        'apiKey' => $this->apiKey,
        'sessionId' => $session->getSessionId(),
        'token'=>$token
    ];

    return new JsonResponse($responseData);
}
```

The `GET /session` route generates a convenient session for quick establishment of communication.

```php
$parser = RouteContext::fromRequest($request)->getRouteParser();
return new RedirectResponse($parser->urlFor('room', ['name' => 'session']));
```

### Start an [Archive](https://tokbox.com/developer/guides/archiving/)

A `POST` request to the `/archive/start` route starts an archive recording of an OpenTok session.
The session ID OpenTok session is passed in as JSON data in the body of the request

```php
// Start Archiving and return the Archive
$data = json_decode($request->getBody()->getContents(), true);
$sessionId = $data['sessionId'];
$archive = $this->opentok->startArchive($sessionId, 'Getting Started Sample Archive');

return new JsonResponse($archive->toJson());
```

You can only create an archive for sessions that have at least one client connected. Otherwise,
the app will respond with an error.

### Stop an Archive
    
A `POST` request to the `/archive:archiveId/stop` route stops an archive recording.
The archive ID is returned by the call to the `archive/start` endpoint.

```php
// Stop Archiving and return the Archive
$archive = $this->opentok->stopArchive($args['archiveId']);
return new JsonResponse($archive->toJson());
```

### View an Archive

A `GET` request to `'/archive/:archiveId/view'` redirects the requested clients to
a URL where the archive gets played.

```php
// Download the archive
$archive = $this->opentok->getArchive($args['archiveId']);
if ($archive->status=='available') {
    return new RedirectResponse($archive->url);
}
else {
    return new HtmlResponse(file_get_contents($this->viewsDir . '/view.html'));
}
```

### Get Archive information

A `GET` request to `/archive/:archiveId` returns a JSON object that contains all archive properties, including `status`, `url`, `duration`, etc. For more information, see [here](https://tokbox.com/developer/sdks/node/reference/Archive.html).

```php
$archive = $this->opentok->getArchive($args['archiveId']);
return new JsonResponse($archive->toJson());
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

## Development and Contributing

Interested in contributing? We :heart: pull requests! See the [Contribution](CONTRIBUTING.md) guidelines.

## Getting Help

We love to hear from you so if you have questions, comments, or find a bug in the project, let us know! You can either:

- Open an issue on this repository
- See <https://support.tokbox.com/> for support options
- Tweet at us! We're [@VonageDev](https://twitter.com/VonageDev) on Twitter
- Or [join the Vonage Developer Community Slack](https://developer.nexmo.com/community/slack)

## Further Reading

- Check out the Developer Documentation at <https://tokbox.com/developer/>