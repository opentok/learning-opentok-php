<?php

namespace OTHelloWorld\Action;

use OpenTok\OpenTok;
use OpenTok\MediaMode;
use ICanBoogie\Storage\FileStorage;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class RoomAction
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var OpenTok
     */
    protected $opentok;

    /**
     * @var FileStorage<string>
     */
    protected $storage;

    public function __construct(ContainerInterface $container)
    {
        $this->apiKey = $container->get('config')['tokbox']['api_key'];
        $this->opentok = $container->get(OpenTok::class);
        $this->storage = $container->get('storage');
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
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
    }
}
