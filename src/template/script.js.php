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
    const noop = function () {};
    const Timer = function (interval, callback) {
        this.timerId = null;
        this.interval = interval;
        this.callback = callback;
    };
    Timer.prototype.start = function () {
        clearTimeout(this.timerId);
        this.timerId = setTimeout(this.callback, this.interval);
    };
    Timer.prototype.stop = function () {
        clearTimeout(this.timerId);
    };
    Window.prototype.requestIdleCallback = Window.prototype.requestIdleCallback || function (callback) {
        setTimeout(callback);
    };
    Array.prototype.findLastIndex = function (predicate, thisArg) {
        for (let i = this.length - 1; i >= 0; i--) {
            if (predicate.call(thisArg, this[i], i, this)) {
                return i;
            }
        }
        return -1;
    };
    Element.prototype.$ = Element.prototype.querySelector;
    Element.prototype.$$ = Element.prototype.querySelectorAll;
    Element.prototype.appendChildren = function (nodes) {
        const children = document.createElements(nodes);
        children.forEach(child => this.appendChild(child));
        return children;
    };
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
                        else if (e instanceof Node || typeof (e) === 'string') {
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
    EventTarget.prototype.on = function (event, selector, handler, data) {
        if (typeof (selector) === 'function') {
            data = handler;
            handler = selector;
            selector = null;
        }
        if (typeof (data) === 'boolean') {
            data = {
                capture: data,
            };
        }
        if (data == null) {
            data = {};
        }

        if (event === 'intersect') {
            const opt = Object.assign({
                root: null,
                rootMargin: '0px',
                threshold: 0,
            }, data)
            const observer = new IntersectionObserver(function (entries, observer) {
                entries.forEach(e => handler(Object.assign(e, {observer: observer})));
            }, opt);
            const targets = selector == null ? [this] : this.$$(selector);
            targets.forEach(node => observer.observe(node));
            return observer;
        }
        if (event === 'mutate') {
            const opt = Object.assign({
                attributes: false,
                attributeOldValue: false,
                characterData: false,
                characterDataOldValue: false,
                childList: false,
                subtree: false,
            }, data)
            const observer = new MutationObserver(function (entries, observer) {
                entries.forEach(e => handler(Object.assign(e, {observer: observer})));
            });
            const targets = selector == null ? [this] : this.$$(selector);
            targets.forEach(node => observer.observe(node, opt));
            return observer;
        }

        this.addEventListener(event, function (e) {
            if (selector == null || e.target.matches(selector)) {
                return handler(e);
            }
        }, data);
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
            this.$(`option[value="${value}"]`).selected = true;
        }
        else if (this.type === 'checkbox') {
            this.checked = value === 'true';
        }
        else {
            this.value = value;
        }
    };
    HTMLImageElement.prototype.toDataURL = function (mimetype) {
        const canvas = document.createElement('canvas');
        canvas.width = this.width;
        canvas.height = this.height;
        canvas.getContext('2d').drawImage(this, 0, 0);
        return canvas.toDataURL(mimetype);
    };
    Blob.prototype.toDataURL = function () {
        const reader = new FileReader();
        const dataurl = new Promise(function (resolve, reject) {
            reader.onload = e => resolve(reader.result);
            reader.onerror = e => reject(reader.error);
        });
        reader.readAsDataURL(this);
        return dataurl;
    };

    mermaid.initialize({startOnLoad: false});

    // ハイライト
    let svg_id = 0;
    const highlight = function (element) {
        if (element.classList.contains('complete-highlight')) {
            return;
        }
        element.classList.add('complete-highlight');
        if (element.classList.contains('mermaid')) {
            mermaid.render(`mermaid_svg-${++svg_id}`, element.textContent, (svgCode, bindFunctions) => {
                element.innerHTML = svgCode;
                if (bindFunctions) {
                    bindFunctions(element);
                }
            }, element);
        }
        else {
            hljs.highlightBlock(element);
        }
    };

    /// 印刷前の処理
    const readyprint = function () {
        // Intersection で highlight.js してるので、されていないものが居る
        article.$$('pre>div').forEach(function (node) {
            highlight(node);
        });

        // details を開かないと印刷されない
        article.$$('details').forEach(function (node) {
            node.open = true;
        });
    };
    window.on('beforeprint', readyprint);

    /// エクスポート
    document.on('click', '#button-export', async function (e) {
        readyprint();

        const root = html.cloneNode(true);
        const requests = [];
        root.dataset.exported = 'true';
        root.$$('header,footer').forEach(function (e) {
            e.parentNode.removeChild(e);
        });
        root.$$('[data-section-count]').forEach(function (e) {
            e.dataset.sectionCount = 0;
        });
        root.$$('img[src]').forEach(function (e) {
            const element = document.createElements({
                img: {
                    src: e.toDataURL('image/png'),
                }
            })[0];
            e.parentNode.replaceChild(element, e);
        });
        root.$$('link[href]').forEach(function (e) {
            requests.push((async function () {
                const response = await (await fetch(e.href)).text();
                const data = [];
                for (const url of response.matchAll(/url\((.+?)\)/g)) {
                    const fullurl = e.href + '/../' + url[1].replace(/['"]/g, '');
                    const response = await fetch(fullurl);
                    const binary = await response.blob();
                    const dataurl = await binary.toDataURL();
                    data.push(dataurl);
                }
                const element = document.createElements({
                    style: {
                        id: e.id,
                        children: response.replace(/url\((.+?)\)/g, function () {
                            return 'url("' + data.shift() + '")';
                        }),
                    }
                })[0];
                e.parentNode.replaceChild(element, e);
            })());
        });
        root.$$('script[src]').forEach(function (e) {
            requests.push((async function () {
                const response = await (await fetch(e.src)).text();
                const element = document.createElements({
                    script: {
                        children: response,
                    }
                })[0];
                e.parentNode.replaceChild(element, e);
            })());
        });
        await Promise.all(requests);

        const htmlURL = URL.createObjectURL(new Blob([root.outerHTML]));
        const a = document.createElements({
            a: {
                href: htmlURL,
                download: html.$('title').innerText + '.html',
            }
        })[0];

        body.appendChild(a);
        a.click();
        body.removeChild(a);
        URL.revokeObjectURL(htmlURL);
    });

    /// トグルイベント
    document.on('click', '[data-toggle-class]', function (e) {
        const target = e.target;
        const targets = target.dataset.toggleTarget ? $$(e.target.dataset.toggleTarget) : [target];
        targets.forEach(e => e.classList.toggle(target.dataset.toggleClass));
    });

    /// コンパネ
    const SAVENAME = 'ht-setting';
    const alldata = html.matches('[data-exported]') ? {} : JSON.parse(localStorage.getItem(SAVENAME) ?? '{}');
    const directory = location.pathname.split('/').slice(0, -1).join('/');
    controlPanel.on('change', function (e) {
        if (!e.target.validity.valid) {
            return;
        }
        if (!e.target.matches('.savedata')) {
            return;
        }
        controlPanel.sync();
        controlPanel.save();
    });
    controlPanel.sync = function () {
        this.$$('.savedata').forEach(function (input) {
            html.dataset[input.id] = input.getValue();
            if (input.id === 'highlightCss') {
                const highlight_style = $('#highlight_style');
                highlight_style.href = highlight_style.dataset.cdnUrl + input.getValue() + '.min.css';
            }
            if (input.id === 'tocWidth') {
                document.documentElement.style.setProperty('--side-width', input.getValue() + 'px');
            }
            if (input.id === 'fontFamily') {
                document.documentElement.style.setProperty('--font-family', input.getValue());
            }
        });
    };
    controlPanel.save = function () {
        const savedata = {};
        this.$$('.savedata').forEach(function (input) {
            savedata[input.id] = input.getValue();
        });
        alldata[directory] = savedata;
        localStorage.setItem(SAVENAME, JSON.stringify(alldata));
    };
    controlPanel.load = function () {
        const dir = Object.keys(alldata).sort((a, b) => b.length - a.length).find(dir => directory.indexOf(dir) === 0);
        const savedata = alldata[dir] ?? Object.assign({}, html.dataset);
        this.$$('.savedata').forEach(function (input) {
            input.setValue(savedata[input.id] ?? input.dataset.defaultValue);
        });
        controlPanel.sync();
    };
    controlPanel.load();

    /// DOM ビルド
    if (!html.matches('[data-exported]')) {
        /// セクション由来のアウトラインの構築
        const levels = (new Array(6)).fill(0);
        const idmap = {};
        article.$$('.section').forEach(function (section) {
            const sectionId = `toc-${section.id}`;
            const sectionTitle = section.$('.section-header').textContent;
            const sectionLevel = +section.dataset.sectionLevel;

            levels[sectionLevel - 1]++;
            levels.fill(0, sectionLevel);

            const leading = levels.findIndex(v => v !== 0);
            const trailing = levels.findLastIndex(v => v !== 0);
            const currentLevels = levels.slice(leading, trailing + 1);
            const blockId = currentLevels.join('.');
            const parentId = currentLevels.slice(0, -1).join('.');
            section.firstChild.dataset.blockId = blockId;

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
                        sectionTitle,
                        {
                            a: {
                                class: 'toggler icon',
                            }
                        }
                    ],
                },
            });
        });
    }

    /// コードブロックの highlight.js 監視
    article.on('intersect', 'pre>div', function (e) {
        if (e.isIntersecting) {
            highlight(e.target);
            e.observer.unobserve(e.target);
        }
    }, {
        rootMargin: '0px 0px 10% 0px',
    });

    /// アウトラインのハイライト監視
    article.on('intersect', '.section', function (e) {
        let toch = document.getElementById(`toc-${e.target.id}`);
        if (html.dataset.tocActive === 'false') {
            while (toch.clientHeight <= 1) {
                toch = toch.previousElementSibling;
            }
        }
        toch.dataset.sectionCount = (Math.max(+toch.dataset.sectionCount + (e.isIntersecting ? +1 : -1), 0)) + '';
    }, {
        rootMargin: '0px 0px 0px 0px',
    });

    // アウトラインの自動開閉
    const outlineTimer = new Timer(10, function () {
        const tochs = outline.$$('.toc-h');
        const actives = Array.prototype.filter.call(tochs, e => e.dataset.sectionCount > 0);
        const firstIndex = Array.prototype.indexOf.call(tochs, actives[0]);
        const lastIndex = Array.prototype.indexOf.call(tochs, actives[actives.length - 1]);
        const min = firstIndex === -1 ? 0 : firstIndex - 3;
        const max = lastIndex === -1 ? tochs.length - 1 : lastIndex + 3;

        tochs.forEach(function (toch, i) {
            if ((min <= i && i <= max)) {
                toch.classList.add('visible');
                while (toch) {
                    toch = outline.$(`[data-block-id="${toch.dataset.parentBlockId}"]`);
                    toch?.classList?.add('visible');
                }
            }
            else {
                toch.classList.remove('visible');
            }
        });
    });
    outline.on('mutate', '[data-section-count]', function (e) {
        if (e.attributeName === 'data-section-count' && html.dataset.tocActive === 'true') {
            if (e.oldValue !== e.target.dataset.sectionCount) {
                outlineTimer.start();
            }
        }
    }, {
        attributes: true,
        attributeOldValue: true,
    });

    /// アウトラインの開閉ボタン
    outline.on('mouseenter', 'a.toc-h', function (e) {
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
    }, true);
    outline.on('mouseleave', 'a.toc-h', function (e) {
        e.target.dataset.state = '';
    }, true);
    outline.on('click', 'a.toggler', function (e) {
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
    });

    /// アウトラインのクリックイベント
    let intoViewScrolling = false;
    outline.on('click', 'a.toc-h', function (e) {
        e.preventDefault();
        const section = $(e.target.getAttribute('href'));
        intoViewScrolling = true;
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
        section.on('intersect', function (e) {
            if (e.isIntersecting) {
                e.observer.unobserve(e.target);
                requestIdleCallback(function () {
                    intoViewScrolling = false;
                });
            }
        }, {
            rootMargin: '0px 0px -99.99% 0px',
        });
    });

    // アウトラインのスクロールの自動追従
    const followMenuTimer = new Timer(32, function () {
        if ($('.wy-nav-side').clientWidth > 0) {
            const visibles = $$('.toc-h:not([data-section-count="0"])');
            visibles[Math.floor(visibles.length / 2)].scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }
    });
    document.on('scroll', function (e) {
        if (!intoViewScrolling && html.dataset.tocFollow === 'true') {
            followMenuTimer.stop();
            followMenuTimer.start();
        }
    });

    /// スクロールバーを自動的に隠す
    const scrollHideTimer = new Timer(3000, function () {
        scroller.classList.remove('scrolling');
    });
    scroller.on('scroll', function () {
        scrollHideTimer.stop();
        scroller.classList.add('scrolling');
        scrollHideTimer.start();
    });

    requestIdleCallback(function () {
        /// stop initial animation
        document.documentElement.style.setProperty('--initial-animation-ms', '500ms');

        /// 記事末尾の空白を確保（ジャンプしたときに「え？どこ？」となるのを回避する）
        const lastSection = $('.section:last-child');
        if (lastSection) {
            const height = sentinel.offsetTop - lastSection.offsetTop + parseInt(getComputedStyle(lastSection).marginTop);
            sentinel.style.height = `calc(100vh - ${height}px)`;
        }
    });
});
