<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Sentry\SentrySdk;
use Sentry\Tracing\Span;

/**
 * Sentry helper
 */
class SentryHelper extends Helper
{
    /**
     * @var string[]
     */
    protected $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];

    /**
     * @var \Sentry\Tracing\Span|null
     */
    protected Span|null $span;

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
