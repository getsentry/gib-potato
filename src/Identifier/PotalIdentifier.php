<?php
declare(strict_types=1);

namespace App\Identifier;

use Authentication\Identifier\AbstractIdentifier;
use Cake\ORM\Locator\LocatorAwareTrait;

class PotalIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public function identify(array $credentials)
    {
        if (!isset($credentials['token'])) {
            return null;
        }

        if (env('POTAL_TOKEN') === $credentials['token']) {
            return [
                'id' => 1,
                'username' => 'Potal',
            ];
        }

        return null;
    }
}
