<?php

namespace OTHelloWorld\Action;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EventsAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []) : ResponseInterface
    {
        if (getenv('ENABLE_LOGGING') === "true") {
            file_put_contents(getenv('LOGGING_PATH') . '/events.txt', $args['type'] . '---' . $request->getBody()->getContents() . PHP_EOL, FILE_APPEND);
        }

        return new EmptyResponse();
    }
}
