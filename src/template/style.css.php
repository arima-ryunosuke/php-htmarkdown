/* override theme.css */

h1, h2, h3, h4, h5, h6 {
    border-bottom: 1px solid #E1E4E5;
}

h2, h3, h4, h5, h6 {
    margin-top: 20px;
}

.wy-nav-content {
    max-width: 100%;
    height: unset;
}

.wy-side-scroll.scrolling {
    width: 300px;
}

.wy-side-scroll.no-scrollbar {
    width: 320px;
}

.wy-side-scroll.scrolling-end {
    transition: all 500ms 0s ease;
}

.wy-side-scroll::-webkit-scrollbar {
    width: 12px;
}

.wy-side-scroll::-webkit-scrollbar-track {
    background: #e1e1e1;
}

.wy-side-scroll::-webkit-scrollbar-thumb {
    border-radius: 10px;
    background: #a9a9a9;
}

.rst-content code {
    white-space: inherit;
}

.rst-content pre div.code {
    padding: 8px 12px;
    line-height: 16px;
    max-width: 100%;
    border: solid 1px #e1e4e5;
    font-size: 75%;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", Courier, monospace;
    overflow-x: auto;
}

.rst-content .section {
    clear: both;
}

.rst-content .section ol li > *,
.rst-content .section ul li > *,
.rst-content .section dl dd > * {
    margin-top: 0;
}

.rst-content .section ol li > ol,
.rst-content .section ol li > ul,
.rst-content .section ol li > dl,
.rst-content .section ul li > ol,
.rst-content .section ul li > ul,
.rst-content .section ul li > dl,
.rst-content .section dl dd > ol,
.rst-content .section dl dd > ul,
.rst-content .section dl dd > dl {
    margin-bottom: 0;
}

.rst-content dl.field-list {
    display: grid;
    grid-template-columns: max-content auto;
    overflow-x: auto;
}

.rst-content details {
    margin-bottom: 24px;
}

.rst-content details summary {
    cursor: pointer;
}

.rst-content .admonition-title:empty {
    display: none;
}

.rst-content .sidebar-title:empty {
    display: none;
}

.rst-footer-buttons {
    display: flex;
}

.rst-footer-buttons:before, .rst-footer-buttons:after {
    display: initial;
    content: unset;
}

.rst-footer-buttons div:nth-child(1) {
    width: calc(100% / 3);
    text-align: left;
}

.rst-footer-buttons div:nth-child(2) {
    width: calc(100% / 3);
    text-align: center;

}

.rst-footer-buttons div:nth-child(3) {
    width: calc(100% / 3);
    text-align: right;

}

/* table of contents */

.markdown-toc {
    padding-bottom: 8px;
}

[data-toc-number="false"] .toc-n {
    display: none;
}

[data-toc-level="1"] .toc-h2,
[data-toc-level="1"] .toc-h3,
[data-toc-level="1"] .toc-h4,
[data-toc-level="1"] .toc-h5,
[data-toc-level="1"] .toc-h6,
[data-toc-level="2"] .toc-h3,
[data-toc-level="2"] .toc-h4,
[data-toc-level="2"] .toc-h5,
[data-toc-level="2"] .toc-h6,
[data-toc-level="3"] .toc-h4,
[data-toc-level="3"] .toc-h5,
[data-toc-level="3"] .toc-h6,
[data-toc-level="4"] .toc-h5,
[data-toc-level="4"] .toc-h6,
[data-toc-level="5"] .toc-h6 {
    display: none;
}

.toc-h {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.toc-h:not([data-section-count="0"]) {
    font-weight: bold;
    background-color: #4e4a4a;
}

<?php foreach(range(1, 6) as $n): ?>

a.toc-h<?= $n ?> {
    font-size: <?= 100 - (($n - 1) * 4) ?>%;
    padding-left: <?= ($n - 1) + 1.35 ?>rem;
}

.toc-n<?= $n ?> {
    color: #a9a9a9;
}

<?php endforeach ?>

/* article */

small.metadata {
    font-family: sans-serif;
    display: block;
    text-align: right;
}

.section-level-h1 small.metadata {
    font-size: 100%;
    margin-top: -3.3rem;
}

.section-level-h2 small.metadata {
    font-size: 90%;
    margin-top: -2.7rem;
}

<?php foreach(range(1, 6) as $n): ?>
<?php foreach(range(1, 6) as $m): ?>

[data-section-indent="<?= $n ?>"] [data-section-level="<?= $m ?>"] {
    padding-left: <?= ($m - 1) * $n ?>rem;
}

<?php endforeach ?>
<?php endforeach ?>

.admonition-title:empty {
    display: none;
}

.admonition-body {
    white-space: pre-line;
}

.internal-file {
    /*
    transform: scale(0.5);
    transform-origin: top left;
    height: 50%;
    width: 200%;
    */
    zoom: 0.5;
    background: white;
    padding: 2rem;
}

pre[data-label]:not([data-label=""]):before {
    content: attr(data-label);
    background: gray;
    color: #fff;
    padding: 2px;
    position: absolute;
    margin-top: 1px;
    margin-left: 1px;
    font-size: 85%;
}

pre[data-label]:not([data-label=""]) div.code {
    padding-top: 32px
}

@media print {
    .sentinel {
        display: none;
    }
}

/* control panel */

.option-title {
    color: #fcfcfc;
    display: inline-block;
    width: 176px;
}

.option-input {
    display: inline-block;
    height: 18px;
    vertical-align: text-bottom;
    padding: 0;
}

[type="checkbox"].option-input {
    width: 16px;
    cursor: pointer;
}

[type="number"].option-input {
    width: 60px;
    text-align: right;
}

select.option-input {
    width: 90px;
}

/* utility */

a.disabled {
    pointer-events: none;
    color: gray;
}

.singlefile .hidden-single {
    display: none;
}

.downloaded .hidden-download {
    display: none;
}
