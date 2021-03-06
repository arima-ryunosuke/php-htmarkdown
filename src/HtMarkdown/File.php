<?php

namespace ryunosuke\HtMarkdown;

class File
{
    private $original;

    private $alias;

    private $contents;

    public function __construct(string $fullpath, string $contents = null)
    {
        $this->original = $fullpath;
        $this->contents = $contents;
    }

    public function __toString()
    {
        return $this->alias ?? $this->original;
    }

    public function alias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function parent(): self
    {
        return new self($this->dirname());
    }

    public function relative(self $that): self
    {
        $fa = array_filter(explode('/', $that->normalize()), 'strlen');
        $ta = array_filter(explode('/', $this->normalize()), 'strlen');

        $ca = array_udiff_assoc($fa, $ta, 'strcmp');
        $da = array_udiff_assoc($ta, $fa, 'strcmp');

        return new self(str_repeat("../", count($ca)) . implode('/', $da));
    }

    public function normalize(): string
    {
        $ds = '/';
        if (DIRECTORY_SEPARATOR === '\\') {
            $ds .= '\\\\';
        }

        $result = [];
        foreach (preg_split("#[$ds]#u", $this->original) as $n => $part) {
            if ($n > 0 && $part === '') {
                continue;
            }
            if ($part === '.') {
                continue;
            }
            if ($part === '..') {
                if (empty($result)) {
                    throw new \InvalidArgumentException("'$this->original' is invalid as path string.");
                }
                array_pop($result);
                continue;
            }
            $result[] = $part;
        }
        return implode('/', $result);
    }

    public function realpath(): ?string
    {
        $realpth = realpath($this->original);
        return $realpth === false ? null : $realpth;
    }

    public function dirname(int $levels = 1): string
    {
        return dirname($this->original, $levels);
    }

    public function basename(): string
    {
        return basename($this->original);
    }

    public function filename(): string
    {
        return pathinfo($this->original, PATHINFO_FILENAME);
    }

    public function extension(): string
    {
        return pathinfo($this->original, PATHINFO_EXTENSION);
    }

    public function changeExtension(string $ext): string
    {
        return $this->dirname() . '/' . $this->filename() . $ext;
    }

    public function exists(): bool
    {
        return file_exists($this->original);
    }

    public function match(string $pattern): bool
    {
        return fnmatch("*$pattern*", $this->original);
    }

    public function isFile(): bool
    {
        assert($this->exists());
        return is_file($this->realpath());
    }

    public function isDir(): bool
    {
        assert($this->exists());
        return is_dir($this->realpath());
    }

    public function size(): int
    {
        assert($this->exists());
        return filesize($this->original);
    }

    public function mtime(): int
    {
        assert($this->exists());
        return filemtime($this->original);
    }

    public function mimetype(): string
    {
        assert($this->exists());
        return mime_content_type($this->original);
    }

    public function contents(): ?string
    {
        if ($this->exists()) {
            if ($this->contents !== null) {
                return $this->contents;
            }
            if (pathinfo($this->filename(), PATHINFO_EXTENSION) === 'php') {
                ob_start();
                include $this->original;
                return ob_get_clean();
            }
            return file_get_contents($this->original);
        }
        return $this->contents;
    }

    public function lines(?int $length = null): \Generator
    {
        assert($this->exists());

        try {
            $fp = fopen($this->original, 'rb');

            $length = (array) $length;
            while (($line = fgets($fp, ...$length)) !== false) {
                yield $line;
            }
        }
        finally {
            if (isset($fp)) {
                fclose($fp);
            }
        }
    }
}
