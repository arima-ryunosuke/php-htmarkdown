<?php
namespace ryunosuke\Test\HtMarkdown;

use ryunosuke\HtMarkdown\Markdown;

/**
 * @covers \ryunosuke\HtMarkdown\Markdown
 */
class MarkdownTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_inlineText()
    {
        that(Markdown::render(<<<MD
            line1
            line2
            MD
            , [
                'break_line' => true,
            ]))->containsAll([
            '<br class="break-line" />',
        ]);
    }

    function test_inlineUrl()
    {
        that(Markdown::render(<<<MD
            http://example.com/path1/
            http://example.com/path2/
            MD
            , [
                'link_url' => true,
            ]))->containsAll([
            'class="link-url"',
            'href="http://example.com/path1/"',
            'href="http://example.com/path2/"',
        ]);
    }

    function test_inlineMarker()
    {
        that(Markdown::render(<<<MD
            ==maker1====maker2==
            ==maker3==
            MD
            , []))->containsAll([
            '<marker class="highlighted">maker1</marker>',
            '<marker class="highlighted">maker2</marker>',
            '<marker class="highlighted">maker3</marker>',
        ]);

        that(Markdown::render(<<<MD
            ==maker1=
            MD
            , []))->containsAll([
            '==maker1=',
        ]);
    }

    function test_inlineBadge()
    {
        that(Markdown::render('{}', []))->is('<p>{}</p>');

        that(Markdown::render(<<<MD
            {badge1}
            MD
            , []))->containsAll([
            '<span class="badge " data-badge-title="">badge1</span>',
        ]);

        that(Markdown::render(<<<MD
            {badge1}{badge2}
            {badge3}
            MD
            , []))->containsAll([
            '<span class="badge " data-badge-title="">badge1</span>',
            '<span class="badge " data-badge-title="">badge2</span>',
            '<span class="badge " data-badge-title="">badge3</span>',
        ]);

        that(Markdown::render(<<<MD
            {badge}
            {title|badge}
            {alert:badge}
            {alert:title|badge}
            MD
            , []))->containsAll([
            '<span class="badge " data-badge-title="">badge</span>',
            '<span class="badge info" data-badge-title="title">badge</span>',
            '<span class="badge alert" data-badge-title="">badge</span>',
            '<span class="badge alert" data-badge-title="title">badge</span>',
        ]);
        that(Markdown::render(<<<MD
            {badge1
            MD
            , []))->containsAll([
            '<p>{badge1</p>',
        ]);
    }

    function test_commonBlock()
    {
        that(Markdown::render(<<<MD
            ""
            less
            ""
            
            """type:la"bel
            invalid
            """
            
            """
            interrupt
            MD
            , []))->containsAll([
            'interrupt',
        ]);
    }


    function test_blockHere()
    {
        that(Markdown::render(<<<MD
            <<
            less
            <<
            MD
            , []))->containsAll([
            '&lt;&lt;',
        ]);

        that(Markdown::render(<<<MD
            <<<
            *not em*
            **not strong**
            
            - not list
            <<<
            MD
            , []))->containsAll([
            '*not em*',
            '**not strong**',
            '- not list',
        ]);
    }

    function test_blockNote()
    {
        that(Markdown::render(<<<MD
            """
            text
            """
            MD
            , []))->containsAll([
            'admonition',
            'admonition-title',
        ]);

        that(Markdown::render(<<<MD
            """note
            text
            """
            MD
            , []))->containsAll([
            'note',
            'admonition-title',
        ]);

        that(Markdown::render(<<<MD
            """note:label
            text
            """
            MD
            , []))->containsAll([
            'note',
            '>label</p>',
        ]);
    }

    function test_blockSide()
    {
        that(Markdown::render(<<<MD
            ///
            text
            ///
            MD
            , []))->containsAll([
            'sidebar',
            'sidebar-title',
        ]);

        that(Markdown::render(<<<MD
            ///note
            text
            ///
            MD
            , []))->containsAll([
            'note',
            'sidebar-title',
        ]);

        that(Markdown::render(<<<MD
            ///note:label
            text
            ///
            MD
            , []))->containsAll([
            'note',
            'sidebar-title',
            '>label</p>',
        ]);
    }

    function test_blockLineComment()
    {
        that(Markdown::render(<<<MD
            plain1
            //this is comment
            plain2
            MD
            , []))->containsAll([
            '<p>plain1</p>',
            '<!-- this is comment -->',
            '<p>plain2</p>',
        ]);

        that(Markdown::render(<<<MD
            plain1 //this is comment plain2
            MD
            , []))->containsAll([
            '<p>plain1 //this is comment plain2</p>',
        ]);
    }

    function test_blockDetail()
    {
        that(Markdown::render(<<<MD
            ...summary
            details1
            details2
            ...
            MD
            , []))->containsAll([
            '<summary class="">summary</summary>',
            '<p>details1',
            'details2</p>',
        ]);

        that(Markdown::render(<<<MD
            ...
            - A
            - B
            ```
            // code
            ```
            ...
            MD
            , []))->containsAll([
            '<li>A</li>',
            '<pre data-label=""><div class="code">// code',
        ])->notContainsAll([
            '<summary',
        ]);
    }

    function test_blockDefinitationList()
    {
        that(Markdown::render(<<<MD
            - A
            - B
              - C
            MD
            , []))->containsAll([
            '<ul class=" simple">',
            '<li>A</li>',
            '<li>B<ul class=" simple">',
            '<li>C</li>',
        ])->notContainsAll([
            '<dl',
            '<dt',
            '<dd',
        ]);

        that(Markdown::render(<<<MD
            - A: a
            - B: b
              - C: c
            MD
            , []))->containsAll([
            '<dl class="docutils field-list">',
            '<div class="dtdd-container">',
            '<dt>A</dt>',
            '<dd>a</dd>',
            '<dt>B</dt>',
            '<dd>b<dl class="docutils field-list">',
            '<dt>C</dt>',
            '<dd>c</dd>',
        ]);

        that(Markdown::render(<<<MD
            - `A`: `a`
            - **B**: **b**
              - ~~C~~: ~~c~~
            MD
            , []))->containsAll([
            '<dl class="docutils field-list">',
            '<div class="dtdd-container">',
            '<dt><code>A</code>',
            '<dd><code>a</code>',
            '<dt><strong>B</strong>',
            '<dd><strong>b</strong>',
            '<dt><del>C</del></dt>',
            '<dd><del>c</del>',
        ]);

        that(Markdown::render(<<<MD
            - A:
              a1
              a2
            - B:
              b1
              b2
              - C:
                c1
                c2
            MD
            , []))->containsAll([
            '<dl class="docutils">',
            '<div class="dtdd-container">',
            '<dt>A</dt>',
            '<p>a1',
            'a2</p>',
            '<dt>B</dt>',
            '<p>b1',
            'b2</p>',
            '<dt>C</dt>',
            '<p>c1',
            'c2</p>',
        ]);
    }

    function test_blockFenceLabel()
    {
        that(Markdown::render(<<<MD
            ```lang
            text
            ```
            MD
            , []))->containsAll([
            'data-label=""',
            'language-lang lang',
        ]);

        that(Markdown::render(<<<MD
            ```lang:label
            text
            ```
            MD
            , []))->containsAll([
            'data-label="label"',
            'language-lang:label lang',
        ]);
    }

    function test_blockHeader()
    {
        that(Markdown::render(<<<MD
            ###### H6
            
            ####### Caption
            
            ######## H8
            MD
            , []))->containsAll([
            '<h6>H6</h6>',
            '<em class="caption">Caption</em>',
            '######## H8',
        ]);
    }

    function test_blockSetextHeader()
    {
        that(Markdown::render(<<<MD
            main
            =====
            
            sub
            -----
            MD
            , []))->containsAll([
            'main-header',
            'sub-header',
        ]);
    }

    function test_blockTable()
    {
        that(Markdown::render(<<<MD
            | headA  | headB |
            |:-------|:-------|
            | cellA1 | cellB1 |
            | cellA2 | cellB2 |
            | cellA3 | cellB3 |
            MD
            , []))->containsAll([
            '<table class=" docutils align-default">',
        ]);
    }

    function test_blockList()
    {
        that(Markdown::render(<<<MD
            - listA
            - listB
                1. number1
                2. number2
                    + root1
                        + node1
                        + node2
            MD
            , []))->containsAll([
            '<ul class=" simple">',
            '<ol class="">',
            '<ul class=" simple tree">',
        ]);
    }
}
