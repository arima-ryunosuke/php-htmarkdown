document.addEventListener('DOMContentLoaded', function () {
    /// ショートカットとユーティリティとプロトタイプ拡張
    const $ = document.querySelector.bind(document);
    const $$ = document.querySelectorAll.bind(document);
    const html = $('html');
    const body = $('body');
    const scroller = $('.wy-side-scroll');
    const outline = $('.markdown-toc');
    const article = $('.markdown-body>article');
    const sentinel = $('.sentinel');
    const controlPanel = $('.control-panel');
    const Timer = function (interval, callback) {
        let timerId = null;
        this.start = function () {
            clearTimeout(timerId);
            timerId = setTimeout(callback, interval);
        };
        this.stop = function () {
            clearTimeout(timerId);
        };
    };
    Window.prototype.requestIdleCallback = Window.prototype.requestIdleCallback || function (callback) {
        setTimeout(callback);
    };
    Element.prototype.$ = Element.prototype.querySelector;
    Element.prototype.$$ = Element.prototype.querySelectorAll;
    Element.prototype.appendChildren = function (nodes) {
        for (const node of Object.values(document.createElements(nodes))) {
            this.appendChild(node);
        }
    }
    Document.prototype.createElements = function (nodes) {
        const elements = [];
        for (const [tagname, attributes] of Object.entries(nodes)) {
            const element = document.createElement(tagname);
            for (const [name, attr] of Object.entries(attributes)) {
                if (name === 'class') {
                    element.className = (attr instanceof Array ? attr : [attr]).join(' ');
                }
                else if (name === 'dataset') {
                    for (const [name, value] of Object.entries(attr)) {
                        element.dataset[name] = value;
                    }
                }
                else if (name === 'children') {
                    const append = function (e) {
                        if (e instanceof Array) {
                            e.forEach(e => append(e));
                        }
                        else if (e instanceof Node || typeof (e) === "string") {
                            element.append(e);
                        }
                        else if (e instanceof Object) {
                            element.appendChildren(e);
                        }
                    };
                    append(attr);
                }
                else {
                    element[name] = attr;
                }
            }
            elements.push(element);
        }
        return elements;
    };
    NodeList.prototype.observeIntersection = function (opts) {
        const observer = new IntersectionObserver(function (entries, observer) {
            entries.forEach((e) => (opts.change ?? function () {})(e, observer));
            entries.filter(e => e.isIntersecting).forEach((e) => (opts.intersect ?? function () {})(e, observer));
            entries.filter(e => !e.isIntersecting).forEach((e) => (opts.notIntersect ?? function () {})(e, observer));
        }, opts);
        this.forEach(node => observer.observe(node));
        return observer;
    };
    HTMLInputElement.prototype.getValue = HTMLSelectElement.prototype.getValue = function () {
        if (this.tagName === 'SELECT') {
            return this.$('option:checked').value;
        }
        else if (this.type === 'checkbox') {
            return '' + this.checked;
        }
        else {
            return this.value;
        }
    };
    HTMLInputElement.prototype.setValue = HTMLSelectElement.prototype.setValue = function (value) {
        if (this.tagName === 'SELECT') {
            this.$('option[value="' + value + '"]').selected = true;
        }
        else if (this.type === 'checkbox') {
            this.checked = value === 'true';
        }
        else {
            this.value = value;
        }
    };

    /// 印刷前の処理
    window.addEventListener('beforeprint', function (e) {
        // Intersection で highlight.js してるので、されていないものが居る
        article.$$('pre>div').forEach(function (node) {
            hljs.highlightBlock(node);
        });

        // details を開かないと印刷されない
        article.$$('details').forEach(function (node) {
            node.open = true;
        });
    });

    /// いくつか標準ではつかない class があるので付与
    article.$$('table').forEach(e => e.classList.add('docutils', 'align-default'));
    article.$$('ul').forEach(e => e.classList.add('simple'));

    /// セクション由来のアウトラインの構築
    const levels = (new Array(6)).fill(0);
    const idmap = {};
    article.$$('.section').forEach(function (section) {
        const sectionId = `toc-${section.id}`;
        const sectionTitle = section.$('.section-header').textContent;
        const sectionLevel = +section.dataset.sectionLevel;

        levels[sectionLevel - 1]++;
        levels.fill(null, sectionLevel);

        const blockId = levels.filter(v => v !== null).join('.');
        const parentId = levels.filter(v => v !== null).slice(0, -1).join('.');

        idmap[blockId] = sectionId;
        const parent = document.getElementById(idmap[parentId]);
        if (parent) {
            parent.dataset.childCount = (+parent.dataset.childCount + 1) + '';
        }

        outline.appendChildren({
            a: {
                id: sectionId,
                href: `#${section.id}`,
                title: sectionTitle,
                class: ['toc-h', `toc-h${sectionLevel}`],
                dataset: {
                    sectionCount: '0',
                    sectionLevel: sectionLevel,
                    blockId: blockId,
                    parentBlockId: parentId,
                    childCount: '0',
                    state: '',
                },
                children: [
                    ' ' + sectionTitle,
                    {
                        a: {
                            class: 'toggler icon',
                        }
                    }
                ],
            },
        });
    });

    /// アウトラインのハイライト監視
    article.$$('.section').observeIntersection({
        rootMargin: '0px 0px 0px 0px',
        change: function (e) {
            let toch = document.getElementById(`toc-${e.target.id}`);
            if (html.dataset.tocActive === 'false') {
                while (toch.clientHeight <= 1) {
                    toch = toch.previousElementSibling;
                }
            }
            toch.dataset.sectionCount = (Math.max(+toch.dataset.sectionCount + (e.isIntersecting ? +1 : -1), 0)) + '';

            if (html.dataset.tocActive === 'false') {
                return;
            }

            if (toch.dataset.sectionCount > 0) {
                while (toch) {
                    const siblings = outline.$$(`[data-parent-block-id="${toch.dataset.parentBlockId}"]`);
                    siblings.forEach(e => e.classList.add('visible'));
                    toch = outline.$(`[data-block-id="${toch.dataset.parentBlockId}"]`);
                }
            }
            else {
                while (toch) {
                    const descendants = outline.$$(`[data-parent-block-id^="${toch.dataset.parentBlockId}"]`);
                    if (Array.prototype.filter.call(descendants, e => !e.matches('[data-section-count="0"]')).length === 0) {
                        descendants.forEach(e => e.classList.remove('visible'));
                    }
                    toch = outline.$(`[data-block-id="${toch.dataset.parentBlockId}"]`);
                }
            }
        },
    });

    /// コードブロックの highlight.js 監視
    article.$$('pre>div').observeIntersection({
        rootMargin: '0px 0px 10% 0px',
        intersect: function (e, observer) {
            hljs.highlightBlock(e.target);
            observer.unobserve(e.target);
        },
    });

    /// 記事末尾の空白を確保（ジャンプしたときに「え？どこ？」となるのを回避する）
    const lastSection = $('.section:last-child');
    if (lastSection) {
        const height = sentinel.offsetTop - lastSection.offsetTop + parseInt(getComputedStyle(lastSection).marginTop);
        sentinel.style.height = `calc(100vh - ${height}px)`;
    }

    /// コンパネ
    $('.rst-current-version').addEventListener('click', function (e) {
        e.target.closest('.rst-versions').classList.toggle('shift-up');
    });
    controlPanel.addEventListener('change', function (e) {
        if (e.target.matches('[data-input-name]')) {
            html.dataset[e.target.dataset.inputName] = e.target.getValue();
            controlPanel.save();
        }
        if (e.target.id === 'highlight_css') {
            const highlight_style = $('#highlight_style');
            highlight_style.href = highlight_style.dataset.cdnUrl + e.target.getValue() + '.min.css';
        }
        if (e.target.id === 'toc_width') {
            document.documentElement.style.setProperty('--side-width', e.target.getValue() + 'px');
        }

    });
    controlPanel.save = function () {
        const savedata = {};
        this.$$('[data-input-name]').forEach(function (input) {
            savedata[input.dataset.inputName] = input.getValue();
        });
        localStorage.setItem('ht-setting', JSON.stringify(savedata));
    };
    controlPanel.load = function () {
        const e = new Event('change', {bubbles: true});
        const savedata = JSON.parse(localStorage.getItem('ht-setting') ?? '{"tocNumber":"true","tocLevel":"5"}');
        this.$$('[data-input-name]').forEach(function (input) {
            input.setValue(savedata[input.dataset.inputName] ?? input.dataset.defaultValue);
            input.dispatchEvent(e);
        });
    };
    controlPanel.load();

    /// アウトラインの開閉ボタン
    outline.addEventListener('mouseenter', function (e) {
        if (e.target.matches('a.toc-h')) {
            const toch = e.target;
            if (toch.dataset.sectionLevel >= html.dataset.tocLevel) {
                if (+toch.dataset.childCount) {
                    const tochs = outline.$$(`[data-parent-block-id="${toch.dataset.blockId}"]`);
                    const visibles = Array.prototype.filter.call(tochs, e => e.matches('.visible,.forced-visible'));
                    if (tochs.length === visibles.length) {
                        toch.dataset.state = 'close';
                    }
                    else {
                        toch.dataset.state = 'open';
                    }
                }
            }
        }
    }, true);
    outline.addEventListener('mouseleave', function (e) {
        if (e.target.matches('a.toc-h')) {
            e.target.dataset.state = '';
        }
    }, true);
    outline.addEventListener('click', function (e) {
        if (e.target.matches('a.toggler')) {
            const toch = e.target.parentElement;
            if (toch.dataset.state === 'open') {
                toch.dataset.state = 'close';
                outline.$$(`[data-parent-block-id="${toch.dataset.blockId}"]`).forEach(e => e.classList.add('forced-visible', 'visible'));
            }
            else {
                toch.dataset.state = 'open';
                outline.$$(`[data-parent-block-id^="${toch.dataset.blockId}"]`).forEach(e => e.classList.remove('forced-visible', 'visible'));
            }
            e.preventDefault();
            return false;
        }
    });

    /// アウトラインのクリックイベント
    let intoViewScrolling = false;
    outline.addEventListener('click', function (e) {
        if (e.target.matches('a.toc-h')) {
            e.preventDefault();
            const section = $$(e.target.getAttribute('href'));
            intoViewScrolling = true;
            section[0].scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
            section.observeIntersection({
                rootMargin: '0px 0px -99.99% 0px',
                intersect: function (e, observer) {
                    observer.unobserve(e.target);
                    requestIdleCallback(function () {
                        intoViewScrolling = false;
                    });
                },
            });
        }
    });

    // アウトラインのスクロールの自動追従
    const followMenuTimer = new Timer(32, function () {
        const visibles = $$('.toc-h:not([data-section-count="0"])');
        visibles[Math.floor(visibles.length / 2)].scrollIntoView({
            behavior: "smooth",
            block: "center",
        });
    });
    document.addEventListener('scroll', function (e) {
        if (!intoViewScrolling && html.dataset.tocFollow === 'true') {
            followMenuTimer.stop();
            followMenuTimer.start();
        }
    });

    /// スクロールバーを自動的に隠す
    const scrollHideTimer = new Timer(3000, function () {
        scroller.classList.remove('scrolling');
    });
    scroller.addEventListener('scroll', function () {
        scrollHideTimer.stop();
        scroller.classList.add('scrolling');
        scrollHideTimer.start();
    });

    /// stop initial animation
    requestIdleCallback(function () {
        document.documentElement.style.setProperty('--initial-animation-ms', '500ms');
    });
});
