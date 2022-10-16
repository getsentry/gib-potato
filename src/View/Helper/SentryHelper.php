<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;
use Sentry\SentrySdk;

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

    protected $span;

    public function initialize(array $config): void
    {
        $this->span = SentrySdk::getCurrentHub()->getSpan();
    }

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
