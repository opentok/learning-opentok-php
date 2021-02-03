<?php

namespace OTHelloWorld\Action\Archive;

use OpenTok\OpenTok;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class ListAction
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
        $offset = $request->getQueryParams()['offset'] ? (int) $request->getQueryParams()['offset'] : 0;
        $count = $request->getQueryParams()['count'] ? (int) $request->getQueryParams()['count'] : 1000;
        $archiveList = $this->opentok->listArchives($offset, $count);
        $archives = $archiveList->getItems();

        $result = array();
        foreach ($archives as $archive) {
            $result[] = $archive->toArray();
        }

        return new JsonResponse($result);
    }
}
