<?php

namespace OTHelloWorld\Action\Archive;

use OpenTok\OpenTok;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class StartAction
{
    /**
     * @var OpenTok
     */
    protected $opentok;

    public function __construct(ContainerInterface $container)
    {
        $this->opentok = $container->get(OpenTok::class);
    }

    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $sessionId = $data['sessionId'];
        $archive = $this->opentok->startArchive($sessionId, ['name' => 'Getting Started Sample Archive']);

        return new JsonResponse($archive->toArray());
    }
}
