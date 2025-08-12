<?php

declare(strict_types=1);

/*
 * This file is part of Laravel Markdown.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Node\Block\Paragraph;

return [

    /*
    |--------------------------------------------------------------------------
    | Enable View Integration
    |--------------------------------------------------------------------------
    |
    | This option specifies if the view integration is enabled so you can write
    | markdown views and have them rendered as html. The following extensions
    | are currently supported: ".md", ".md.php", and ".md.blade.php". You may
    | disable this integration if it is conflicting with another package.
    |
    | Default: true
    |
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | CommonMark Extensions
    |--------------------------------------------------------------------------
    |
    | This option specifies what extensions will be automatically enabled.
    | Simply provide your extension class names here.
    |
    | Default: [
    |              League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension::class,
    |              League\CommonMark\Extension\Table\TableExtension::class,
    |          ]
    |
    */

    'extensions' => [
        League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension::class,
        League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension::class,
        League\CommonMark\Extension\Table\TableExtension::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Renderer Configuration
    |--------------------------------------------------------------------------
    |
    | This option specifies an array of options for rendering HTML.
    |
    | Default: [
    |              'block_separator' => "\n",
    |              'inner_separator' => "\n",
    |              'soft_break'      => "\n",
    |          ]
    |
    */

    'renderer' => [
        'block_separator' => "\n",
        'inner_separator' => "\n",
        'soft_break' => "\n",
    ],

    /*
    |--------------------------------------------------------------------------
    | Commonmark Configuration
    |--------------------------------------------------------------------------
    |
    | This option specifies an array of options for commonmark.
    |
    | Default: [
    |              'enable_em' => true,
    |              'enable_strong' => true,
    |              'use_asterisk' => true,
    |              'use_underscore' => true,
    |              'unordered_list_markers' => ['-', '+', '*'],
    |          ]
    |
    */

    'commonmark' => [
        'enable_em' => true,
        'enable_strong' => true,
        'use_asterisk' => true,
        'use_underscore' => true,
        'unordered_list_markers' => ['-', '+', '*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Input
    |--------------------------------------------------------------------------
    |
    | This option specifies how to handle untrusted HTML input.
    |
    | Default: 'strip'
    |
    */

    'html_input' => 'strip',

    /*
    |--------------------------------------------------------------------------
    | Allow Unsafe Links
    |--------------------------------------------------------------------------
    |
    | This option specifies whether to allow risky image URLs and links.
    |
    | Default: true
    |
    */

    'allow_unsafe_links' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Nesting Level
    |--------------------------------------------------------------------------
    |
    | This option specifies the maximum permitted block nesting level.
    |
    | Default: PHP_INT_MAX
    |
    */

    'max_nesting_level' => PHP_INT_MAX,

    /*
    |--------------------------------------------------------------------------
    | Slug Normalizer
    |--------------------------------------------------------------------------
    |
    | This option specifies an array of options for slug normalization.
    |
    | Default: [
    |              'max_length' => 255,
    |              'unique' => 'document',
    |          ]
    |
    */

    'slug_normalizer' => [
        'max_length' => 255,
        'unique' => 'document',
    ],

    'default_attributes' => [
        Heading::class => [
            'class' => static fn (Heading $node) => match ($node->getLevel()) {
                1 => 'text-xl font-semibold text-gray-900 dark:text-white mb-6 mt-8 first:mt-0',
                2 => 'text-lg font-semibold text-gray-900 dark:text-white mb-4 mt-6',
                3 => 'text-lg font-medium text-gray-900 dark:text-white mb-3 mt-5',
                4 => 'text-base font-medium text-gray-900 dark:text-white mb-2 mt-4',
                5 => 'text-base font-medium text-gray-900 dark:text-white mb-2 mt-3',
                6 => 'text-base font-medium text-gray-700 dark:text-gray-300 mb-2 mt-3',
                default => 'font-medium text-gray-900 dark:text-white mb-2 mt-3',
            },
        ],
        // Paragraphs with proper spacing
        Paragraph::class => [
            'class' => 'text-gray-700 dark:text-gray-300 leading-relaxed mb-4',
        ],

        // Links styled like Flux buttons/links
        Link::class => [
            'class' => 'text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline decoration-1 underline-offset-2 transition-colors',
        ],

        // Tables with Flux-like styling
        Table::class => [
            'class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6',
        ],

        TableRow::class => [
            'class' => 'hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors',
        ],

        TableCell::class => [
            'class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100',
        ],

        // Code blocks with syntax highlighting ready
        FencedCode::class => [
            'class' => 'bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto border border-gray-200 dark:border-gray-700',
        ],

        Code::class => [
            'class' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-1 rounded text-sm font-mono border border-gray-200 dark:border-gray-700',
        ],

        // Lists with proper spacing
        ListBlock::class => [
            'class' => static function (ListBlock $node) {
                $classes = 'mb-4 space-y-2';
                if ($node->getListData()->type === ListBlock::TYPE_ORDERED) {
                    return $classes.' list-decimal list-inside';
                }

                return $classes.' list-disc list-inside';
            },
        ],

        ListItem::class => [
            'class' => 'text-gray-700 dark:text-gray-300 leading-relaxed',
        ],

        // Blockquotes
        BlockQuote::class => [
            'class' => 'border-l-4 border-blue-500 pl-4 py-2 mb-4 bg-blue-50 dark:bg-blue-900/20 text-gray-700 dark:text-gray-300 italic',
        ],

        // Horizontal rules
        ThematicBreak::class => [
            'class' => 'my-8 border-gray-300 dark:border-gray-600',
        ],

        // Images
        Image::class => [
            'class' => 'max-w-full h-auto rounded-lg shadow-sm mb-4',
        ],

        // Strong and emphasis
        Strong::class => [
            'class' => 'font-medium text-gray-900 dark:text-white',
        ],

        Emphasis::class => [
            'class' => 'italic text-gray-800 dark:text-gray-200',
        ],
    ],
];
