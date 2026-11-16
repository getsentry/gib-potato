<?php
declare(strict_types=1);

namespace App\Identifier;

use App\Model\Entity\User;
use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class ApiTokenIdentifier extends AbstractIdentifier
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

        $apiTokensTable = $this->fetchTable('ApiTokens');

        $apiToken = $apiTokensTable->find()
            ->where(['ApiTokens.token' => $credentials['token']])
            ->contain('Users')
            ->first();

        if ($apiToken === null) {
            return null;
        }

        if ($apiToken->user->status !== User::STATUS_ACTIVE) {
            return null;
        }

        $apiToken = $apiTokensTable->patchEntity($apiToken, [
            'last_used' => new DateTime(),
        ], [
            'accessibleFields' => [
                'last_used' => true,
            ],
        ]);
        $apiTokensTable->saveOrFail($apiToken);

        return $apiToken->user;
    }
}
