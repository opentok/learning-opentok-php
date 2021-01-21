<?php

namespace OTHelloWorld\Action;

use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Container\ContainerInterface;

class IndexAction
{
    /**
     * @var string
     */
    protected $viewsDir;

    public function __construct(ContainerInterface $container)
    {
        $this->viewsDir = $container->get('config')['views_dir'];
    }

    public function __invoke() : ResponseInterface
    {
        $template = file_get_contents($this->viewsDir . '/home.html');
        $template = $template ? $template : 'Unable to find home template';

        return new HtmlResponse($template);
    }
}
