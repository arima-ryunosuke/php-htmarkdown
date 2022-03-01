<?php
$h = function ($string) { return htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); };

/** @var \ryunosuke\HtMarkdown\Document $this */
$CDN = "https://cdn.jsdelivr.net";
$article = $this->markup($_GET['query'] ?? '') ?? '';
$locale = $this->locale();
$parents = $this->parents();
$siblings = $this->siblings();
?>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $h($this->localName()) ?></title>

    <link id="sphinx_rtd_theme" href="<?= $CDN ?>/npm/sphinx_rtd_theme@0.4.2/css/theme.css" rel="stylesheet">
    <link id="highlight_style" data-cdn-url="<?= $CDN ?>/npm/highlightjs@9.16.2/styles/" href="" rel="stylesheet">
    <style><?php include __DIR__ . '/style.css.php' ?></style>
    <?php if (strlen($this->css_file)): ?>
        <style><?php include $this->css_file ?></style>
    <?php endif ?>

    <script defer src="<?= $CDN ?>/npm/highlightjs@9.16.2/highlight.pack.min.js"></script>
    <script defer src="<?= $CDN ?>/npm/mermaid@8.14.0/dist/mermaid.min.js"></script>
    <script defer><?php include __DIR__ . '/script.js.php' ?></script>
    <?php if (strlen($this->js_file)): ?>
        <script><?php include $this->js_file ?></script>
    <?php endif ?>
</head>

<body class="wy-body-for-nav <?= $this->download ? 'downloaded' : '' ?> <?= $this->singlefile ? 'singlefile' : '' ?>">
<div class="wy-grid-for-nav">
    <nav data-toggle="wy-nav-shift" class="wy-nav-side">
        <div class="wy-side-scroll">
            <div class="wy-side-nav-search">
                <a href="" class="icon icon-home"> <?= $h($this->localName()) ?></a>
                <div class="version">
                    <?= $h($locale['last_modified']) ?> <?= $h(date('Y/m/d H:i:s', $this->lastModified())) ?>
                </div>
                <div role="search">
                    <form id="rtd-search-form" class="wy-form hidden-download" method="get">
                        <input type="text" name="query" placeholder="<?= $h($locale['search_docs']) ?>" value="<?= $h($_GET['query'] ?? '') ?>">
                    </form>
                </div>
            </div>
            <div class="wy-menu wy-menu-vertical markdown-toc">
                <p class="caption"><span class="caption-text">Table of Contents</span></p>
            </div>
        </div>
    </nav>
    <div data-toggle="wy-nav-shift" class="wy-nav-content-wrap">
        <nav class="wy-nav-top" aria-label="Mobile navigation menu">
            <i data-toggle="wy-nav-top" class="fa fa-bars" data-toggle-target="[data-toggle=&quot;wy-nav-shift&quot;]" data-toggle-class="shift"></i>
            <a href=""> <?= $h($this->localName()) ?></a>
        </nav>
        <div class="wy-nav-content">
            <div class="rst-content">
                <header class="hidden-single">
                    <ul class="wy-breadcrumbs">
                        <li class="top-breadcrumb"><a href="/" class="icon icon-home"></a> »</li>
                        <?php foreach (array_reverse($parents, true) as $n => $parent): ?>
                            <li><a href="<?= $h($parent->localPath($this)) ?>"><?= $h($parent->localName()) ?></a> »</li>
                        <?php endforeach ?>
                        <li><?= $h($this->localName()) ?></li>
                        <li class="wy-breadcrumbs-aside hidden-download">
                            <a href="?raw=true" class="fa fa-file-text"> <?= $h($locale['view_markdown']) ?></a>
                            <a href="?dl=true" class="fa fa-download" download> <?= $h($locale['download_file']) ?></a>
                            <a id="button-export" href="javascript:void(0)" class="fa fa-arrow-circle-down"> <?= $h($locale['export_file']) ?></a>
                        </li>
                    </ul>
                    <hr>
                </header>
                <main class="markdown-body">
                    <?= $article ?>
                </main>
                <footer>
                    <div class="rst-footer-buttons hidden-single">
                        <div>
                            <?php if (isset($siblings[-1])): ?>
                                <a href="<?= $h($siblings[-1]->localPath($this)) ?>" class="btn btn-neutral" accesskey="p" rel="prev"><span class="fa fa-arrow-circle-left" aria-hidden="true"></span> <?= $h($siblings[-1]->localName()) ?></a>
                            <?php endif ?>
                        </div>
                        <div>
                            <?php if (isset($parents[0])): ?>
                                <a href="<?= $h($parents[0]->localPath($this)) ?>" class="btn btn-neutral" accesskey="u" rel="up"><span class="fa fa-arrow-circle-up" aria-hidden="true"></span> <?= $h($parents[0]->localName()) ?></a>
                            <?php endif ?>
                        </div>
                        <div>
                            <?php if (isset($siblings[+1])): ?>
                                <a href="<?= $h($siblings[+1]->localPath($this)) ?>" class="btn btn-neutral" accesskey="n" rel="next"><?= $h($siblings[+1]->localName()) ?> <span class="fa fa-arrow-circle-right" aria-hidden="true"></span></a>
                            <?php endif ?>
                        </div>
                    </div>
                    <hr>
                    <div role="contentinfo">
                        <p>© Copyright Dave Snider, Read the Docs, Inc. &amp; contributors.</p>
                    </div>
                    Built with htmarkdown Using a
                    <a href="https://github.com/readthedocs/sphinx_rtd_theme">theme</a>
                    provided by <a href="https://readthedocs.org">Read the Docs</a>.
                </footer>
            </div>
        </div>
        <div class="sentinel"></div>
    </div>
