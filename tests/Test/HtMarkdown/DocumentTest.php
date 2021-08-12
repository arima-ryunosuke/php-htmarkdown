<?php
namespace ryunosuke\Test\HtMarkdown;

use ryunosuke\HtMarkdown\Document;

/**
 * @covers \ryunosuke\HtMarkdown\Document
 */
class DocumentTest extends \ryunosuke\Test\AbstractTestCase
{
    protected function setUp(): void
    {
        \Closure::bind(function () {
            Document::$cache = [];
        }, null, Document::class)();
    }

    function test___get()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that($doc->docroot)->is($dir);

        that($doc)->try('__get', 'undefined')->wasThrown('is undefined property');
    }

    function test___toString()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that(strval($doc))->is(realpath("$dir/dummy.md"));

        $doc = new Document("$dir/index.md", []);
        that(strval($doc))->is(realpath("$dir"));

        $doc = new Document("$dir/sub/sub/sub/index.md", []);
        that(strval($doc))->is(realpath("$dir/sub/sub/sub"));

        $doc = new Document("$dir", []);
        that(strval($doc))->is(realpath("$dir"));
    }

    function test_exists()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that($doc->exists())->isTrue();

        $doc = new Document("$dir/", []);
        that($doc->exists())->isTrue();

        $doc = new Document("$dir/notfound", []);
        that($doc->exists())->isFalse();

        $doc = new Document("$dir-notfound", []);
        that($doc->exists())->isFalse();
    }

    function test_lastModified()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        touch("$dir/dummy.md");
        that($doc->lastModified())->is(time());
    }

    function test_locale()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/index.md", [
            'locale' => 'ja',
        ]);
        that($doc->locale()['language'])->is('ja');

        $doc = new Document("$dir/index.md", [
            'locale' => 'unknown',
        ]);
        that($doc->locale()['language'])->is('en');
    }

    function test_isSupported()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/sub", [
            'soft_limit' => 0,
        ]);
        that($doc->isSupported())->isNull();

        $doc = new Document("$dir/img/a.png", [
            'soft_limit' => 9,
        ]);
        that($doc->isSupported())->isFalse();

        $doc = new Document("$dir/sub/sub", [
            'soft_limit' => 1,
        ]);
        that($doc->isSupported())->isNull();

        $doc = new Document("$dir/sub/sub", [
            'hard_limit' => 1,
        ]);
        that($doc->isSupported())->isFalse();

        $doc = new Document("$dir/dummy.md", []);
        that($doc->isSupported())->isTrue();

        $doc = new Document("$dir/.dotfile.md", []);
        that($doc->isSupported())->isFalse();

        $doc = new Document("$dir/vendor", []);
        that($doc->isSupported())->isFalse();

        $doc = new Document("$dir/parent/a", []);
        that($doc->isSupported())->isFalse();
    }

    function test_parents()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/sub/sub/sub/index.md", [
            'docroot' => $dir,
        ]);
        $parents = $doc->parents();
        that(array_map('strval', $parents))->is([
            realpath("$dir/sub/sub"),
            realpath("$dir/sub"),
        ]);
    }

    function test_siblings()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/parent/b.md", [
            'docroot' => $dir,
        ]);
        $siblings = $doc->siblings();
        that(array_map('strval', $siblings))->is([
            -1 => null,
            +1 => realpath("$dir/parent/d.md"),
        ]);

        $doc = new Document("$dir/parent/d.md", [
            'docroot' => $dir,
        ]);
        $siblings = $doc->siblings();
        that(array_map('strval', $siblings))->is([
            -1 => realpath("$dir/parent/b.md"),
            +1 => null,
        ]);
    }

    function test_children()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/parent", []);
        $children = $doc->children();
        that(array_map('strval', $children))->is([
            realpath("$dir/parent/b.md"),
            realpath("$dir/parent/d.md"),
        ]);
    }

    function test_descendants()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir", [
            'soft_limit' => 256,
        ]);

        $descendants = iterator_to_array($doc->descendants(), false);
        that(array_map('strval', $descendants))->is([
            realpath("$dir/dummy.md"),
            realpath("$dir/hogera"),
            realpath("$dir/hogera/hogera1"),
            realpath("$dir/hogera/hogera1/dummy.md"),
            realpath("$dir/parent"),
            realpath("$dir/parent/b.md"),
            realpath("$dir/parent/d.md"),
            realpath("$dir/php"),
            realpath("$dir/php/a.md"),
            realpath("$dir/php/b.md"),
            realpath("$dir/php/c.md"),
            realpath("$dir/php/index.php.md"),
            realpath("$dir/plain.md"),
            realpath("$dir/sub"),
            realpath("$dir/sub/sub"),
            realpath("$dir/sub/sub/sub"),
            realpath("$dir/sub/sub/sub/hoge.md"),
        ]);
    }

    function test_search()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir", [
            'soft_limit' => 256,
        ]);
        $result = iterator_to_array($doc->search('hoge'), false);
        that(array_map('strval', $result))->is([
            realpath("$dir/hogera"),
            realpath("$dir/hogera/hogera1"),
            realpath("$dir/sub/sub/sub"),
            realpath("$dir/sub/sub/sub/hoge.md"),
        ]);

        $doc = new Document(realpath("$dir/sub/sub"), [
            'soft_limit' => 256,
            'docroot'    => realpath($dir),
        ]);
        $result = iterator_to_array($doc->search('this is hoge'), false);
        that($result)->count(1);
        $result = iterator_to_array($doc->search('plain markdown'), false);
        that($result)->count(0);
    }

    function test_match()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir");
        that($doc->match('word'))->isFalse();
        that($doc->match('stub'))->isTrue();

        $doc = new Document("$dir/dummy.md");
        that($doc->match('word'))->isTrue();
        that($doc->match('stub'))->isFalse();
    }

    function test_localName()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that($doc->localName())->is('dummy.md');

        $doc = new Document("$dir/parent/index.md", []);
        that($doc->localName())->is('parent/');

        $doc = new Document("$dir/parent", []);
        that($doc->localName())->is('parent/');
    }

    function test_localPath()
    {
        $dir = __DIR__ . '/../../stub';
        $root = new Document("$dir", []);

        $doc = new Document("$dir/img/a.png", [
            'soft_limit' => 0,
        ]);
        that($doc->localPath($root))->is('img/a.png');

        $doc = new Document("$dir/index.md", []);
        that($doc->localPath($root))->is('index.md');

        $doc = new Document("$dir/parent", []);
        that($doc->localPath($root))->is('parent/index.md');

        $doc = new Document("$dir/parent/c.dummy", []);
        that($doc->localPath($root))->is('c.dummy');

        $doc = new Document("$dir/index.md", [
            'download' => true,
        ]);
        that($doc->localPath($root))->is('index.html');

        $doc = new Document("$dir/parent", [
            'download' => true,
        ]);
        that($doc->localPath($root))->is('parent/index.html');

        $root = new Document("$dir/sub", []);

        $doc = new Document("$dir/sub/sub", []);
        that($doc->localPath($root))->is('sub/index.md');
    }

    function test_summary()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", [
            'list_length' => 64,
        ]);
        that($doc->summary($doc, ''))->is(<<<MD
            # this is markdown file
            
            - test1
            - test2
            
            word
            
            this is description1.


            MD
        );

        $doc = new Document("$dir/dummy.md", [
            'list_length' => 186,
        ]);
        that($doc->summary($doc, ''))->contains(<<<MD
            ```
            codeblock1
            ```
            MD
        );

        $doc = new Document("$dir/dummy.md", [
            'list_length' => 64,
        ]);
        that($doc->summary($doc, 'word'))->is(<<<MD
            <mark class="highlighted">word</mark>
            
            this is description1.
            this is description2.
            
            
            MD
        );

        $doc = new Document("$dir/img/a.png", []);
        that($doc->summary($doc, ''))->is('![a.png](a.png)');

        $doc = new Document("$dir/sub/sub/dummy.txt", []);
        that($doc->summary($doc, ''))->is('');
    }

    function test_markup()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/sub/sub/sub/index.md", [
            'docroot' => $dir,
        ]);
        that($doc->markup('')->saveHTML())->containsAll([
            'main-section',
            'sub-section',
            'href="index"',
            'href="hoge.md"',
            'href="/plain.md"',
            'href="http://example.com"',
            'href="unknown"',
        ]);
    }

    function test_archive()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/plain.md", []);
        that(strval($doc->archive()))->stringEndsWith("plain.html");

        $doc = new Document("$dir/dummy.md", []);
        that(strval($doc->archive()))->stringEndsWith("dummy.zip");

        $doc = new Document("$dir", []);
        $archive = $doc->archive();
        that(strval($archive))->stringEndsWith("stub.zip");
        $zip = new \ZipArchive();
        $zip->open($archive->realpath());
        that($zip->getFromName('img/a.png'))->isNotFalse();
        that($zip->getFromName('img/a/b.jpg'))->isNotFalse();
        $zip->close();

        $doc = new Document("$dir/sub", [
            'hard_limit' => 2,
        ]);
        $zip = new \ZipArchive();
        $zip->open($doc->archive()->realpath());
        that($zip->count())->is(3);
        that($zip->getFromName('archive.log'))->contains('too many entries');
        $zip->close();
    }

    function test_plain()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that($doc->plain(''))->equalsFile("$dir/dummy.md");

        $doc = new Document("$dir/parent", []);
        that($doc->plain(''))->containsAll([
            'Index of parent',
            '## [b.md](b.md',
            '## [d.md](d.md',
        ]);

        $doc = new Document("$dir/sub", []);
        that($doc->plain('HOGE'))->containsAll([
            "Search Results 'HOGE'",
            '## [sub/sub/](sub/sub/index.md',
            '## [sub/sub/hoge.md](sub/sub/hoge.md',
            'class="highlighted"',
        ]);
    }

    function test_html()
    {
        $dir = __DIR__ . '/../../stub';

        $doc = new Document("$dir/dummy.md", []);
        that($doc->html())->containsAll([
            'section-level-h',
            'data-section-level',
        ]);
    }
}
