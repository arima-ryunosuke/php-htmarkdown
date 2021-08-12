<?php

namespace ryunosuke\HtMarkdown;

/**
 * @property-read bool $download
 * @property-read bool $singlefile
 * @property-read int $list_length
 * @property-read int $soft_limit
 * @property-read int $hard_limit
 */
class Document
{
    /** @var array */
    private static $cache = [];

    /** @var \DOMDocument */
    private $dom;

    /** @var File */
    private $file;

    /** @var array */
    private $options;

    public function __construct(string $filename, array $options = [])
    {
        $options = array_replace([
            'index_file' => 'index.md',
            'singlefile' => false,
            'download'   => false,
        ], array_filter($options, function ($v) { return !is_null($v); }));

        if (is_dir($filename)) {
            $filename = "$filename/{$options['index_file']}";
        }

        $this->file = new File($filename);

        $this->options = array_replace(Controller::SPECIFIABLE_OPTIONS, [
            'docroot' => $this->file->dirname(),
        ], $options);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        throw new \InvalidArgumentException("'$name' is undefined property");
    }

    public function __toString(): string
    {
        if ($this->isDirectoryIndex()) {
            return (string) $this->file->parent()->realpath();
        }
        else {
            return (string) $this->file->realpath();
        }
    }

    public function exists(): bool
    {
        return !!strlen($this);
    }

    public function lastModified(): int
    {
        $targets = [(string) $this];

        // for debug
        if (!\Phar::running()) {
            $targets = array_merge($targets, [
                __FILE__,
                __DIR__ . '/../../src/HtMarkdown',
                __DIR__ . '/../../src/locale',
                __DIR__ . '/../../src/template',
            ]);
        }

        $mtimes = [];
        foreach ($targets as $target) {
            $target = realpath($target);
            if (is_dir($target)) {
                foreach (glob("$target/*") as $file) {
                    $mtimes[$file] = filemtime($file);
                }
            }
            else {
                $mtimes[$target] = filemtime($target);
            }
        }
        return max($mtimes);
    }

    public function locale(): array
    {
        $locale = strtolower($this->options['locale']);
        $localefile = __DIR__ . "/../locale/$locale.php";
        if (!file_exists($localefile)) {
            $localefile = __DIR__ . "/../locale/en.php";
        }
        return require $localefile;
    }

    public function isSupported(): ?bool
    {
        $main = function () {
            foreach ((array) $this->options['ignore_path'] as $path) {
                if ($this->file->match($path)) {
                    return false;
                }
            }

            if (($this->file->filename()[0] ?? '') === '.') {
                return false;
            }

            if ($this->soft_limit <= 0) {
                return null;
            }

            if ($this->file->exists()) {
                return $this->file->extension() === 'md';
            }

            if ($this->soft_limit < count(self::$cache)) {
                return null;
            }
            if ($this->hard_limit < count(self::$cache)) {
                return false;
            }

            return !!count($this->children());
        };

        $key = (string) $this;
        return self::$cache[$key] ?? self::$cache[$key] = $main();
    }

    public function isDirectoryIndex(): bool
    {
        return $this->file->basename() === $this->options['index_file'];
    }

    /**
     * @return static[]
     */
    public function parents(): array
    {
        $dirname = $this->file->dirname($this->isDirectoryIndex() ? 2 : 1);
        $parents = [];
        for ($i = 1; $i < 128; $i++) {
            if (strlen($dirname) < strlen($this->options['docroot']) || in_array($dirname, [$this->options['docroot'], '.', '/'], true)) {
                break;
            }
            $parents[] = new self($dirname, $this->options);
            $dirname = dirname($dirname);
        }
        return $parents;
    }

    /**
     * @return static[]
     */
    public function siblings(): array
    {
        $siblings = [
            -1 => null,
            +1 => null,
        ];

        $parent = $this->parents()[0] ?? null;
        if (!$parent) {
            return $siblings;
        }

        $parent_children = $parent->children();
        foreach ($parent_children as $n => $parent_child) {
            if (strcmp($this, $parent_child) === 0) {
                $siblings[-1] = isset($parent_children[$n - 1]) ? $parent_children[$n - 1] : null;
                $siblings[+1] = isset($parent_children[$n + 1]) ? $parent_children[$n + 1] : null;
                break;
            }
        }
        return $siblings;
    }