</div>
<div data-toggle="wy-nav-shift" class="rst-versions">
    <span class="rst-current-version" data-toggle-target=".rst-versions" data-toggle-class="shift-up">
        <span class="fa fa-cog pull-left"> <?= $h($locale['control_panel']) ?></span>
        <span class="fa fa-caret-down"></span>
    </span>
    <div class="rst-other-versions">
        <form class="control-panel">
            <dl>
                <dt>Head</dt>
                <dd>
                    <label class="option-title" for="toc_width"><?= $h($locale['toc_width']) ?></label>
                    <input id="tocWidth" class="option-input savedata" data-default-value="300" type="number" min="10" max="700">
                </dd>
                <dd>
                    <label class="option-title" for="toc_level"><?= $h($locale['toc_level']) ?></label>
                    <input id="tocLevel" class="option-input savedata" data-default-value="5" type="number" min="1" max="6">
                </dd>
                <dd>
                    <label class="option-title" for="toc_number"><?= $h($locale['toc_number']) ?></label>
                    <input id="tocNumber" class="option-input savedata" data-default-value="true" type="checkbox" value="1">
                </dd>
                <dd>
                    <label class="option-title" for="toc_follow"><?= $h($locale['toc_follow']) ?></label>
                    <input id="tocFollow" class="option-input savedata" data-default-value="true" type="checkbox" value="1">
                </dd>
                <dd>
                    <label class="option-title" for="toc_active"><?= $h($locale['toc_active']) ?></label>
                    <input id="tocActive" class="option-input savedata" data-default-value="true" type="checkbox" value="1">
                </dd>
                <dt>Body</dt>
                <dd>
                    <label class="option-title" for="section_indent"><?= $h($locale['section_indent']) ?></label>
                    <input id="sectionIndent" class="option-input savedata" data-default-value="0" type="number" min="0" max="6">
                </dd>
                <dd>
                    <label class="option-title" for="highlight_css"><?= $h($locale['highlight_css']) ?></label>
                    <select id="highlightCss" class="option-input savedata" data-default-value="default">
                        <?php foreach (['default', 'zenburn', 'github', 'vs', /* and more */] as $cssname): ?>
                            <option value="<?= $h($cssname) ?>"><?= $h($cssname) ?></option>
                        <?php endforeach ?>
                    </select>
                </dd>
                <dd>
                    <label class="option-title" for="link_url"><?= $h($locale['link_url']) ?></label>
                    <input id="linkUrl" class="option-input savedata" data-default-value="true" type="checkbox" value="1">
                </dd>
                <dd>
                    <label class="option-title" for="break_line"><?= $h($locale['break_line']) ?></label>
                    <input id="breakLine" class="option-input savedata" data-default-value="true" type="checkbox" value="1">
                </dd>
            </dl>
        </form>
    </div>
</div>
</body>

</html>
