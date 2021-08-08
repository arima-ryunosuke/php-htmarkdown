/* override theme.css */
:root {
    --initial-animation-ms: 0ms;
    --side-width: 300px;
}

h1, h2, h3, h4, h5, h6 {
    border-bottom: 1px solid #E1E4E5;
}

h2, h3, h4, h5, h6 {
    margin-top: 20px;
}

.caption {
    display: block;
    font-weight: bold;
    margin-bottom: 4px;
}

.wy-nav-content {
    max-width: 100%;
    height: unset;
}

.wy-tray-container li {
    width: var(--side-width);
}

.wy-menu-vertical {
    width: var(--side-width);
}

.wy-side-nav-search {
    width: var(--side-width);
}

.wy-nav-side {
    width: var(--side-width);
}

.wy-nav-content-wrap {
    margin-left: var(--side-width);
}

@media screen and (max-width: 768px) {
    .wy-nav-side {
        width: calc(0 -var(--side-width));
    }
}

.rst-versions {
    width: var(--side-width);
}

.wy-side-scroll {
    transition: width var(--initial-animation-ms) 0s ease;
    width: calc(var(--side-width) + 20px);
    overscroll-behavior: contain;
}

.wy-side-scroll.scrolling {
    transition-duration: 0s;
    width: var(--side-width);
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

.rst-content .sidebar.right {
    padding: 0;
    margin: 0;
    background: transparent;
    border: none;
    width: auto;
}

.rst-content .sidebar.right p {
    margin-bottom: 0;
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

.rst-versions .rst-other-versions dd {
    display: block;
}

/* table of contents */

.markdown-toc {
    padding-bottom: 8px;
}

[data-toc-number="true"] [data-block-id]:before {
    content: attr(data-block-id);
    color: #a9a9a9;
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
    visibility: hidden;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.toc-h.visible,
.toc-h.forced-visible {
    visibility: visible;
    max-height: 36px;
    padding-top: .4045em;
    padding-bottom: .4045em;
}

.toc-h {
    transition-property: all;
    transition-delay: 0s;
    transition-duration: var(--initial-animation-ms);
    transition-timing-function: ease;
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

a.toc-h<?= $n ?> a.toggler {
    left: <?= ($n - 2) + 1.1 ?>rem;
}

<?php endforeach ?>

a.toggler {
    position: absolute;
    top: 0;
    left: 0;
    font-size: inherit;
    display: inline-block;
    padding: .4045em 0;
    text-align: center;
}

a.toggler:before {
    line-height: inherit;
    opacity: 0.7;
}

a.toggler:hover:before {
    opacity: 1;
}

[data-state=""] a.toggler:before {
    content: "";
}

[data-state="open"] a.toggler:before {
    content: "";
}

[data-state="close"] a.toggler:before {
    content: "";
}

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

[data-section-indent="<?= $n ?>"] section[data-section-level="<?= $m ?>"] {
    padding-left: <?= ($m) * $n ?>rem;
}

<?php endforeach ?>
<?php endforeach ?>

html:not([data-section-indent="0"]) section[data-section-level] .section-header {
    margin-left: -1rem;
}

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

.badge {
    margin-right: 5px;
    padding: 3px 6px 3px 0;
    font-size: 80%;
    white-space: nowrap;
    border-radius: 4px;
    color: white;
    background-color: #666666;
}

.badge[data-badge-title=""] {
    padding-left: 6px;
}

.badge:not([data-badge-title=""]):before {
    content: attr(data-badge-title);
    padding: 3px 6px;
    margin-right: 6px;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
}

.badge[data-badge-title=""].info {
    background: #6ab0de;
}

.badge[data-badge-title=""].success {
    background: #1abc9c;
}

.badge[data-badge-title=""].notice {
    background: #f0b37e;
}

.badge[data-badge-title=""].alert {
    background: #f29f97;
}

.badge:not([data-badge-title=""]).info:before {
    background: #6ab0de;
}

.badge:not([data-badge-title=""]).success:before {
    background: #1abc9c;
}

.badge:not([data-badge-title=""]).notice:before {
    background: #f0b37e;
}

.badge:not([data-badge-title=""]).alert:before {
    background: #f29f97;
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
    width: 210px;
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
