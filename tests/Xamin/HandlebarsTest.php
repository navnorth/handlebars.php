<?php
/**
 * This file is part of Handlebars-php
 * Base on mustache-php https://github.com/bobthecow/mustache.php
 *
 * PHP version 5.3
 *
 * @category  Xamin
 * @package   Handlebars
 * @author    fzerorubigd <fzerorubigd@gmail.com>
 * @copyright 2013 (c) f0ruD A
 * @license   MIT <http://opensource.org/licenses/MIT>
 * @version   GIT: $Id$
 * @link      http://xamin.ir
 */

/**
 * Class AutoloaderTest
 */
class HandlebarsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        \Handlebars\Autoloader::register();
    }
    /**
     * Test handlebars autoloader
     *
     * @return void
     */
    public function testAutoLoad()
    {
        Handlebars\Autoloader::register(realpath(__DIR__ . '/../fixture/'));

        $this->assertTrue(class_exists('Handlebars\\Test'));
        $this->assertTrue(class_exists('\\Handlebars\\Test'));
        $this->assertTrue(class_exists('Handlebars\\Example\\Test'));
        $this->assertTrue(class_exists('\\Handlebars\\Example\\Test'));
        $this->assertFalse(class_exists('\\Another\\Example\\Test'));
    }

    /**
     * Test basic tags
     *
     * @param string $src    handlebars source
     * @param array  $data   data
     * @param string $result expected data
     *
     * @dataProvider simpleTagdataProvider
     *
     * @return void
     */
    public function testBasicTags($src, $data, $result)
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $engine = new \Handlebars\Handlebars(array('loader' => $loader));
        $this->assertEquals($result, $engine->render($src, $data));
    }

    /**
     * Simple tag provider
     *
     * @return array
     */
    public function simpleTagdataProvider()
    {
        return array(
            array(
                '{{! This is comment}}',
                array(),
                ''
            ),
            array(
                '{{data}}',
                array('data' => 'result'),
                'result'
            ),
            array(
                '{{data.key}}',
                array('data' => array('key' => 'result')),
                'result'
            ),
        );
    }


    /**
     * Test helpers (internal helpers)
     *
     * @param string $src    handlebars source
     * @param array  $data   data
     * @param string $result expected data
     *
     * @dataProvider internalHelpersdataProvider
     *
     * @return void
     */
    public function testSimpleHelpers($src, $data, $result)
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $helpers = new \Handlebars\Helpers();
        $engine = new \Handlebars\Handlebars(array('loader' => $loader, 'helpers' => $helpers));

        $this->assertEquals($result, $engine->render($src, $data));
    }

    /**
     * Simple helpers provider
     *
     * @return array
     */
    public function internalHelpersdataProvider()
    {
        return array(
            array(
                '{{#if data}}Yes{{/if}}',
                array('data' => true),
                'Yes'
            ),
            array(
                '{{#if data}}Yes{{/if}}',
                array('data' => false),
                ''
            ),
            array(
                '{{#with data}}{{key}}{{/with}}',
                array('data' => array('key' => 'result')),
                'result'
            ),
            array(
                '{{#each data}}{{this}}{{/each}}',
                array('data' => array(1, 2, 3, 4)),
                '1234'
            ),
            array(
                '{{#each data}}{{@key}}=>{{this}}{{/each}}',
                array('data' => array('key1'=>1, 'key2'=>2,)),
                'key1=>1key2=>2'
            ),
            array(
                '{{#unless data}}ok{{/unless}}',
                array('data' => true),
                ''
            ),
            array(
                '{{#unless data}}ok{{/unless}}',
                array('data' => false),
                'ok'
            ),
            array(
                '{{#bindAttr data}}',
                array(),
                'data'
            )

        );
    }

    /**
     * Management helpers
     */
    public function testHelpersManagement()
    {
        $helpers = new \Handlebars\Helpers(array('test' => function () {
        }), false);
        $engine = new \Handlebars\Handlebars(array('helpers' => $helpers));
        $this->assertTrue(is_callable($engine->getHelper('test')));
        $this->assertTrue($engine->hasHelper('test'));
        $engine->removeHelper('test');
        $this->assertFalse($engine->hasHelper('test'));
    }

    /**
     * Custom helper test
     */
    public function testCustomHelper()
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $engine = new \Handlebars\Handlebars(array('loader' => $loader));
        $engine->addHelper('test', function () {
            return 'Test helper is called';
        });
        $this->assertEquals('Test helper is called', $engine->render('{{#test}}', array()));
        $this->assertEquals('Test helper is called', $engine->render('{{test}}', array()));

        $engine->addHelper('test2', function ($template, $context, $arg) {
            return 'Test helper is called with ' . $arg;
        });
        $this->assertEquals('Test helper is called with a b c', $engine->render('{{#test2 a b c}}', array()));
        $this->assertEquals('Test helper is called with a b c', $engine->render('{{test2 a b c}}', array()));

        $engine->addHelper('renderme', function() {return new \Handlebars\String("{{test}}");});
        $this->assertEquals('Test helper is called', $engine->render('{{#renderme}}', array()));

        $engine->addHelper('dontrenderme', function() {return "{{test}}";});
        $this->assertEquals('{{test}}', $engine->render('{{#dontrenderme}}', array()));
    }

    /**
     * Test mustache style loop and if
     */
    public function testMustacheStyle()
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $engine = new \Handlebars\Handlebars(array('loader' => $loader));
        $this->assertEquals('yes', $engine->render('{{#x}}yes{{/x}}', array ('x' => true)));
        $this->assertEquals('', $engine->render('{{#x}}yes{{/x}}', array ('x' => false)));
        $this->assertEquals('yes', $engine->render('{{^x}}yes{{/x}}', array ('x' => false)));
        $this->assertEquals('1234', $engine->render('{{#x}}{{this}}{{/x}}', array ('x' => array (1,2,3,4))));
        $std = new stdClass();
        $std->value = 1;
        $this->assertEquals('1', $engine->render('{{#x}}{{value}}{{/x}}', array ('x' => $std)));
        $this->assertEquals('1', $engine->render('{{{x}}}', array ('x' => 1)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testParserException()
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $engine = new \Handlebars\Handlebars(array('loader' => $loader));
        $engine->render('{{#test}}{{#test2}}{{/test}}{{/test2}}', array());
    }

    /**
     * Test add/get/has/clear functions on helper class
     */
    public function testHelpersClass()
    {
        $helpers = new \Handlebars\Helpers();
        $helpers->add('test', function(){});
        $this->assertTrue($helpers->has('test'));
        $this->assertTrue(isset($helpers->test));
        $this->assertFalse($helpers->isEmpty());
        $helpers->test2 = function(){};
        $this->assertTrue($helpers->has('test2'));
        $this->assertTrue(isset($helpers->test2));
        $this->assertFalse($helpers->isEmpty());
        unset($helpers->test2);
        $this->assertFalse($helpers->has('test2'));
        $this->assertFalse(isset($helpers->test2));
        $helpers->clear();
        $this->assertFalse($helpers->has('test'));
        $this->assertFalse(isset($helpers->test));
        $this->assertTrue($helpers->isEmpty());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHelperWrongConstructor()
    {
        $helper = new \Handlebars\Helpers("helper");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHelperWrongCallable()
    {
        $helper = new \Handlebars\Helpers();
        $helper->add('test', 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHelperWrongGet()
    {
        $helper = new \Handlebars\Helpers();
        $x = $helper->test;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHelperWrongUnset()
    {
        $helper = new \Handlebars\Helpers();
        unset($helper->test);
    }

    /**
     * test String class
     */
    public function testStringClass()
    {
        $string = new \Handlebars\String('test');
        $this->assertEquals('test', $string->getString());
        $string->setString('new');
        $this->assertEquals('new', $string->getString());
    }

    /**
     * @param $dir
     *
     * @return bool
     */
    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    /**
     * Its not a good test :) but ok
     */
    public function testCacheSystem()
    {
        $path = sys_get_temp_dir() . '/__cache__handlebars';

        @$this->delTree($path);

        $dummy = new \Handlebars\Cache\Disk($path);
        $engine = new \Handlebars\Handlebars(array('cache' => $dummy));
        $this->assertEquals(0, count(glob($path . '/*')));
        $engine->render('test', array());
        $this->assertEquals(1, count(glob($path . '/*')));
    }

    /**
     * Test file system loader
     */
    public function testFileSystemLoader()
    {
        $loader = new \Handlebars\Loader\FilesystemLoader(realpath(__DIR__ . '/../fixture/data'));
        $engine = new \Handlebars\Handlebars();
        $engine->setLoader($loader);
        $this->assertEquals('test', $engine->render('loader', array()));
    }
    /**
     * Test file system loader
     */
    public function testFileSystemLoaderMultipleFolder()
    {
        $paths = array(
            realpath(__DIR__ . '/../fixture/data'),
            realpath(__DIR__ . '/../fixture/another')
        );

        $options = array(
            'prefix' => '__',
            'extension' => 'hb'
        );
        $loader = new \Handlebars\Loader\FilesystemLoader($paths, $options);
        $engine = new \Handlebars\Handlebars();
        $engine->setLoader($loader);
        $this->assertEquals('test_extra', $engine->render('loader', array()));
        $this->assertEquals('another_extra', $engine->render('another', array()));
    }

    /**
     * Test file system loader
     * @expectedException \InvalidArgumentException
     */
    public function testFileSystemLoaderNotFound()
    {
        $loader = new \Handlebars\Loader\FilesystemLoader(realpath(__DIR__ . '/../fixture/data'));
        $engine = new \Handlebars\Handlebars();
        $engine->setLoader($loader);
        $engine->render('invalid_file', array());
    }
    /**
     * Test file system loader
     * @expectedException \RuntimeException
     */
    public function testFileSystemLoaderInvalidFolder()
    {
        new \Handlebars\Loader\FilesystemLoader(realpath(__DIR__ . '/../fixture/') . 'invalid/path');
    }

    /**
     * Test partial loader
     */
    public function testPartialLoader()
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $partialLoader = new \Handlebars\Loader\FilesystemLoader(realpath(__DIR__ . '/../fixture/data'));
        $engine = new \Handlebars\Handlebars();
        $engine->setLoader($loader);
        $engine->setPartialsLoader($partialLoader);

        $this->assertEquals('test', $engine->render('{{>loader}}', array()));
    }

    /**
     * test variable access
     */
    public function testVariableAccess()
    {
        $loader = new \Handlebars\Loader\StringLoader();
        $engine = \Handlebars\Handlebars::factory();
        $engine->setLoader($loader);

        $var = new \StdClass();
        $var->x = 'var-x';
        $var->y = array(
            'z' => 'var-y-z'
        );
        $this->assertEquals('test', $engine->render('{{var}}', array('var' => 'test')));
        $this->assertEquals('var-x', $engine->render('{{var.x}}', array('var' => $var)));
        $this->assertEquals('var-y-z', $engine->render('{{var.y.z}}', array('var' => $var)));
        // Access parent context in with helper
        $this->assertEquals('var-x', $engine->render('{{#with var.y}}{{../var.x}}{{/with}}', array('var' => $var)));

        $obj = new DateTime();
        $time = $obj->getTimestamp();
        $this->assertEquals($time, $engine->render('{{time.getTimestamp}}', array('time' => $obj)));

    }


    public function testContext()
    {
        $test = new stdClass();
        $test->value = 'value';
        $test->array = array('a' => '1', 'b' => '2');
        $context = new \Handlebars\Context($test);
        $this->assertEquals('value', $context->get('value'));
        $this->assertEquals('value', $context->get('value', true));
        $this->assertEquals('1', $context->get('array.a', true));
        $this->assertEquals('2', $context->get('array.b', true));
        $new = array('value' => 'new value');
        $context->push($new);
        $this->assertEquals('new value', $context->get('value'));
        $this->assertEquals('new value', $context->get('value', true));
        $this->assertEquals('value', $context->get('../value'));
        $this->assertEquals('value', $context->get('../value', true));
        $this->assertEquals($new, $context->last());
        $this->assertEquals($new, $context->get('.'));
        $this->assertEquals($new, $context->get('this'));
        $this->assertEquals($new, $context->get('this.'));
        $this->assertEquals($test, $context->get('../.'));
        $context->pop();
        $this->assertEquals('value', $context->get('value'));
        $this->assertEquals('value', $context->get('value', true));
        $this->assertFalse($context->lastIndex());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getInvalidData
     */
    public function testInvalidAccessContext($invalid)
    {
        $context = new \Handlebars\Context(array());
        $this->assertEmpty($context->get($invalid));
        $context->get($invalid, true);
    }

    public function getInvalidData()
    {
        return array (
            array('../../data'),
            array('data'),
            array(''),
            array('data.key.key'),
        );
    }

}