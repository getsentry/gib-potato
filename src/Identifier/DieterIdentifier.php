<?php
declare(strict_types=1);

namespace App\Identifier;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Cake\ORM\Locator\LocatorAwareTrait;
use function Cake\Core\env;

class DieterIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public function identify(array $credentials): ArrayAccess|array|null
    {
        if (!isset($credentials['token'])) {
            return null;
        }

        if (env('DIETER_TOKEN') === $credentials['token']) {
            return [
                'id' => 2,
                'username' => 'Dieter',
            ];
        }

        return null;
    }
}
