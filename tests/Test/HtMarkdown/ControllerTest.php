<?php
namespace ryunosuke\Test\HtMarkdown;

use ryunosuke\HtMarkdown\Controller;

/**
 * @covers \ryunosuke\HtMarkdown\Controller
 */
class ControllerTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_isModified()
    {
        $controller = new Controller([
            'HTTP_IF_MODIFIED_SINCE' => '2000/12/12 12:34:56',
        ], []);
        that($controller->isModifiedSince(strtotime('2000/12/12 12:34:55')))->isFalse();
        that($controller->isModifiedSince(strtotime('2000/12/12 12:34:56')))->isFalse();
        that($controller->isModifiedSince(strtotime('2000/12/12 12:34:57')))->isTrue();
    }

    function test_isDownload()
    {
        $controller = new Controller([], []);
        that($controller->isDownload())->isFalse();

        $controller = new Controller([], [
            'dl' => true,
        ]);
        that($controller->isDownload())->isTrue();
    }

    function test_isPlain()
    {
        $controller = new Controller([], []);
        that($controller->isPlain())->isFalse();

        $controller = new Controller([], [
            'raw' => true,
        ]);
        that($controller->isPlain())->isTrue();
    }

    function test_response()
    {
        $controller = new Controller([], []);

        $controller->header([
            'X-header1' => 1,
            'X-header2: 1',
        ], false);
        $controller->content('aa', false);
        $controller->content('bb', false);

        @that($controller)->response(...[])->outputEquals('bb');
    }

    function test_handleCli()
    {
        $dir = __DIR__ . '/../../stub';

        if ($_SERVER['argc'] < 3) {
            $this->markTestSkipped();
        }

        $controller = new Controller([], [null, "$dir/plain.md"]);
        that($controller)->handleCli(...[])->outputMatches("#^PK#");

        $controller = new Controller([], [null, "$dir/plain.md", sys_get_temp_dir() . 'htt']);
        that($controller)->handleCli(...[])->outputEquals("");
        $this->assertCount(1, glob(sys_get_temp_dir() . 'htt'));

        $controller = new Controller([], [null, "$dir/notfound"]);
        that($controller)->handleCli()->wasThrown('is not found');
    }

    function test_handleHttp()
    {
        $dir = __DIR__ . '/../../stub';

        $controller = new Controller([
            'DOCUMENT_ROOT' => realpath($dir),
            'REQUEST_URI'   => '/notfound',
        ], []);
        @that($controller)->handleHttp(...[])->outputEquals('');

        $controller = new Controller([
            'DOCUMENT_ROOT'          => realpath($dir),
            'REQUEST_URI'            => '/dummy.md',
            'HTTP_IF_MODIFIED_SINCE' => date('r', strtotime('2037/12/24 12:34:56')),
        ], []);
        @that($controller)->handleHttp(...[])->outputEquals('');

        $controller = new Controller([
            'DOCUMENT_ROOT' => realpath($dir),
            'REQUEST_URI'   => '/sub/sub/dummy.txt',
        ], []);
        @that($controller)->handleHttp(...[])->outputEquals('dummy');

        $controller = new Controller([
            'DOCUMENT_ROOT' => realpath($dir),
            'REQUEST_URI'   => '/',
        ], []);
        @that($controller)->handleHttp(...[])->outputStartsWith('<html');

        $controller = new Controller([
            'DOCUMENT_ROOT' => realpath($dir),
            'REQUEST_URI'   => '/',
        ], [
            'raw' => true,
        ]);
        @that($controller)->handleHttp(...[])->outputStartsWith('# Index of');

        $controller = new Controller([
            'DOCUMENT_ROOT' => realpath($dir),
            'REQUEST_URI'   => '/',
        ], [
            'dl' => true,
        ]);
        @that($controller)->handleHttp()->final('stdout')->stringStartsWith('PK');
    }
}
