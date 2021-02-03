<?php

namespace OTHelloWorld\Action\Archive;

use OpenTok\OpenTok;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class StopAction
{
    /**
     * @var OpenTok
     */
    protected $opentok;

    public function __construct(ContainerInterface $container)
    {
        $this->opentok = $container->get(OpenTok::class);
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
        $archive = $this->opentok->stopArchive($args['archiveId']);
        
        return new JsonResponse($archive->toArray());
    }
}
