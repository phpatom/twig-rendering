<?php


namespace Atom\TwigRendering;

use Atom\Kernel\Kernel;
use Atom\Kernel\Contracts\ServiceProviderContract;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\DI\Exceptions\UnsupportedInvokerException;
use Atom\Web\Application;
use Atom\Web\Contracts\RendererContract;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

class TwigRendering implements ServiceProviderContract
{
    /**
     * @var ?LoaderInterface
     */
    private $loader;

    /**
     * @var ?Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $paths;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var array
     */
    private $globals;

    private $cacheIfProduction = false;

    /**
     * TwigRendering constructor.
     * @param array $paths
     * @param array $options
     */
    public function __construct(array $paths = [], array $options = [])
    {
        $this->paths = $paths;
        $this->options = $options;
    }


    /**
     * @param Application|Kernel $app
     * @throws LoaderError
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws UnsupportedInvokerException
     */
    public function register(Kernel $app)
    {
        if (!is_null($this->cacheDir) && !$this->cacheIfProduction) {
            $this->options["cache"] = $app->path()->app($this->cacheDir);
        }
        if (!is_null($this->cacheDir) && $this->cacheIfProduction && $app->env()->isProduction()) {
            $this->options["cache"] = $app->path()->app($this->cacheDir);
        }
        $renderer = $this->makeRenderer($app->path()->app());
        if (!is_null($this->globals)) {
            $renderer->addGlobal($this->globals);
        }
        $c = $app->container();
        $c->bindings()->bindInstance($renderer);
        $c->bindings()->store(RendererContract::class, $c->as()->object($renderer));
        $c->bindings()->bindInstance($renderer->getTwig());
    }

    /**
     * @param LoaderInterface $loader
     * @return TwigRendering
     */
    public function withLoader(LoaderInterface $loader): TwigRendering
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * @param Environment $environment
     * @return TwigRendering
     */
    public function withEnvironment(Environment $environment): TwigRendering
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @param array $options
     * @return TwigRendering
     */
    public function withOptions(array $options): TwigRendering
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $paths
     * @return TwigRendering
     */
    public function withPaths(array $paths): TwigRendering
    {
        $this->paths = $paths;
        return $this;
    }

    public function hasLoader(): bool
    {
        return !is_null($this->loader);
    }

    public function hasEnvironment(): bool
    {
        return !is_null($this->environment);
    }

    /**
     * @param string $rootPath
     * @return TwigRenderer
     * @throws LoaderError
     */
    private function makeRenderer(string $rootPath): TwigRenderer
    {
        if ($this->hasEnvironment()) {
            return new TwigRenderer($this->environment);
        }
        if ($this->hasLoader()) {
            $environment = new Environment($this->loader, $this->options);
            return new TwigRenderer($environment);
        }
        $loader = new FilesystemLoader([], $rootPath);
        foreach ($this->paths as $namespace => $path) {
            if (!is_string($namespace)) {
                $loader->addPath($path);
            } else {
                $loader->addPath($path, $namespace);
            }
        }
        return new TwigRenderer(new Environment($loader, $this->options));
    }

    public function cache(string $cacheDir): TwigRendering
    {
        $this->cacheDir = $cacheDir;
        return $this;
    }

    public function cacheIfProduction(string $cacheDir): TwigRendering
    {
        $this->cacheIfProduction = true;
        $this->cache($cacheDir);
        return $this;
    }

    public static function create(array $paths = []): TwigRendering
    {
        if (empty($paths)) {
            $paths = ["templates"];
        }
        return (new self($paths));
    }

    public static function default(array $paths = []): TwigRendering
    {
        return (new self(array_merge($paths, [
            "templates"
        ])))->cacheIfProduction("var/twig");
    }

    /**
     * @param array $globals
     * @return TwigRendering
     */
    public function withGlobals(array $globals): TwigRendering
    {
        $this->globals = $globals;
        return $this;
    }
}
