<?php


namespace Atom\TwigRendering;

use Atom\Web\Contracts\RendererContract;
use Atom\Web\Contracts\RendererExtensionProvider;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFunction;

class TwigRenderer implements RendererContract
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string $template
     * @param array $args
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, array $args = []): string
    {
        return $this->getTwig()->render($template, $args);
    }

    public function addExtensions(RendererExtensionProvider $extensionProvider)
    {
        foreach ($extensionProvider->getExtensions() as $name => $callable) {
            $this->getTwig()->addFunction(new TwigFunction($name, $callable));
        }
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function addGlobal(array $data)
    {
        foreach ($data as $k => $v) {
            $this->getTwig()->addGlobal($k, $v);
        }
    }
}
