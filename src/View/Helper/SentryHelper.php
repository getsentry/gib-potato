<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Sentry\SentrySdk;
use Sentry\Tracing\Spans\Span;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class SentryHelper extends Helper
{
    /**
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * @var \Sentry\Tracing\Spans\Span|null
     */
    protected ?Span $span;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        $this->span = SentrySdk::getCurrentHub()->getSpan();
    }

    /**
     * @return string|null
     */
    public function sentryTracingMeta(): ?string
    {
        if (empty($this->span)) {
            return null;
        }

        return $this->Html->meta(
            'sentry-trace',
            $this->span->toTraceparent()
        );
    }

    // /**
    //  * @return string|null
    //  */
    // public function sentryBaggageMeta(): ?string
    // {
    //     if (empty($this->span)) {
    //         return null;
    //     }

    //     return $this->Html->meta(
    //         'baggage',
    //         $this->span->toBaggage()
    //     );
    // }
}