    /**
     * @return static[]
     */
    public function children(): array
    {
        if (!$this->isDirectoryIndex()) {
            return [];
        }

        $files = glob("{$this->file->dirname()}/*");
        sort($files);
        $children = [];
        foreach ($files as $file) {
            $child = new self($file, $this->options);
            if (($child->isSupported() ?? true) && strcmp($this->file, $child->file) !== 0) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * @return \Generator|static[]
     */
    public function descendants(): \Generator
    {
        foreach ($this->children() as $child) {
            yield $child;
            yield from $child->descendants();
        }
    }

    /**
     * @param string $query
     * @return \Generator|static[]
     */
    public function search(string $query): \Generator
    {
        foreach ($this->descendants() as $it) {
            if ($it->match($query)) {
                yield $it;
            }
        }
    }

    public function match(string $query): bool
    {
        if (!strlen($query)) {
            return true;
        }

        if ($this->file->exists()) {
            foreach ($this->file->lines() as $line) {
                if (mb_stripos($line, $query) !== false) {
                    return true;
                }
            }
        }
        else {
            if ($this->file->match($query)) {
                return true;
            }
        }
        return false;
    }

    public function localName(): string
    {
        if (!$this->isDirectoryIndex()) {
            return $this->file->basename();
        }
        else {
            return $this->file->parent()->basename() . '/';
        }
    }

    public function localPath(self $from): string
    {
        if ($this->isSupported() ?? true) {
            $path = $this->file->relative($from->file->parent());
            $ext = $this->file->extension();
            if ($ext !== 'md') {
                return $path;
            }
            return preg_replace('#^\\./#u', '', $path->changeExtension($this->download ? '.html' : '.md'));
        }
        else {
            return $this->file->basename();
        }
    }

    public function summary(self $parent, string $query): string
    {
        $list_length = $this->options['list_length'];

        $summary = '';

        if (!$this->file->exists()) {
            foreach ($this->children() as $child) {
                if ($child->match($query)) {
                    $summary .= "- [{$child->localName()}]({$child->localPath($parent)})\n";
                }
            }
            return $summary;
        }

        if (preg_match('#^image/#', $this->file->mimetype())) {
            return '![' . $this->localName() . '](' . $this->localName() . ')';
        }

        if ($this->file->extension() !== 'md') {
            return '';
        }

        $blockmarkers = ['```', '"""', '///', '<<<'];
        $block = '';
        $queried = !!strlen($query);
        $matched = !$queried;

        $mblen = 0;
        foreach ($this->file->lines() as $line) {
            foreach ($blockmarkers as $marker) {
                if (substr_compare($line, $marker, 0, strlen($marker)) === 0) {
                    $block = $block === $marker ? '' : $marker;
                }
            }
            if (!$matched) {
                $matched = mb_stripos($line, $query) !== false;
            }
            if ($matched) {
                if ($queried) {
                    $line = preg_replace('#(' . preg_quote($query, '#') . ')#ui', '<mark class="highlighted">$1</mark>', $line);
                }
                $summary .= $line;
                $mblen += mb_strlen($line);
            }
            if ($mblen >= $list_length) {
                break;
            }
        }

        $summary .= $block . "\n";

        return $summary;
    }

    /**
     * @return File[]
     */
    public function contents(): array
    {
        $result = [
            new File($this->file->changeExtension('.html'), $this->html()),
        ];

        $dom = $this->markup('');
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//img') as $img) {
            /** @var \DOMElement $img */
            $src = $img->getAttribute('src');
            if (!parse_url($src, PHP_URL_HOST)) {
                $imgfile = new File($this->file->parent() . '/' . $src);
                if ($imgfile->exists()) {
                    $result[] = $imgfile;
                }
            }
        }

        return $result;
    }

    public function markup(string $query): \DOMDocument
    {
        if ($this->dom !== null) {
            return $this->dom;
        }

        libxml_use_internal_errors(true);

        $html = Markdown::render($this->plain($query), $this->options);
        $this->dom = new class() extends \DOMDocument {
            public function __toString() { return $this->saveHTML($this->documentElement); }
        };
        $this->dom->loadHTML("<?xml encoding=\"UTF-8\"><article>$html</article>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL);

        $headers = [];
        foreach (iterator_to_array($this->dom->documentElement->childNodes) as $childNode) {
            if (in_array($childNode->nodeName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
                /** @var \DOMElement $childNode */
                $hash = '_' . sha1($childNode->textContent);
                for ($i = 1; true; $i++) {
                    $id = "$hash-$i";
                    if (!isset($headers[$id])) {
                        $headers[$id] = true;
                        break;
                    }
                }

                $childClass = $childNode->getAttribute('class');
                $childNode->setAttribute('class', trim($childClass . ' section-header'));
                $mainHeader = strpos($childClass, 'main-header') !== false;
                $subHeader = strpos($childClass, 'sub-header') !== false;

                $section = $this->dom->createElement('section');
                $section->setAttribute('id', $id);
                $section->setAttribute('class', 'section section-level-' . $childNode->nodeName . ($mainHeader ? ' main-section' : '') . ($subHeader ? ' sub-section' : ''));
                $section->setAttribute('data-section-level', substr($childNode->nodeName, 1));
                $this->dom->documentElement->appendChild($section);
            }
            if (isset($section)) {
                $section->appendChild($childNode);
            }
        }

        $xpath = new \DOMXPath($this->dom);
        foreach ($xpath->query('//a') as $a) {
            $href = $a->getAttribute('href');
            if (!parse_url($href, PHP_URL_HOST) && pathinfo($href, PATHINFO_EXTENSION) === '') {
                if (($href[0] ?? '') === '/') {
                    $fullpath = $this->docroot . '/' . $href;
                }
                else {
                    $fullpath = $this->file->parent() . '/' . $href;
                }
                if (!is_dir($fullpath) && is_file("$fullpath.md")) {
                    $a->setAttribute('href', $href . ($this->download ? '.html' : '.md'));
                }
            }
        }

        return $this->dom;
    }

    public function archive(): File
    {
        $this->options['soft_limit'] = $this->hard_limit;
        $this->options['docroot'] = $this->file->parent();
        $this->options['singlefile'] = $this->file->exists();

        $alias = pathinfo($this, PATHINFO_FILENAME);
        $tmpdir = sys_get_temp_dir() . '/htmarkdown';
        @mkdir($tmpdir);
        $basename = tempnam($tmpdir, 'hmd');
        register_shutdown_function('unlink', $basename);

        $addContents = function (\ZipArchive $zip, File $from, array $files) {
            foreach ($files as $file) {
                /** @var File $file */
                $zip->addFromString($file->relative($from->parent()), $file->contents());
            }
        };

        if ($this->file->exists()) {
            $contents = $this->contents();
            if (count($contents) === 1) {
                file_put_contents($basename, reset($contents)->contents());
                return (new File($basename))->alias("$alias.html");
            }

            $zip = new \ZipArchive();
            $zip->open($basename, \ZipArchive::CREATE);
            $addContents($zip, $this->file, $contents);
            $zip->close();
            return (new File($basename))->alias("$alias.zip");
        }
        else {
            $zip = new \ZipArchive();
            $zip->open($basename, \ZipArchive::CREATE);

            $addContents($zip, $this->file, $this->contents());
            foreach ($this->descendants() as $it) {
                if ($zip->numFiles >= $this->hard_limit) {
                    $zip->addFromString('archive.log', "too many entries limitation by hard limit ({$this->hard_limit})");
                    break;
                }
                $addContents($zip, $this->file, $it->contents());
            }
            $zip->close();
            return (new File($basename))->alias("$alias.zip");
        }
    }

    public function plain(string $query): string
    {
        if (!strlen($query) && $this->file->exists()) {
            return $this->file->contents();
        }

        $link = function (self $item) use ($query) {
            if (!strlen($query)) {
                return $item->localName();
            }

            $link = $item->localPath($this);
            if (!$item->isDirectoryIndex()) {
                return $link;
            }
            return dirname($link) . '/';
        };

        $metadata = function ($length, $unit, $time) {
            return vsprintf("<small class='metadata'>%s $unit, %s</small>\n\n", [
                number_format($length),
                date("Y/m/d H:i:s", $time),
            ]);
        };

        $count = 0;
        $contents = '';

        $items = strlen($query) ? $this->search($query) : $this->children();
        foreach ($items as $item) {
            $count++;

            if (!$item->file->exists()) {
                if (count($item->children())) {
                    $summary = $item->summary($this, $query);
                    $contents .= "## [{$link($item)}]({$item->localPath($this)})\n\n";
                    $contents .= $metadata(count($item->children()), 'item', $item->file->parent()->mtime());
                    $contents .= $summary . "\n";
                }
            }
            else {
                $summary = $item->summary($this, $query);
                $contents .= "## [{$link($item)}]({$item->localPath($this)})\n\n";
                $contents .= $metadata($item->file->size(), 'byte', $item->file->mtime());
                if (strlen($summary)) {
                    $contents .= "<div class='internal-file'>\n\n" . $summary;
                    $contents .= "\n\n</div>\n<div class='internal-file-end'>\n\n</div>\n";
                }
                $contents .= "\n\n";
            }
        }

        if (strlen($query)) {
            $header = "# Search Results '" . htmlspecialchars($query) . "'\n\n";
            $header .= $metadata($count, 'item', time());
        }
        else {
            $header = "# Index of {$this->localName()}\n\n";
            $header .= $metadata($count, 'item', $this->file->parent()->mtime());
        }

        return $header . $contents;
    }

    public function html(): string
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return (function () {
            ob_start();
            include(func_get_arg(0));
            return ob_get_clean();
        })(__DIR__ . '/../template/article.html.php');
    }
}
