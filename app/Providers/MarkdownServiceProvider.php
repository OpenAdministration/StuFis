<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\CommonMark\Environment\Environment;
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

class MarkdownServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->afterResolving(Environment::class, function (Environment $environment): void {
            $environment->mergeConfig(['default_attributes' => $this->defaultAttributes()]);
        });
    }

    /**
     * Default attributes applied to rendered markdown nodes.
     *
     * These live here rather than in config/markdown.php because the Heading and
     * ListBlock rules are closures, which cannot be serialized by `config:cache`.
     *
     * @return array<class-string, array<string, string|callable>>
     */
    private function defaultAttributes(): array
    {
        return [
            Heading::class => [
                'class' => static fn (Heading $node): string => match ($node->getLevel()) {
                    1 => 'text-xl font-semibold text-gray-900 dark:text-white mb-6 mt-8 first:mt-0',
                    2 => 'text-lg font-semibold text-gray-900 dark:text-white mb-4 mt-6',
                    3 => 'text-lg font-medium text-gray-900 dark:text-white mb-3 mt-5',
                    4 => 'text-base font-medium text-gray-900 dark:text-white mb-2 mt-4',
                    5 => 'text-base font-medium text-gray-900 dark:text-white mb-2 mt-3',
                    6 => 'text-base font-medium text-gray-700 dark:text-gray-300 mb-2 mt-3',
                    default => 'font-medium text-gray-900 dark:text-white mb-2 mt-3',
                },
            ],

            Paragraph::class => [
                'class' => 'text-gray-700 dark:text-gray-300 leading-relaxed mb-4',
            ],

            Link::class => [
                'class' => 'text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline decoration-1 underline-offset-2 transition-colors',
            ],

            Table::class => [
                'class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6',
            ],

            TableRow::class => [
                'class' => 'hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors',
            ],

            TableCell::class => [
                'class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100',
            ],

            FencedCode::class => [
                'class' => 'bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto border border-gray-200 dark:border-gray-700',
            ],

            Code::class => [
                'class' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-1 rounded text-sm font-mono border border-gray-200 dark:border-gray-700',
            ],

            ListBlock::class => [
                'class' => static function (ListBlock $node): string {
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

            BlockQuote::class => [
                'class' => 'border-l-4 border-blue-500 pl-4 py-2 mb-4 bg-blue-50 dark:bg-blue-900/20 text-gray-700 dark:text-gray-300 italic',
            ],

            ThematicBreak::class => [
                'class' => 'my-8 border-gray-300 dark:border-gray-600',
            ],

            Image::class => [
                'class' => 'max-w-full h-auto rounded-lg shadow-sm mb-4',
            ],

            Strong::class => [
                'class' => 'font-medium text-gray-900 dark:text-white',
            ],

            Emphasis::class => [
                'class' => 'italic text-gray-800 dark:text-gray-200',
            ],
        ];
    }
}
