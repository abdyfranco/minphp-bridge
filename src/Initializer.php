<?php
namespace Minphp\Bridge;

use Minphp\Container\ContainerAwareInterface;
use Minphp\Container\ContainerInterface;
use Router;

/**
 * Initializer for the Bridge
 *
 * Obtains the container for use by bridged libraries to manage dependencies
 */
class Initializer implements ContainerAwareInterface
{
    private static $initializer;
    protected $container;

    /**
     * Singleton
     */
    private function __construct()
    {

    }

    /**
     * Fetch the instance of the Initializer
     *
     * @return Initializer
     */
    public static function get()
    {
        if (!self::$initializer) {
            self::$initializer = new self();
        }

        return self::$initializer;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Run the initializer
     */
    public function run()
    {
        $this->defineConstants();
        $this->setAutoload();
        $this->setRouter();
        $this->loadRoutes();
    }

    /**
     * Define global constants
     */
    private function defineConstants()
    {
        foreach ($this->container->get('minphp.constants') as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * Register the autoloader
     */
    private function setAutoload()
    {
        spl_autoload_register(
            array($this->container->get('loader'), 'autoload'),
            true,
            true
        );
    }

    /**
     * Setup the router
     */
    private function setRouter()
    {
        $router = Router::get();
        $router->setWebDir(
            $this->container->get('minphp.constants')['WEBDIR']
        );
        $router->setPluginDir(
            $this->container->get('minphp.constants')['PLUGINDIR']
        );
        $router->setDefaultController(
            $this->container->get('minphp.mvc')['default_controller']
        );
    }

    /**
     * Load the routes
     */
    private function loadRoutes()
    {
        require_once $this->container->get('minphp.constants')['CONFIGDIR']
            . 'routes.php';
    }
}
