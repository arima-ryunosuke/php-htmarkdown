<?php

namespace ryunosuke\HtMarkdown;

class Controller
{
    const SPECIFIABLE_OPTIONS = [
        'dummy'       => false,
        'project'     => null,
        'docroot'     => null,
        'singlehtml'  => false,
        'index_file'  => null,
        'js_file'     => null,
        'css_file'    => null,
        'locale'      => 'en',
        'sort_order'  => 'asc',
        'list_length' => 320,
        'hard_limit'  => 500,
        'ignore_path' => [
            'vendor',
            'node_modules',
        ],
    ];

    const DEFAULT_SETTINGS = [
        'font_family'    => 'sans-serif',
        'font_size'      => 16,
        'toc_visible'    => true,
        'toc_width'      => 380,
        'toc_level'      => 5,
        'toc_sticky'     => 3,
        'toc_active'     => 'some',
        'toc_number'     => true,
        'toc_child'      => true,
        'toc_follow'     => true,
        'section_indent' => 0,
        'highlight_css'  => 'default',
        'section_number' => true,
        'link_url'       => true,
        'break_line'     => 'break',
    ];

    private $server;
    private $request;

    private $headers  = [];
    private $contents = [];

    public function __construct(array $server, array $request)
    {
        $this->server = $server;
        $this->request = $request;
    }

    public function isModifiedSince(int $lastModified): bool
    {
        return $lastModified > strtotime($this->server['HTTP_IF_MODIFIED_SINCE'] ?? null);
    }

    public function getDownloadType(): ?string
    {
        return $this->request['dl'] ?? null;
    }

    public function isPlain(): bool
    {
        return !!($this->request['raw'] ?? false);
    }

    public function header(array $headers, bool $append = true): self
    {
        if (!$append) {
            $this->headers = [];
        }

        foreach ($headers as $name => $header) {
            if (is_int($name)) {
                $this->headers[] = $header;
            }
            else {
                $this->headers[] = "$name: $header";
            }
        }
        return $this;
    }

    public function content(string $content, bool $append = true): self
    {
        if (!$append) {
            $this->contents = [];
        }

        $this->contents[] = $content;
        return $this;
    }

    public function response(): self
    {
        foreach ($this->headers as $header) {
            header($header);
        }

        foreach ($this->contents as $content) {
            echo $content;
        }

        return $this;
    }

    public function handleCli(): bool
    {
        $long_options = array_map(function ($key, $value) {
            if (is_bool($value)) {
                return $key;
            }
            if (is_null($value)) {
                return "$key:";
            }
            return "$key::";
        },
            array_keys(self::SPECIFIABLE_OPTIONS),
            array_values(self::SPECIFIABLE_OPTIONS)
        );
        $long_options = array_merge($long_options, array_map(fn($setting) => "defaults.$setting::", array_keys(self::DEFAULT_SETTINGS)));

        $options = getopt('', $long_options, $rest_index) + ['dummy' => false]; // for coverage
        foreach ($options as $k => $v) {
            // false is no value option or optional value option
            if ($v === false) {
                $options[$k] = is_bool(self::SPECIFIABLE_OPTIONS[$k]) ? true : self::SPECIFIABLE_OPTIONS[$k];
            }
        }
        $options += $this->server['options'] ?? [];
        $defaults = [];
        foreach ($options as $opt => $val) {
            if (strpos($opt, 'defaults.') === 0) {
                $defaults[substr($opt, strlen('defaults.'))] = $val;
                unset($options[$opt]);
            }
        }
        $options = array_replace([
            'singlehtml' => false,
        ], $options, [
            'download' => true,
            'defaults' => $defaults + self::DEFAULT_SETTINGS,
        ]);

        $input = $this->request[$rest_index];
        $filename = realpath($input);
        if ($filename === false) {
            throw new \InvalidArgumentException("$input is not found.");
        }

        $document = new Document($filename, $options);

        $output = $this->request[$rest_index + 1] ?? null;
        if ($output !== null && !$options['singlehtml']) {
            foreach ($document->generate($output) as $name => $contents) {
                @mkdir(dirname("$output/$name"), 0777, true);
                file_put_contents("$output/$name", $contents);
            }
            return true;
        }

        if ($options['singlehtml']) {
            print($document->html());
        }
        else {
            readfile($document->archive()->realpath());
        }

        return true;
    }

    public function handleHttp(): bool
    {
        $options = [
            'navroot'    => null,
            'docroot'    => null,
            'download'   => $this->getDownloadType() !== null,
            'singlehtml' => $this->getDownloadType() === 'html',
            'locale'     => locale_accept_from_http($this->server['HTTP_ACCEPT_LANGUAGE']),
        ];
        $options += array_intersect_key($this->server, self::SPECIFIABLE_OPTIONS);

        $docroot = strtr($this->server['CONTEXT_DOCUMENT_ROOT'] ?? $this->server['DOCUMENT_ROOT'] ?? null, [DIRECTORY_SEPARATOR => '/']);
        $reqfile = parse_url($this->server['REDIRECT_URL'] ?? $this->server['REQUEST_URI'] ?? null, PHP_URL_PATH);
        $filename = rtrim($docroot . $reqfile, '/');
        $filename = strlen($this->request['query'] ?? '') && !is_dir($filename) ? dirname($filename) : $filename;
        $options['docroot'] = $docroot;

        $scriptfile = $this->server['SCRIPT_FILENAME'] ?: $docroot;
        $commons = array_intersect_assoc(mb_str_split($filename), mb_str_split($scriptfile));
        $n = 0;
        $navroot = implode('', array_filter($commons, function ($i) use (&$n) { return $i === $n++; }, ARRAY_FILTER_USE_KEY));
        $options['navroot'] = dirname("{$navroot}dummy");

        $options['defaults'] = ((array) ($this->request['defaults'] ?? [])) + self::DEFAULT_SETTINGS;

        $document = new Document($filename, $options);

        ob_start();
        try {
            if (!$document->exists()) {
                $this->header(['HTTP/1.1 404 Not Found'], false);
                return false;
            }

            $lastModified = $document->lastModified();
            if (!$this->isModifiedSince($lastModified)) {
                $this->header(['HTTP/1.1 304 Not Modified'], false);
                return false;
            }

            if (!$document->isSupported()) {
                $this->header(['Last-Modified' => date('r', $lastModified)]);
                $this->header(['Content-Type' => mime_content_type($filename)]);
                $this->content(file_get_contents($filename));
                return false;
            }

            if ($this->getDownloadType() === 'zip') {
                $file = $document->archive();
                $this->header(['Content-Disposition: attachment; filename="' . $file . '"']);
                $this->content($file->contents());
            }
            elseif ($this->getDownloadType() === 'html') {
                $this->header(['Content-Disposition: attachment; filename="' . $document->localName() . '"']);
                $this->content($document->html());
            }
            elseif ($this->isPlain()) {
                $this->header(['Last-Modified' => date('r', $lastModified)]);
                $this->header(['Content-Type' => 'text/plain']);
                $this->content($document->plain($this->request['query'] ?? ''));
            }
            else {
                $this->header(['Last-Modified' => date('r', $lastModified)]);
                $this->header(['Content-Type' => 'text/html']);
                $this->content($document->html());
            }
            return true;
        }
        finally {
            $this->response();
            ob_end_flush();
        }
    }
}
