<?php

use Minphp\Container\Container;
use Minphp\Bridge\Initializer;

/**
 * @coversDefaultClass \View
 */
class ViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $fixtureDir;

    /**
     * @var string The view path of the fixture plugin, relative to the root web directory
     */
    protected $pluginPath;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->fixtureDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Fixtures'
            . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR;
        $this->pluginPath = 'plugins' . DIRECTORY_SEPARATOR . 'my_plugin' . DIRECTORY_SEPARATOR;

        $fixtureDir = $this->fixtureDir;

        $init = Initializer::get();
        $container = new Container();

        $container->set('minphp.constants', function () use ($fixtureDir) {
            return [
                'ROOTWEBDIR' => $fixtureDir,
                'PLUGINDIR' => $fixtureDir . 'plugins' . DIRECTORY_SEPARATOR,
                'WEBDIR' => $fixtureDir,
                'APPDIR' => 'app' . DIRECTORY_SEPARATOR
            ];
        });
        $container->set('minphp.mvc', function () {
            return [
                'default_controller' => 'main',
                'default_structure' => 'structure',
                'default_view' => 'default',
                'view_extension' => '.pdt',
                'cli_render_views' => false,
                '404_forwarding' => false,
                'error_view' => 'errors'
            ];
        });

        $init->setContainer($container);
    }

    /**
     * Builds a View pointed at the fixture plugin, the way Dispatcher and a plugin
     * controller leave it
     *
     * @param string $template The template directory to prefer, if any
     * @return View
     */
    protected function getPluginView($template = null, $template_view = 'default')
    {
        $view = new View();
        $view->setDefaultView($this->pluginPath);
        $view->view = 'default';

        if (null !== $template) {
            $view->setTemplate($template, $template_view);
        }

        return $view;
    }

    /**
     * Returns the view directory the given rendered output was loaded from
     *
     * @param string $output The rendered view
     * @return array The marker the view printed and the view directory it reported
     */
    protected function parse($output)
    {
        return explode('|', $output);
    }

    /**
     * @covers ::fetch
     * @covers ::setView
     * @covers ::setDefaultView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * Tests that a view provided by the template directory is rendered from it
     */
    public function testFetchPrefersTemplate()
    {
        list($marker, $view_dir) = $this->parse($this->getPluginView('my_template')->fetch('main'));

        $this->assertEquals('TEMPLATE-MAIN', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/my_template/', $view_dir);
    }

    /**
     * @covers ::fetch
     * @covers ::setView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * @uses \View::setDefaultView
     * Tests that a view the template directory does not provide falls back to the view
     * directory, even though the template directory provides other views
     */
    public function testFetchFallsBackPerFile()
    {
        $view = $this->getPluginView('my_template');

        list($marker) = $this->parse($view->fetch('main'));
        $this->assertEquals('TEMPLATE-MAIN', $marker);

        list($marker, $view_dir) = $this->parse($view->fetch('other'));
        $this->assertEquals('DEFAULT-OTHER', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/default/', $view_dir);
    }

    /**
     * @covers ::fetch
     * @covers ::setView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * @uses \View::setDefaultView
     * Tests that no template leaves the view directory in charge
     */
    public function testFetchWithoutTemplate()
    {
        list($marker, $view_dir) = $this->parse($this->getPluginView()->fetch('main'));

        $this->assertEquals('DEFAULT-MAIN', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/default/', $view_dir);
    }

    /**
     * @covers ::fetch
     * @covers ::setView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * @uses \View::setDefaultView
     * Tests that a template matching the view directory is a no-op
     */
    public function testFetchWithTemplateMatchingView()
    {
        list($marker, $view_dir) = $this->parse($this->getPluginView('default')->fetch('main'));

        $this->assertEquals('DEFAULT-MAIN', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/default/', $view_dir);
    }

    /**
     * @covers ::fetch
     * @covers ::setView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * @uses \View::setDefaultView
     * @uses \View::setTemplate
     * Tests that a template directory which does not exist leaves the view directory in charge
     */
    public function testFetchWithUnknownTemplate()
    {
        list($marker, $view_dir) = $this->parse($this->getPluginView('no_such_template')->fetch('main'));

        $this->assertEquals('DEFAULT-MAIN', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/default/', $view_dir);
    }

    /**
     * @covers ::fetch
     * @covers ::setTemplate
     * @covers ::setView
     * @covers ::buildViewDir
     * @uses \View::__construct
     * @uses \View::setDefaultView
     * Tests that a view which has moved to a directory of its own is not overridden, even
     * though the template directory contains a view of the same name
     */
    public function testFetchLeavesViewOutsideTemplateViewAlone()
    {
        $view = $this->getPluginView('my_template');
        $view->view = 'my_other_dir';

        list($marker, $view_dir) = $this->parse($view->fetch('main'));

        $this->assertEquals('OTHER-DIR-MAIN', $marker);
        $this->assertStringEndsWith('plugins/my_plugin/views/my_other_dir/', $view_dir);
    }

    /**
     * @covers ::setTemplate
     * @uses \View::__construct
     * Tests that the template and the view it may override are both recorded
     */
    public function testSetTemplate()
    {
        $view = new View();
        $view->setTemplate('my_template', 'default');

        $this->assertEquals('my_template', $view->template);
        $this->assertEquals('default', $view->template_view);
    }
}
