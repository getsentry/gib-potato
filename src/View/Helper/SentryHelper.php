<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Sentry\SentrySdk;
use Sentry\Tracing\Span;

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
     * @var \Sentry\Tracing\Span|null
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
     * @return string|void
     */
    public function sentryTracingMeta()
    {
        if (empty($this->span)) {
            return;
        }

        return $this->Html->meta(
            'sentry-trace',
            $this->span->toTraceparent()
        );
    }

    /**
     * @return string|void
     */
    public function sentryBaggageMeta()
    {
        if (empty($this->span)) {
            return;
        }

        return $this->Html->meta(
            'baggage',
            $this->span->toBaggage()
        );
    }
}
