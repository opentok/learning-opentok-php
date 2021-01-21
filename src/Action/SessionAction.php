<?php

namespace OTHelloWorld\Action;

use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * This action redirects to /room/session
 */
class SessionAction
{
    public function __invoke(ServerRequestInterface $request) : ResponseInterface
    {
        $parser = RouteContext::fromRequest($request)->getRouteParser();
        return new RedirectResponse($parser->urlFor('room', ['name' => 'session']));
    }
}
