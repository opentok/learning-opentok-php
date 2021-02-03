<?php

namespace OTHelloWorld\Action;

use OpenTok\OpenTok;
use ICanBoogie\Storage\FileStorage;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

class JoinAction
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

    /**
     * @var string
     */
    protected $viewsDir;

    public function __construct(ContainerInterface $container)
    {
        $this->apiKey = $container->get('config')['tokbox']['api_key'];
        $this->opentok = $container->get(OpenTok::class);
        $this->storage = $container->get('storage');
        $this->viewsDir = $container->get('config')['views_dir'];
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
        $name = $args['name'];
        if ($this->storage->exists($name)) {
            // fetch the sessionId from local storage
            $sessionId = $this->storage[$name];

            // generate token
            $token = $this->opentok->generateToken($sessionId);
            $data = [
                'apiKey' => $this->apiKey,
                'sessionId' => $sessionId,
                'token' => $token
            ];

            $template = file_get_contents($this->viewsDir . '/join.html');
            if ($template) {
                foreach ($data as $key => $value) {
                    $template = str_replace('{{ ' . $key . ' }}', $value, $template);
                }
            }
            $template = $template ? $template : 'Unable to find home template';

            return new HtmlResponse($template);
        } else { // Generate a new session and store it off
            return new HtmlResponse('<h1>404 Not Found</h1>The room you requested was not found', 404);
        }
        
    }
}
