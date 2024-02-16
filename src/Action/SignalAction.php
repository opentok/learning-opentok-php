<?php

namespace OTHelloWorld\Action;

use Laminas\Diactoros\Response\EmptyResponse;
use OpenTok\OpenTok;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SignalAction
{
    /**
     * @var Client
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
        $data = json_decode($request->getBody()->getContents(), true);
        $sessionId = $data['sessionId'];
        $signalData = 'Signal from server at ' . date('l jS \of F Y h:i:s A');
        $this->opentok->sendSignal($sessionId, 'from-server', $signalData);

        return new EmptyResponse();
    }
}
