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
            ]))->htmlMatchesArray([
            "p" => [
                "line1",
                "br" => [
                    "class" => ["break-line"],
                ],
                "line2",
            ],
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
            ]))->htmlMatchesArray([
            "p" => [
                "a[1]" => [
                    "href"  => "http://example.com/path1/",
                    "class" => ["link-url"],
                ],
                "a[2]" => [
                    "href"  => "http://example.com/path2/",
                    "class" => ["link-url"],
                ],
            ],
        ]);
    }

    function test_inlineMarker()
    {
        that(Markdown::render(<<<MD
            ==maker1====maker2==
            ==maker3==
            MD
            , []))->htmlMatchesArray([
            'p' => [
                "marker[1]" => [
                    "class" => ["highlighted"],
                    "maker1",
                ],
                "marker[2]" => [
                    "class" => ["highlighted"],
                    "maker2",
                ],
                "marker[3]" => [
                    "class" => ["highlighted"],
                    "maker3",
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            ==maker1=
            MD
            , []))->htmlMatchesArray([
            'p' => [
                '==maker1=',
            ],
        ]);
    }

    function test_inlineBadge()
    {
        that(Markdown::render('{}', []))->is('<p>{}</p>');

        that(Markdown::render(<<<MD
            {badge1}
            MD
            , []))->htmlMatchesArray([
            "p" => [
                "span" => [
                    "class"            => ["badge"],
                    "data-badge-title" => "",
                    "badge1",
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            {badge1}{badge2}  
            {badge3}
            MD
            , []))->htmlMatchesArray([
            "p" => [
                "span[1]" => [
                    "class"            => ["badge"],
                    "data-badge-title" => "",
                    "badge1",
                ],
                "span[2]" => [
                    "class"            => ["badge"],
                    "data-badge-title" => "",
                    "badge2",
                ],
                "span[3]" => [
                    "class"            => ["badge"],
                    "data-badge-title" => "",
                    "badge3",
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            {badge}  
            {title|badge}  
            {alert:badge}  
            {alert:title|badge}
            MD
            , []))->htmlMatchesArray([
            "p" => [
                "span[1]" => [
                    "class"            => ["badge"],
                    "data-badge-title" => "",
                    "badge",
                ],
                "span[2]" => [
                    "class"            => ["badge", "info"],
                    "data-badge-title" => "title",
                    "badge",
                ],
                "span[3]" => [
                    "class"            => ["badge", "alert"],
                    "data-badge-title" => "",
                    "badge",
                ],
                "span[4]" => [
                    "class"            => ["badge", "alert"],
                    "data-badge-title" => "title",
                    "badge",
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            {badge1
            MD
            , []))->htmlMatchesArray([
            "p" => ["{badge1"],
        ]);

        that(Markdown::render(<<<MD
            {undefined:title|badge}
            MD
            , []))->notContains('<span class="badge');
    }

    function test_inlineLink()
    {
        that(Markdown::render(<<<MD
            [nomatch
            
            [text1](url1)
            [text2](url2 "title")
            [text3](url3 target=_blank)
            [text4](url4 id=hoge class="c1 c2")
            [text5][link-x]
            
            [link-x]: url5
            MD
            , []))->htmlMatchesArray([
            "p[1]" => [
                "[nomatch",
            ],
            "p[2]" => [
                "a[1]" => [
                    "href" => "url1",
                    "text1",
                ],
                "a[2]" => [
                    "href"  => "url2",
                    "title" => "title",
                    "text2",
                ],
                "a[3]" => [
                    "href"   => "url3",
                    "target" => "_blank",
                    "text3",
                ],
                "a[4]" => [
                    "href"  => "url4",
                    "id"    => "hoge",
                    "class" => "c1 c2",
                    "text4",
                ],
                "a[5]" => [
                    "href" => "url5",
                    "text5",
                ],
            ],
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
            , []))->htmlMatchesArray([
            "body" => [
                "p[1]" => ["\"\"", "less", "\"\""],
                "p[2]" => ["\"\"\"type:la\"bel", "invalid"],
                "p[3]" => ["interrupt"],
            ],
        ]);
    }

    function test_paragraphContinue()
    {
        that(Markdown::render(<<<MD
            [attr=value]{color:red}
            plain text
            MD
            , []))->htmlMatchesArray([
            "body" => [
                "p[1]" => [
                    "x-attrs" => [
                        "attr"  => "value",
                        "style" => ["color" => "red"],
                    ],
                ],
                "p[2]" => ["plain text"],
            ],
        ]);
    }

    function test_blockAlias()
    {
        that(Markdown::render(<<<'MD'
            <<block1
            1
            $block3
            block1
            
            <<block2
            2
            $block1
            block2
            
            <<block3
            3
            $block2
            block3
            
            - $block1
            - $block2
            - $block3
            MD
            , []))->htmlMatchesArray([
            "ul" => [
                "class" => ["simple"],
                "li[1]" => [
                    "div[1]" => [
                        "class" => ["block1"],
                        "p[1]"  => ["1", "\$block3"],
                    ],
                ],
                "li[2]" => [
                    "div[1]" => [
                        "class"  => ["block2"],
                        "p[1]"   => ["2"],
                        "div[1]" => [
                            "class" => "block1",
                            "p[1]"  => ["1", "\$block3"],
                        ],
                    ],
                ],
                "li[3]" => [
                    "div[1]" => [
                        "class"  => ["block3"],
                        "p[1]"   => ["3"],
                        "div[1]" => [
                            "class"  => ["block2"],
                            "p[1]"   => ["2"],
                            "div[1]" => [
                                "class" => ["block1"],
                                "p[1]"  => ["1", "\$block3"],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    function test_blockHere()
    {
        that(Markdown::render(<<<MD
            <<
            less
            <<
            MD
            , []))->htmlMatchesArray([
            "p" => ["<<", "less", "<<"],
        ]);

        that(Markdown::render(<<<MD
            <<<
            *not em*
            **not strong**
            
            - not list
            <<<
            MD
            , []))->htmlMatchesArray([
            "pre" => [
                "p" => [
                    <<<PRE
                    *not em*
                    **not strong**
                    
                    - not list
                    PRE
                    ,
                ],
            ],
        ]);
    }

    function test_blockCascade()
    {
        that(Markdown::render(<<<MD
            ^^^(1,2)
            (n)(n)
              (1)(n)
            (-1, -2, 3, 4)
            (-1, -2:n)
            (-1, -2: text)
            ^^^
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class"   => ["cascade"],
                "style"   => ["padding-left" => "1px", "padding-top" => "2px"],
                "span[1]" => [
                    "style" => ["left" => "0px", "top" => "0px"],
                    "class" => ["cascade-item", "number-item", "group-1", "index-1"],
                    "1",
                ],
                "span[2]" => [
                    "style" => ["left" => "0px", "top" => "0px"],
                    "class" => ["cascade-item", "number-item", "group-1", "index-2"],
                    "2",
                ],
                "span[3]" => [
                    "style" => ["left" => "0px", "top" => "0px"],
                    "class" => ["cascade-item", "number-item", "group-2", "index-1"],
                    "1",
                ],
                "span[4]" => [
                    "style" => ["left" => "0px", "top" => "0px"],
                    "class" => ["cascade-item", "number-item", "group-2", "index-2"],
                    "2",
                ],
                "span[5]" => [
                    "style" => ["left" => "-1px", "top" => "-2px", "width" => "3px", "height" => "4px"],
                    "class" => "cascade-item shape-item group-2",
                ],
                "span[6]" => [
                    "style" => ["left" => "-1px", "top" => "-2px"],
                    "class" => ["cascade-item", "number-item", "group-2", "index-3"],
                    "3",
                ],
                "span[7]" => [
                    "style" => ["left" => "-1px", "top" => "-2px"],
                    "class" => ["cascade-item", "text-item", "group-2", "index-4"],
                    "text",
                ],
            ],
        ]);
    }

    function test_blockNote()
    {
        that(Markdown::render(<<<MD
            """
            text
            """
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["admonition"],
                "p[1]"  => [
                    "class" => ["admonition-title"],
                ],
                "p[2]"  => ["text"],
            ],
        ]);

        that(Markdown::render(<<<MD
            """note
            text
            """
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["note", "admonition"],
                "p[1]"  => [
                    "class" => ["admonition-title"],
                ],
                "p[2]"  => ["text"],
            ],
        ]);

        that(Markdown::render(<<<MD
            """note:label
            text
            """
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["note", "admonition"],
                "p[1]"  => [
                    "class" => ["admonition-title"],
                    "label",
                ],
                "p[2]"  => ["text"],
            ],
        ]);
    }

    function test_blockDiv()
    {
        that(Markdown::render(<<<MD
            --
            block
            --
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["block"],
                "p[1]"  => ["block"],
            ],
        ]);

        that(Markdown::render(<<<MD
            --section
            block
            --
            MD
            , []))->htmlMatchesArray([
            "section" => [
                "class" => ["block"],
                "p[1]"  => ["block"],
            ],
        ]);

        that(Markdown::render(<<<MD
            --section#id.class[attr=value]{color:#eee;margin:1.1em;}
            block
            --
            MD
            , []))->htmlMatchesArray([
            "section" => [
                "id"    => "id",
                "class" => ["class"],
                "attr"  => "value",
                "style" => "color:#eee;margin:1.1em;",
                "p[1]"  => ["block"],
            ],
        ]);

        that(Markdown::render(<<<MD
            --section#id.class[attr1=value attr2="x y z" attr3=\\"]{color:#eee;margin:1.1em;}
            block
            --
            MD
            , []))->htmlMatchesArray([
            "section" => [
                "id"    => "id",
                "class" => ["class"],
                "attr1" => "value",
                "attr2" => "x y z",
                "attr3" => "\"",
                "style" => ["color" => "#eee", "margin" => "1.1em"],
                "p[1]"  => ["block"],
            ],
        ]);
    }

    function test_blockSide()
    {
        that(Markdown::render(<<<MD
            ///
            text
            ///
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["sidebar"],
                "p[1]"  => [
                    "class" => ["sidebar-title"],
                ],
                "p[2]"  => ["text"],
            ],
        ]);

        that(Markdown::render(<<<MD
            ///note
            text
            ///
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["note", "sidebar"],
                "p[1]"  => [
                    "class" => ["sidebar-title"],
                ],
                "p[2]"  => ["text"],
            ],
        ]);

        that(Markdown::render(<<<MD
            ///note:label
            text
            ///
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "class" => ["note", "sidebar"],
                "p[1]"  => [
                    "class" => ["sidebar-title"],
                    "label",
                ],
                "p[2]"  => ["text"],
            ],
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
            , []))->htmlMatchesArray([
            "details" => [
                "summary" => [
                    "class" => "",
                    "summary",
                ],
                "p"       => ["details1", "details2"],
            ],
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
            , []))->htmlMatchesArray([
            "details" => [
                "ul" => [
                    "class" => ["simple"],
                    "li[1]" => ["A"],
                    "li[2]" => [
                        "B",
                        "pre" => [
                            "data-label" => "",
                            "div"        => [
                                "class" => ["code"],
                                "// code",
                            ],
                        ],
                    ],
                ],
            ],
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
            , []))->htmlMatchesArray([
            "body/ul" => [
                "class" => ["simple"],
                "li[1]" => ["A"],
                "li[2]" => [
                    "B",
                    "ul" => [
                        "class" => ["simple"],
                        "li"    => ["C"],
                    ],
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            - A: a
            - B: b
              - C: c
            MD
            , []))->htmlMatchesArray([
            "body/dl" => [
                "class"  => ["field-list"],
                "div[1]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => ["A"],
                    "dd"    => ["a"],
                ],
                "div[2]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => ["B"],
                    "dd"    => [
                        "b",
                        "dl" => [
                            "class" => ["field-list"],
                            "div"   => [
                                "class" => ["dtdd-container"],
                                "dt"    => ["C"],
                                "dd"    => ["c"],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            - `A`: `a`
            - **B**: **b**
              - ~~C~~: ~~c~~
            MD
            , []))->htmlMatchesArray([
            "body/dl" => [
                "class"  => ["field-list"],
                "div[1]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => [
                        "code" => ["A"],
                    ],
                    "dd"    => [
                        "code" => ["a"],
                    ],
                ],
                "div[2]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => [
                        "strong" => ["B"],
                    ],
                    "dd"    => [
                        "strong" => ["b"],
                        "dl"     => [
                            "class" => ["field-list"],
                            "div"   => [
                                "class" => ["dtdd-container"],
                                "dt"    => [
                                    "del" => ["C"],
                                ],
                                "dd"    => [
                                    "del" => ["c"],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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
            , []))->htmlMatchesArray([
            "body/dl" => [
                "class"  => ["docutils"],
                "div[1]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => ["A"],
                    "dd"    => [
                        "p" => ["a1", "a2"],
                    ],
                ],
                "div[2]" => [
                    "class" => ["dtdd-container"],
                    "dt"    => ["B"],
                    "dd"    => [
                        "p"  => ["b1", "b2"],
                        "dl" => [
                            "class" => ["docutils"],
                            "div"   => [
                                "class" => ["dtdd-container"],
                                "dt"    => ["C"],
                                "dd"    => [
                                    "p" => ["c1", "c2"],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    function test_blockFenceLabel()
    {
        that(Markdown::render(<<<MD
            ```lang
            text
            ```
            MD
            , []))->htmlMatchesArray([
            "pre" => [
                "data-label" => "",
                "div"        => [
                    "class" => ["code", "language-lang", "lang"],
                    "text",
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            ```lang:label
            text
            ```
            MD
            , []))->htmlMatchesArray([
            "pre" => [
                "data-label" => "label",
                "div"        => [
                    "class" => ["code", "language-lang:label", "lang:label"],
                    "text",
                ],
            ],
        ]);
    }

    function test_blockHeader()
    {
        that(Markdown::render(<<<MD
            ###### H6
            
            ####### Caption
            
            ######## H8
            MD
            , []))->htmlMatchesArray([
            "h6" => ["H6"],
            "em" => [
                "class" => ["caption"],
                "Caption",
            ],
            "p"  => ["######## H8"],
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
            , []))->htmlMatchesArray([
            "h1" => [
                "class" => ["main-header"],
                "main",
            ],
            "h2" => [
                "class" => ["sub-header"],
                "sub",
            ],
        ]);
    }

    function test_blockTable()
    {
        that(Markdown::render(<<<MD
            | headA      | headB            |
            |:---------- |:---------------- |
            | cellA1     | cellB1           |
            | cellA2     | cellB2           |
            | cellA3     | cellB3           |
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "table" => [
                    "class"    => ["has-colgroup"],
                    "colgroup" => [
                        "col[1]" => [
                            "style" => ["width" => "35%"],
                        ],
                        "col[2]" => [
                            "style" => ["width" => "65%"],
                        ],
                    ],
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
             headA     | headB           
            :----------|:----------------
             cellA1    | cellB1          
             cellA2    | cellB2          
             cellA3    | cellB3          
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "table" => [
                    "class" => ["no-border"],
                ],
            ],
        ]);

        that(Markdown::render(<<<MD
            |           |                 |
            |:----------|:----------------|
            | cellA1    | cellB1          |
            | cellA2    | cellB2          |
            | cellA3    | cellB3          |
            MD
            , []))->htmlMatchesArray([
            "div" => [
                "table" => [
                    "class" => ["no-header"],
                ],
            ],
        ])->notContains('<thead>');

        that(Markdown::render(<<<MD
            caption-top:
            | headA     | headB           |
            |:----------|:----------------|
            | cellA1    | cellB1          |
            | cellA2    | cellB2          |
            | cellA3    | cellB3          |
            :caption-bottom
            
            :caption-center:
            | headA     | headB           |
            |:----------|:----------------|
            | cellA1    | cellB1          |
            | cellA2    | cellB2          |
            | cellA3    | cellB3          |
            MD
            , []))->htmlMatchesArray([
            "div[1]" => [
                "table" => [
                    "class"      => ["has-caption", "has-top-caption", "has-bottom-caption"],
                    "caption[1]" => [
                        "style" => ["text-align" => "right", "caption-side" => "top"],
                        "caption-top",
                    ],
                    "caption[2]" => [
                        "style" => ["text-align" => "left", "caption-side" => "bottom"],
                        "caption-bottom",
                    ],
                ],
            ],
            "div[2]" => [
                "table" => [
                    "class"      => ["has-caption", "has-top-caption"],
                    "caption[1]" => [
                        "style" => ["text-align" => "center", "caption-side" => "top"],
                        "caption-center",
                    ],
                ],
            ],
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
            , []))->htmlMatchesArray([
            "body/ul" => [
                "class" => ["simple"],
                "li[1]" => [
                    "class" => [],
                    "listA",
                ],
                "li[2]" => [
                    "class" => [],
                    "listB",
                    "ol"    => [
                        "class" => [],
                        "li[1]" => [
                            "class" => [],
                            "number1",
                        ],
                        "li[2]" => [
                            "class" => [],
                            "number2",
                            "ul"    => [
                                "class" => ["simple", "tree"],
                                "li[1]" => [
                                    "root1",
                                    "ul" => [
                                        "class" => ["simple", "tree"],
                                        "li[1]" => [
                                            "class" => ["leaf"],
                                            "node1",
                                        ],
                                        "li[2]" => [
                                            "class" => ["leaf"],
                                            "node2",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
