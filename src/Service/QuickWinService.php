<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Model\Entity\QuickWin;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\Metrics\MetricsUnit;
use Sentry\SentrySdk;
use function Sentry\metrics;

class QuickWinService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Model\Entity\User $fromUser User who tagged #quickwin.
     * @param \App\Event\MessageEvent $event The event.
     * @return bool
     */
    public function store(
        User $fromUser,
        MessageEvent $event,
    ): bool {
        if (!str_contains($event->text, QuickWin::QUICK_WIN_TAG)) {
            return false;
        }

        $quickWinsTable = $this->fetchTable('QuickWins');

        $quickWin = $quickWinsTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'message' => $event->text,
            'permalink' => $event->permalink,
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'message' => true,
                'permalink' => true,
            ],
        ]);
        $quickWinsTable->saveOrFail($quickWin);

        metrics()->increment(
            key: 'gibpotato.message.quick_win',
            value: 1,
            unit: MetricsUnit::custom('tags'),
        );

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            $span->setData([
                'gibpotato.message.quick_win' => 1,
            ]);
        }

        return true;
    }
}
