<?php

namespace OTHelloWorld\Action\Archive;

use OpenTok\OpenTok;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class ViewAction
{
    /**
     * @var OpenTok
     */
    protected $opentok;

    /**
     * @var string
     */
    protected $viewsDir;

    public function __construct(ContainerInterface $container)
    {
        $this->opentok = $container->get(OpenTok::class);
        $this->viewsDir = $container->get('config')['views_dir'];
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
        $archive = $this->opentok->getArchive($args['archiveId']);
        if ($archive->status=='available') {
            return new RedirectResponse($archive->url);
        }
        else {
            $template = file_get_contents($this->viewsDir . '/view.html');
            $template = $template ? $template : 'Unable to find view archive template';
    
            return new HtmlResponse($template);
        }
    }
}
