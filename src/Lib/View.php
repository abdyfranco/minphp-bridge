<?php

use Minphp\Bridge\Initializer;

/**
 * Allows the creation of views.
 */
class View extends Language
{
    /**
     * @var array Holds all the variables we will send to the view
     */
    protected $vars;

    /**
     * @var string The file used in the view
     */
    public $file;

    /**
     * @var string The file extension used in the view
     */
    public $view_ext = '.pdt';

    /**
     * @var string The location of this view's files within the view path
     */
    public $view;

    /**
     * @var string This view's relative path
     */
    public $view_dir;

    /**
     * @var string This view's partial path relative to the public web directory
     */
    public $view_path;

    /**
     * @var string The default view path
     */
    public $default_view_path;

    /**
     * @var \Minphp\Container\Container
     */
    private $container;

    /**
     * @param string $file The file name you want to load.
     * @param string $view The view directory to use (plugins use PluginName.directory).
     */
    public function __construct($file = null, $view = null)
    {
        $this->container = Initializer::get()->getContainer();
        $settings = $this->container->get('minphp.mvc');

        $this->view = $settings['default_view'];
        $this->view_ext = $settings['view_extension'];

        $this->default_view_path = $this->container->get('minphp.constants')['APPDIR'];

        $this->setView($file, $view);
    }

    /**
     * Copy constructor used to clone the View with all properties (e.g.
     * helpers) retained, while clearing all variables to be set in the view
     * file.
     */
    public function __clone()
    {
        $this->vars = null;
    }

    /**
     * Overwrites the default view path
     *
     * @param string $path The path to set as the default view path in this view
     */
    final public function setDefaultView($path)
    {
        $this->default_view_path = $path;
        $this->view_path = $path;
    }

    /**
     * Sets the view file and view to be used for this View
     *
     * @param string $file The file name to load
     * @param string $view The view directory to use (plugins use PluginName.directory)
     */
    final public function setView($file = null, $view = null)
    {
        // Overwrite the view file if given
        if ($file !== null) {
            $this->file = $file;
        }

        // Overwrite the view directory if given
        list($view_path, $view) = $this->getViewPath($view);
        $this->view = $view;
        $this->view_path = $view_path;
        $this->view_dir = str_replace(
            "\\",
            "/",
            str_replace(
                'index.php/',
                '',
                $this->container->get('minphp.constants')['WEBDIR']
            ) . $view_path . 'views' . DIRECTORY_SEPARATOR . $view . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Sets a variable in this view with the given name and value
     *
     * @param mixed $name Name of the variable to set in the view, or an array
     *  of key/value pairs where each key is the variable and each value is the value to set.
     * @param mixed $value Value of the variable to set in the view.
     */
    final public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $var_name => $value) {
                $this->vars[$var_name] = is_object($value) && $value instanceof self
                    ? $value->fetch()
                    : $value;
            }
        } else {
            $this->vars[$name] = is_object($value) && $value instanceof self
                ? $value->fetch()
                : $value;
        }
    }

    /**
     * Returns the output of the view
     *
     * @param string $file The file used as our view
     * @param string $view The view directory to use
     * @return string HTML generated by the view
     */
    final public function fetch($file = null, $view = null)
    {
        $this->setView($file, $view);

        $file = $this->container->get('minphp.constants')['ROOTWEBDIR']
            . $this->view_path . 'views' . DIRECTORY_SEPARATOR
            . $this->view . DIRECTORY_SEPARATOR . $this->file . $this->view_ext;

        if (is_array($this->vars)) {
            extract($this->vars);
        }

        if (!file_exists($file)) {
            // In some instances when running minPHP on macOS from a network drive
            // or an external drive, the file path is not correct due to the fact
            // that "$this->view_path" may already include ROOTWEBDIR
            if (strpos(strtolower(PHP_OS), 'darwin') !== false) {
                $file = $this->view_path . 'views' . DIRECTORY_SEPARATOR
                    . $this->view . DIRECTORY_SEPARATOR . $this->file . $this->view_ext;

                if (!file_exists($file)) {
                    throw new Exception(sprintf('Files does not exist: %s', $file));
                }
            } else {
                throw new Exception(sprintf('Files does not exist: %s', $file));
            }
        }

        ob_start(); // Start output buffering

        include $file; // Include the file

        $contents = ob_get_clean(); // Get the contents of the buffer and close buffer.

        return $contents; // Return the contents
    }

    /**
     * Fetches the view path and view file from the given string which may be
     * of the format PluginName.view_path/view_dir
     *
     * @param string $view The string to parse into a view path and view
     * @return array An array where the 1st index is the view path and the 2nd is the view
     */
    private function getViewPath($view = null)
    {
        $view_path = $this->view_path
            ? $this->view_path
            : $this->default_view_path;

        $view = $view == null
            ? $this->view
            : $view;

        $view_parts = explode('.', $view);

        if (count($view_parts) == 2) {
            $view_path = str_replace(
                $this->container->get('minphp.constants')['ROOTWEBDIR'],
                '',
                $this->container->get('minphp.constants')['PLUGINDIR']
            ) . Loader::fromCamelCase($view_parts[0]) . DIRECTORY_SEPARATOR;
            $view = $view_parts[1];
        }

        return array($view_path, $view);
    }
}
