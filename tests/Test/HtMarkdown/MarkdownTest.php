<?php
namespace ryunosuke\Test\HtMarkdown;

use ryunosuke\HtMarkdown\Markdown;

/**
 * @covers \ryunosuke\HtMarkdown\Markdown
 */
class MarkdownTest extends \ryunosuke\Test\AbstractTestCase
{
    function test_common()
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
            '<pre><div class=" code">// code',
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
            '<ul>',
            '<li>A</li>',
            '<li>B<ul>',
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

    function test_fenceLabel()
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
}
