<?php

namespace ryunosuke\HtMarkdown;

use Parsedown;

class Markdown extends Parsedown
{
    public function __construct($options)
    {
        $this->setMarkupEscaped(false);
        $this->setBreaksEnabled(true);
        $this->setUrlsLinked(true);
        $this->setStrictMode(!!($options['strict_mode'] ?? false));
        $this->setSafeMode(!!($options['safe_mode'] ?? false));

        $newInlineTypes = [
            '=' => ['Marker'],
            '{' => ['Badge'],
        ];
        foreach ($newInlineTypes as $char => $inline) {
            $this->InlineTypes[$char] = array_merge($this->InlineTypes[$char] ?? [], $inline);
        }
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));

        $newBlockTypes = [
            '<' => ['Here'],
            '"' => ['Note'],
            '/' => ['Side'],
            '.' => ['Detail'],
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
        $Inline = parent::inlineText($text);
        foreach ($Inline['element']['elements'] as $n => $element) {
            if (($element['name'] ?? '') === 'br') {
                $Inline['element']['elements'][$n]['attributes']['class'] = 'break-line';
            }
        }
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

    protected function _commonBlock($Line, $Element)
    {
        $marker = $Line['text'][0];

        $openerLength = strspn($Line['text'], $marker);
        if ($openerLength < 3) {
            return;
        }

        $infostring = trim(substr($Line['text'], $openerLength), "\t ");
        if (strpos($infostring, $marker) !== false) {
            return;
        }

        $Block = [
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

    protected function _commonBlockContinue($Line, $Block, &$Ref)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Ref .= str_repeat("\n", $Block['interrupted']);
            unset($Block['interrupted']);
        }

        if (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength'] and chop(substr($Line['text'], $len), ' ') === '') {
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
        $Block = parent::blockFencedCode($Line);
        if ($Block !== null) {
            $Block['element']['element']['name'] = 'div';

            $class = $Block['element']['element']['attributes']['class'] ?? '';
            [, $infostring] = explode('language-', $class, 2) + [1 => ''];

            if (strlen($infostring)) {
                [$lang, $label] = explode(':', $infostring, 2) + [1 => ''];
                $class .= ' ' . $lang;
                $Block['element']['attributes']['data-label'] = $label;
            }
            $Block['element']['element']['attributes']['class'] = $class . ' code';
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

    protected function blockTable($Line, array $Block = null)
    {
        $Block = parent::blockTable($Line, $Block);
        if ($Block !== null) {
            $Block['element']['attributes']['class'] = ($Block['element']['attributes']['class'] ?? '') . ' docutils align-default';
        }
        return $Block;
    }

    protected function blockList($Line, array $CurrentBlock = null)
    {
        $Block = parent::blockList($Line, $CurrentBlock);
        if ($Block !== null && $Block['element']['name'] === 'ul') {
            $Block['element']['attributes']['class'] = ($Block['element']['attributes']['class'] ?? '') . ' simple';
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
}
