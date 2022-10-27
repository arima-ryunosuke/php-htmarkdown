<?php

namespace ryunosuke\HtMarkdown;

use Parsedown;

class Markdown extends Parsedown
{
    private $aliases = [];

    public function __construct($options)
    {
        $this->setMarkupEscaped(false);
        $this->setUrlsLinked(true);
        $this->setStrictMode(!!($options['strict_mode'] ?? false));
        $this->setSafeMode(!!($options['safe_mode'] ?? false));

        $newInlineTypes = [
            '=' => ['Marker'],
            '{' => ['Badge', 'Attribute'],
            '$' => ['Alias'],
            '[' => ['Attribute'],
        ];
        foreach ($newInlineTypes as $char => $inline) {
            $this->InlineTypes[$char] = array_merge($this->InlineTypes[$char] ?? [], $inline);
        }
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));

        $newBlockTypes = [
            '<' => ['Alias', 'Here'],
            '"' => ['Note'],
            '/' => ['Side', 'LineComment'],
            '.' => ['Detail'],
            '-' => ['Div'],
            '^' => ['Cascade'],
        ];
        foreach ($newBlockTypes as $char => $block) {
            $this->BlockTypes[$char] = array_merge($this->BlockTypes[$char] ?? [], $block);
        }
    }

    public static function render($contents, $options)
    {
        return (new static($options))->text($contents);
    }

    protected function inlineText($text)
    {
        $Inline = [
            'extent'  => strlen($text),
            'element' => [],
        ];

        while (preg_match('/(?<explicit>([ ]*+\\\\|[ ]{2,}+)\n)|(?<implicit>[ ]*+\n)/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matches[0][0]));

            $Inline['element']['elements'][] = ['text' => $before];

            if (($matches['explicit'][1] ?? -1) >= 0) {
                $Inline['element']['elements'][] = ['name' => 'br'];
            }
            if (($matches['implicit'][1] ?? -1) >= 0) {
                $Inline['element']['elements'][] = ['name' => 'span', 'attributes' => ['class' => 'implicit-br'], 'text' => "\n"];
            }

            $text = $after;
        }

        $Inline['element']['elements'][] = ['text' => $text];

        return $Inline;
    }

    protected function inlineUrl($Excerpt)
    {
        $Inline = parent::inlineUrl($Excerpt);
        if ($Inline !== null) {
            $Inline['element']['attributes']['class'] = 'link-url';
        }
        return $Inline;
    }

    protected function inlineAttribute($Excerpt)
    {
        if (preg_match('/^([\[{][ ]*[_\-a-z0-9]+[=:][^{}\[\]]+?[]}])+/is', $Excerpt['text'], $matches)) {
            $attrs = $this->selector($matches[0]);
            if ($attrs) {
                return [
                    'extent'  => strlen($matches[0]),
                    'element' => [
                        'name'       => 'x-attrs',
                        'attributes' => $attrs,
                    ],
                ];
            }
        }
        return null;
    }

    protected function inlineMarker($Excerpt)
    {
        $p = strpos($Excerpt['text'], '==', 1);
        if ($p !== false) {
            return [
                'extent'  => $p + 2,
                'element' => [
                    'name'       => 'marker',
                    'text'       => substr($Excerpt['text'], 2, $p - 2),
                    'attributes' => [
                        'class' => 'highlighted',
                    ],
                ],
            ];
        }
    }

    protected function inlineBadge($Excerpt)
    {
        $p = strpos($Excerpt['text'], '}');
        if ($p !== false && preg_match('#([^:|]*):?([^:|]*)\|?([^:|]*)#', substr($Excerpt['text'], 1, $p - 1), $match)) {
            array_shift($match);
            $acount = count(array_filter($match, 'strlen'));
            if ($acount === 1) {
                [$type, $title, $text] = ['', '', $match[0]];
            }
            elseif ($acount === 2 && strlen($match[1])) {
                [$type, $title, $text] = [$match[0], '', $match[1]];
            }
            elseif ($acount === 2 && !strlen($match[1])) {
                [$type, $title, $text] = ['info', $match[0], $match[2]];
            }
            elseif ($acount === 3) {
                [$type, $title, $text] = $match;
            }
            else {
                return;
            }

            if (!in_array($type, ['', 'alert', 'info', 'caution', 'danger', 'error', 'hint', 'important', 'note', 'success', 'tip', 'warning'], true)) {
                return;
            }
            return [
                'extent'  => $p + 1,
                'element' => [
                    'name'       => 'span',
                    'text'       => $text,
                    'attributes' => [
                        'class'            => "badge $type",
                        'data-badge-title' => $title,
                    ],
                ],
            ];
        }
    }

    protected function inlineAlias($Excerpt)
    {
        if (preg_match('#\\$([_a-z][_a-z0-9]*)#ui', $Excerpt['text'], $match)) {
            $alias = $match[1];
            if (isset($this->aliases[$alias])) {
                return [
                    'extent'  => strlen($alias) + 1,
                    'element' => [
                        'rawHtml' => $this->aliases[$alias],
                    ],
                ];
            }
        }
    }

    protected function inlineLink($Excerpt)
    {
        $Link = parent::inlineLink($Excerpt);
        if ($Link !== null) {
            return $Link;
        }

        if (preg_match('/^\[((?:[^][]++|(?R))*+)\][(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+([^)]*+))?\s*+[)]/', $Excerpt['text'], $matches, PREG_OFFSET_CAPTURE) && isset($matches[3])) {
            $Excerpt['text'] = substr_replace($Excerpt['text'], '', $matches[3][1], strlen($matches[3][0]));
            $Link = parent::inlineLink($Excerpt);
            if ($Link !== null) {
                $title = $matches[3][0];
                $Link['extent'] += strlen($title);
                $Link['element']['attributes'] += $this->selector("[{$title}]");
            }
        }
        return $Link;
    }

    protected function _commonBlock($Line, $Element, $Opener = null)
    {
        $marker = $Line['text'][0];

        $Opener ??= 3;
        if (is_int($Opener)) {
            $openerLength = strspn($Line['text'], $marker);
            if ($openerLength < $Opener) {
                return;
            }
            $Opener = substr($Line['text'], 0, $openerLength);
        }
        else {
            // @todo
            // $openerLength = strlen($Opener);
        }

        $infostring = trim(substr($Line['text'], $openerLength), "\t ");
        if (strpos($infostring, $marker) !== false) {
            return;
        }

        $Block = [
            'opener'       => $Opener,
            'char'         => $marker,
            'openerLength' => $openerLength,
            'infoString'   => $infostring,
            'element'      => array_replace_recursive([
                'text'     => '',
                'elements' => [
                    [],
                    [
                        'handler' => [
                            'function'    => 'textElements',
                            'argument'    => '',
                            'destination' => 'elements',
                        ],
                    ],
                ],
            ], $Element),
        ];

        return $Block;
    }

    protected function _commonBlockContinue($Line, $Block, &$Ref, $Closer = null)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Ref .= str_repeat("\n", $Block['interrupted']);
            unset($Block['interrupted']);
        }

        if (false
            || ($Closer === null && (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength'] and chop(substr($Line['text'], $len), ' ') === ''))
            || ($Closer === $Line['text'])
        ) {
            $Ref = substr($Ref, 1);
            $Block['complete'] = true;

            return $Block;
        }

        $Ref .= "\n" . $Line['body'];

        return $Block;
    }

    protected function _commonBlockComplete($Block)
    {
        return $Block;
    }

    protected function paragraphContinue($Line, array $Block)
    {
        $attribute = $this->inlineAttribute(['text' => $Block['element']['handler']['argument']]);
        if ($attribute && $attribute['extent'] === strlen($Block['element']['handler']['argument'])) {
            return;
        }
        return parent::paragraphContinue($Line, $Block);
    }

    protected function blockAlias($Line)
    {
        $Block = $this->_commonBlock($Line, [], 2);
        if ($Block !== null) {
            if (!preg_match('#^([_a-z][_a-z0-9]*)$#ui', $Block['infoString'])) {
                $Block = null;
            }
        }
        return $Block;
    }

    protected function blockAliasContinue($Line, $Block)
    {
        $Ref = &$Block['element']['text'];
        return $this->_commonBlockContinue($Line, $Block, $Ref, $Block['infoString']);
    }

    protected function blockAliasComplete($Block)
    {
        $this->aliases[$Block['infoString']] = "<div class='{$Block['infoString']}'>{$this->text($Block['element']['text'])}</div>";
        return null;
    }

    protected function blockHere($Line)
    {
        $Block = $this->_commonBlock($Line, []);
        if ($Block !== null) {
            $Block['element'] = [
                'name'    => 'pre',
                'element' => [
                    'name' => 'p',
                    'text' => '',
                ],
            ];
        }
        return $Block;
    }

    protected function blockHereContinue($Line, $Block)
    {
        $Ref = &$Block['element']['element']['text'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockHereComplete($Block)
    {
        return $this->_commonBlockComplete($Block);
    }

    protected function blockCascade($Line)
    {
        $Block = $this->_commonBlock($Line, []);
        if ($Block !== null) {
            [$x, $y] = [0, 0];
            if (preg_match('#\((-?\d+),\s*(-?\d+)\)#', $Block['infoString'], $matches)) {
                [, $x, $y] = $matches;
            }
            $Block['element'] = [
                'name'       => 'div',
                'rawHtml'    => "",
                'attributes' => [
                    'class' => 'cascade',
                    'style' => "padding-left:{$x}px; padding-top:{$y}px;",
                ],

            ];
        }
        return $Block;
    }

    protected function blockCascadeContinue($Line, $Block)
    {
        $Ref = &$Block['element']['rawHtml'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockCascadeComplete($Block)
    {
        $DIGIT = '-?\\d+';
        $COMMA = '\\s*,\\s*';
        $group = 1;
        $index = 0;
        $Block['element']['rawHtml'] = preg_replace_callback("#\(((?<x>$DIGIT)?$COMMA(?<y>$DIGIT)?)?(:?\s*(?<content>.+?))?\)#", function ($matches) use ($DIGIT, $COMMA, &$group, &$index) {
            $x = $matches['x'] ?: 0;
            $y = $matches['y'] ?: 0;

            if (preg_match("#$COMMA(?<w>$DIGIT)$COMMA(?<h>$DIGIT)#", $matches['content'], $m)) {
                [$w, $h] = [$m['w'] ?: 0, $m['h'] ?: 0];
                return "<span style='left:{$x}px; top:{$y}px; width:{$w}px; height:{$h}px;' class='cascade-item shape-item group-$group'></span>";
            }

            $content = htmlspecialchars($matches['content'], ENT_QUOTES);
            $class = 'cascade-item';

            if ($content === 'n') {
                $content = ++$index;
                $class .= " number-item group-$group";
            }
            elseif (ctype_digit("$content")) {
                $group++;
                $index = (int) $content;
                $class .= " number-item group-$group";
            }
            else {
                $index++;
                $class .= " text-item group-$group";
            }
            return "<span style='left:{$x}px; top:{$y}px;' class='$class index-$index'>$content</span>";
        }, $Block['element']['rawHtml']);
        return $this->_commonBlockComplete($Block);
    }

    protected function blockNote($Line)
    {
        $Block = $this->_commonBlock($Line, [
            'name'     => 'div',
            'elements' => [
                [
                    'name'       => 'p',
                    'attributes' => [
                        'class' => 'admonition-title',
                    ],
                ],
            ],
        ]);
        if ($Block !== null) {
            [$type, $title] = explode(':', $Block['infoString']) + [1 => ''];
            $Block['element']['attributes']['class'] = "$type admonition";
            $Block['element']['elements'][0]['text'] = $title;
        }
        return $Block;
    }

    protected function blockNoteContinue($Line, $Block)
    {
        $Ref = &$Block['element']['elements'][1]['handler']['argument'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockNoteComplete($Block)
    {
        return $this->_commonBlockComplete($Block);
    }

    protected function blockDiv($Line)
    {
        $Block = $this->_commonBlock($Line, [
            'name' => 'div',
        ], 2);
        if ($Block !== null) {
            $attrs = $this->selector($Block['infoString']);
            $attrs['class'] ??= 'block';
            $tag = $attrs[''] ?? 'div';
            unset($attrs['']);

            $Block['element']['name'] = $tag;
            $Block['element']['attributes'] = $attrs;
        }
        return $Block;
    }

    protected function blockDivContinue($Line, $Block)
    {
        $Ref = &$Block['element']['elements'][1]['handler']['argument'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockDivComplete($Block)
    {
        return $this->_commonBlockComplete($Block);
    }

    protected function blockSide($Line)
    {
        $Block = $this->_commonBlock($Line, [
            'name'     => 'div',
            'elements' => [
                [
                    'name'       => 'p',
                    'attributes' => [
                        'class' => 'sidebar-title',
                    ],
                ],
            ],
        ]);
        if ($Block !== null) {
            [$type, $title] = explode(':', $Block['infoString']) + [1 => ''];
            $Block['element']['attributes']['class'] = "$type sidebar";
            $Block['element']['elements'][0]['text'] = $title;
        }
        return $Block;
    }

    protected function blockSideContinue($Line, $Block)
    {
        $Ref = &$Block['element']['elements'][1]['handler']['argument'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockSideComplete($Block)
    {
        return $this->_commonBlockComplete($Block);
    }

    protected function blockLineComment($Line, $Block = null)
    {
        if ($Block !== null && strpos($Line['text'], '//') === 0 && substr($Line['text'], 2, 1) !== '/') {
            return [
                'element' => [
                    'rawHtml' => '<!-- ' . ltrim($Line['body'], '/') . ' -->',
                ],
            ];
        }
    }

    protected function blockDetail($Line)
    {
        $Block = $this->_commonBlock($Line, [
            'name'     => 'details',
            'elements' => [
                [
                    'name'       => 'summary',
                    'attributes' => [
                        'class' => '',
                    ],
                ],
            ],
        ]);
        if ($Block !== null) {
            $Block['element']['elements'][0]['text'] = trim($Block['infoString']);
        }
        return $Block;
    }

    protected function blockDetailContinue($Line, $Block)
    {
        $Ref = &$Block['element']['elements'][1]['handler']['argument'];
        return $this->_commonBlockContinue($Line, $Block, $Ref);
    }

    protected function blockDetailComplete($Block)
    {
        if (strlen($Block['element']['elements'][0]['text']) === 0) {
            array_shift($Block['element']['elements']);
        }
        return $this->_commonBlockComplete($Block);
    }

    protected function blockFencedCode($Line)
    {
        $Block = $this->_commonBlock($Line, []);
        if ($Block !== null) {
            $Block['element'] = [
                'name'    => 'pre',
                'element' => [
                    'name'       => 'div',
                    'text'       => '',
                    'attributes' => [
                        'class' => 'code',
                    ],
                ],
            ];
            if (strlen($Block['infoString'])) {
                $language = substr($Block['infoString'], 0, strcspn($Block['infoString'], " \t\n\f\r"));
                $Block['element']['element']['attributes']['class'] .= " language-$language $language";
            }
            $Block['element']['attributes']['data-label'] = explode(':', $Block['infoString'], 2)[1] ?? '';
        }
        return $Block;
    }

    protected function blockHeader($Line)
    {
        $level = strspn($Line['text'], '#');

        if ($level === 7) {
            $text = trim($Line['text'], '#');
            $text = trim($text, ' ');

            return [
                'element' => [
                    'name'       => 'em',
                    'attributes' => [
                        'class' => 'caption',
                    ],
                    'handler'    => [
                        'function'    => 'lineElements',
                        'argument'    => $text,
                        'destination' => 'elements',
                    ],
                ],
            ];
        }

        return parent::blockHeader($Line);
    }

    protected function blockSetextHeader($Line, array $Block = null)
    {
        $Block = parent::blockSetextHeader($Line, $Block);
        if ($Block !== null) {
            $main_or_sub = $Block['element']['name'] === 'h1' ? 'main' : 'sub';
            $Block['element']['attributes']['class'] = ($Block['element']['attributes']['class'] ?? '') . " $main_or_sub-header";
        }
        return $Block;
    }

    protected function _tableCaption($oneline)
    {
        if (false
            || preg_match('#^(:)(.+?)(:)$#u', $oneline, $matches)
            || preg_match('#^(:)(.+?)(:?)$#u', $oneline, $matches)
            || preg_match('#^(:?)(.+?)(:)$#u', $oneline, $matches)
        ) {
            $caption['text'] = $matches[2];
            $caption['align'] = 'unset';
            if (strlen($matches[1] ?? '')) {
                $caption['align'] = 'left';
            }
            if (strlen($matches[3] ?? '')) {
                $caption['align'] = $caption['align'] === 'left' ? 'center' : 'right';
            }
            return $caption;
        }
        return null;
    }

    protected function blockTable($Line, array $Block = null)
    {
        $caption = [];
        if (isset($Block) && $Block['type'] === 'Paragraph' && !isset($Block['interrupted'])) {
            $lines = explode("\n", $Block['element']['handler']['argument']);
            if (count($lines) === 2) {
                $caption = $this->_tableCaption(array_shift($lines)) ?? [];
                if ($caption) {
                    $Block['element']['handler']['argument'] = implode("\n", $lines);
                }
            }
        }

        $Block = parent::blockTable($Line, $Block);
        if ($Block === null) {
            return;
        }

        $text = trim($Line['text']);
        $bare = trim($text, '|');

        $Block['cols'] = [];
        foreach (explode('|', $bare) as $dividerCell) {
            $Block['cols'][] = [
                'space' => substr_count($dividerCell, ' '),
                'width' => substr_count($dividerCell, '-') - 3, // minimum count in table notation
            ];
        }

        $Block['captions'] = [];
        if ($caption) {
            $Block['captions']['top'] = $caption;
        }

        $Block['element']['attributes']['class'] = ($Block['element']['attributes']['class'] ?? '');
        $Block['element']['attributes']['class'] .= $text === $bare ? ' no-border' : '';
        $Block['element']['attributes']['class'] .= ' docutils align-default';
        return $Block;
    }

    protected function blockTableContinue($Line, array $Block)
    {
        $parent = parent::blockTableContinue($Line, $Block);
        if ($parent === null) {
            if (isset($Block['interrupted'])) {
                return null;
            }
            $caption = $this->_tableCaption($Line['text']);
            if ($caption) {
                $Block['captions']['bottom'] = $caption;
                return $Block;
            }
        }

        return $parent;
    }

    protected function blockTableComplete($Block)
    {
        if ($Block !== null) {
            // shortcut vars
            $cols = $Block['cols'];
            $table = &$Block['element'];
            $thead = &$table['elements'][0];
            //$tbody = &$table['elements'][1];
            $class = &$table['attributes']['class'];

            // handle no-header
            if (!array_reduce($thead['elements'][0]['elements'], fn($carry, $th) => $carry || strlen($th['handler']['argument']), false)) {
                $class .= ' no-header';
                $thead = [];
            }

            // handle has-colgroup
            if (array_sum(array_column($cols, 'space')) > 0) {
                $total = array_sum(array_column($cols, 'width'));
                if ($total) {
                    $class .= ' has-colgroup';
                    array_unshift($table['elements'], [
                        'name'     => 'colgroup',
                        'elements' => (function () use ($cols, $total) {
                            return array_map(fn($col) => [
                                'name'       => 'col',
                                'attributes' => $col['space'] > 0 ? ['style' => "width:" . ($col['width'] / $total * 100) . '%'] : [],
                            ], $cols);
                        })(),
                    ]);
                }
            }

            // handle has-caption
            if ($Block['captions']) {
                $vertical = array_keys($Block['captions']);
                $class .= ' has-caption ' . implode(' ', array_map(fn($pos) => "has-{$pos}-caption", $vertical));
                array_unshift($table['elements'], ...array_values(array_map(fn($caption, $pos) => [
                    'name'       => 'caption',
                    'handler'    => [
                        'function'    => 'lineElements',
                        'argument'    => $caption['text'],
                        'destination' => 'elements',
                    ],
                    'attributes' => [
                        'style' => implode(";", [
                            "text-align: {$caption['align']}",
                            "caption-side: {$pos}",
                        ]),
                    ],
                ], $Block['captions'], $vertical)));
            }

            // wrap div.wy-table-responsive
            $Block = [
                'type'    => 'tableWrapper',
                'element' => [
                    'name'       => 'div',
                    'elements'   => [$Block['element']],
                    'attributes' => [
                        'class' => "wy-table-responsive",
                    ],
                ],
            ];
        }
        return $Block;
    }

    protected function blockList($Line, array $CurrentBlock = null)
    {
        $Block = parent::blockList($Line, $CurrentBlock);
        if ($Block !== null) {
            $Block['element']['attributes']['class'] = $Block['element']['attributes']['class'] ?? '';
            if ($Block['element']['name'] === 'ul') {
                $Block['element']['attributes']['class'] .= ' simple';
            }
            if ($Block['data']['markerType'] === '+') {
                $Block['element']['attributes']['class'] .= ' tree';
            }
        }
        return $Block;
    }

    protected function blockListComplete(array $Block)
    {
        $Block = parent::blockListComplete($Block);
        if ($Block !== null) {
            $elements = $Block['element']['elements'];
            $Block['element']['elements'] = [];

            foreach ($elements as $element) {
                if ($Block['data']['markerType'] === '+' && $element['name'] === 'li') {
                    $element['attributes']['class'] = ($element['attributes']['class'] ?? '') . ' leaf';
                }

                if (isset($element['handler']['argument'][0])) {
                    $regex = '(?:`(?:\\\\(?:\\\\|`)|[^`])*+`?|[^`:])*+(?:\z(*SKIP)(*FAIL)|\K:)';
                    $argument = preg_split("#$regex#", $element['handler']['argument'][0], 2);
                    if (isset($argument[1]) && (strlen($argument[1]) === 0 || $argument[1][0] === ' ')) {
                        $Block['element']['name'] = 'dl';
                        $Block['element']['attributes']['class'] = strlen($argument[1]) ? 'docutils field-list' : 'docutils';

                        $element['name'] = 'dd';
                        $element['handler']['argument'][0] = trim($argument[1]);

                        $Block['element']['elements'][] = [
                            'name'       => 'div',
                            'attributes' => [
                                'class' => 'dtdd-container',
                            ],
                            'elements'   => [
                                [
                                    'name'    => 'dt',
                                    'text'    => $argument[0],
                                    'handler' => 'line',
                                ],
                                $element,
                            ],
                        ];

                        continue;
                    }
                }

                $Block['element']['elements'][] = $element;
            }
        }
        return $Block;
    }

    private function selector($selector)
    {
        $tag = '';
        $id = '';
        $classes = [];
        $styles = [];
        $attrs = [];

        $context = null;
        $escaping = false;
        $chars = preg_split('##u', $selector, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0, $l = count($chars); $i < $l; $i++) {
            $char = $chars[$i];
            if ($char === '"') {
                $escaping = !$escaping;
            }

            if (!$escaping) {
                if ($context !== '{' && $char === '#') {
                    $context = $char;
                    continue;
                }
                if ($context !== '{' && $char === '.') {
                    $context = $char;
                    $classes[] = '';
                    continue;
                }
                if ($char === '{') {
                    $context = $char;
                    $styles[] = '';
                    continue;
                }
                if ($char === ';') {
                    $styles[] = '';
                    continue;
                }
                if ($char === '}') {
                    $context = null;
                    continue;
                }
                if ($char === '[') {
                    $context = $char;
                    $attrs[] = '';
                    continue;
                }
                if ($context === '[' && $char === ' ') {
                    $attrs[] = '';
                    continue;
                }
                if ($char === ']') {
                    $context = null;
                    continue;
                }
            }

            if ($char === '\\') {
                $char = $chars[++$i];
            }

            if ($context === null) {
                $tag .= $char;
                continue;
            }
            if ($context === '#') {
                $id .= $char;
                continue;
            }
            if ($context === '.') {
                $classes[count($classes) - 1] .= $char;
                continue;
            }
            if ($context === '{') {
                $styles[count($styles) - 1] .= $char;
                continue;
            }
            if ($context === '[') {
                $attrs[count($attrs) - 1] .= $char;
                continue;
            }
        }

        $attrkv = [];
        if (strlen($tag)) {
            $attrkv[''] = $tag;
        }
        if (strlen($id)) {
            $attrkv['id'] = $id;
        }
        if ($classes) {
            $attrkv['class'] = implode(' ', $classes);
        }
        foreach ($attrs as $attr) {
            [$k, $v] = explode('=', $attr, 2) + [1 => $attr];
            $attrkv[$k] = is_string($v) ? json_decode($v) ?? $v : $v;
        }
        if ($styles) {
            $attrkv['style'] = implode(';', $styles);
        }

        return $attrkv;
    }
}
