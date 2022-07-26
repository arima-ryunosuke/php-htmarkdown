<?php
namespace ryunosuke\Test\HtMarkdown;

use ryunosuke\HtMarkdown\File;

/**
 * @covers \ryunosuke\HtMarkdown\File
 */
class FileTest extends \ryunosuke\Test\AbstractTestCase
{
    function test__toString()
    {
        $file = new File(__FILE__);
        that(strval($file))->is(__FILE__);

        $file = new File('../hoge');
        that(strval($file))->is('../hoge');
    }

    function test_alias()
    {
        $file = new File(__FILE__);
        $file->alias('hoge.txt');
        that(strval($file))->is('hoge.txt');
    }

    function test_parent()
    {
        $file = new File(__FILE__);
        that($file->parent())->is(strval(new File(__DIR__)));

        $file = new File('/path/to/notfound');
        that($file->parent())->is('/path/to');

        $file = new File('/path/to/notfound/');
        that($file->parent())->is('/path/to');
    }

    function test_relative()
    {
        $fileA = new File('/path/to/fileA');
        $fileB = new File('/path/to/fileB');
        $fileC = new File('/path/to/dir/fileC');
        $fileD = new File('/path/fileD');

        $cases = [
            [$fileA, $fileB, '../fileA'],
            [$fileA, $fileC, '../../fileA'],
            [$fileA, $fileD, '../to/fileA'],
            [$fileB, $fileA, '../fileB'],
            [$fileB, $fileC, '../../fileB'],
            [$fileB, $fileD, '../to/fileB'],
            [$fileC, $fileA, '../dir/fileC'],
            [$fileC, $fileB, '../dir/fileC'],
            [$fileC, $fileD, '../to/dir/fileC'],
            [$fileD, $fileA, '../../fileD'],
            [$fileD, $fileB, '../../fileD'],
            [$fileD, $fileC, '../../../fileD'],
        ];

        foreach ($cases as [$to, $from, $path]) {
            $actual = $to->relative($from);
            that($actual)->as("$to * $from")->is($path);
            that((new File("$from/$actual"))->normalize())->as("$to * $from")->is($to);
        }
    }

    function test_normalize()
    {
        $file = new File('/path/./to///../file');
        that($file->normalize())->is('/path/file');

        $file = new File('../path');
        that($file)->normalize()->wasThrown('invalid as path');
    }

    function test_realpath()
    {
        $file = new File(__FILE__);
        that($file->realpath())->is(__FILE__);

        $file = new File('/path/to/notfound');
        that($file->realpath())->isNull();
    }

    function test_dirname()
    {
        $file = new File('/path/to/file');
        that($file->dirname())->is('/path/to');
        that($file->dirname(2))->is('/path');
    }

    function test_basename()
    {
        $file = new File('/path/to/file.txt');
        that($file->basename())->is('file.txt');
    }

    function test_filename()
    {
        $file = new File('/path/to/file.txt');
        that($file->filename())->is('file');
    }

    function test_extension()
    {
        $file = new File('/path/to/file.txt');
        that($file->extension())->is('txt');

        $file = new File('/path/to/file');
        that($file->extension())->is('');

        $file = new File('/path/to/file.');
        that($file->extension())->is('');
    }

    function test_changeExtension()
    {
        $file = new File('/path/to/file.txt');
        that($file->changeExtension(''))->is('/path/to/file');

        $file = new File('/path/to/file');
        that($file->changeExtension('.txt'))->is('/path/to/file.txt');

        $file = new File('/path/to/file.txt');
        that($file->changeExtension('.html'))->is('/path/to/file.html');
    }

    function test_exists()
    {
        $file = new File(__FILE__);
        that($file->exists())->isTrue();

        $file = new File('/path/to/notfound');
        that($file->exists())->isFalse();
    }

    function test_match()
    {
        $file = new File(__FILE__);
        that($file->match('HtMarkdown'))->isTrue();
        that($file->match('hogera'))->isFalse();
    }

    function test_isFile()
    {
        $file = new File(__FILE__);
        that($file->isFile())->isTrue();

        $file = new File(__DIR__);
        that($file->isFile())->isFalse();
    }

    function test_isDir()
    {
        $file = new File(__FILE__);
        that($file->isDir())->isFalse();

        $file = new File(__DIR__);
        that($file->isDir())->isTrue();
    }

    function test_size()
    {
        $file = new File(__FILE__);
        that($file->size())->is(filesize(__FILE__));
    }

    function test_mtime()
    {
        $file = new File(__FILE__);
        touch(__FILE__);
        that($file->mtime())->is(time());
    }

    function test_mimetype()
    {
        $file = new File(__FILE__);
        that($file->mimetype())->is('text/x-php');

        $file = new File(__DIR__);
        that($file->mimetype())->is('directory');
    }

    function test_contents()
    {
        $file = new File('/path/to/file.txt');
        that($file->contents())->isNull();

        $file = new File('/path/to/file.txt', 'hoge');
        that($file->contents())->is('hoge');

        $file = new File(__FILE__);
        that($file->contents())->is(file_get_contents(__FILE__));

        $file = new File(__FILE__, 'hoge');
        that($file->contents())->is('hoge');

        $file = new File(__DIR__ . '/../../stub/php/index.php.md');
        that($file->contents())->is(<<<MD
# INDEX

## A
this is A

## B
this is B

## C
this is C

MD
        );
    }

    function test_lines()
    {
        $file = new File(__FILE__);
        $it = $file->lines();
        that(iterator_to_array($it))->contains("    function test_lines()\n");
    }
}
