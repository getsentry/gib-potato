<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Message;
use Cake\I18n\DateTime;
use Exception;

/**
 * Credits Controller
 *
 * @property \App\Model\Table\CreditsTable $Credits
 */
class CreditsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        $this->set([
            'amount' => $user->getCreditAmount(),
            'hasCredit' => $user->getCredit(),
        ]);
    }

    public function add()
    {
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        if ($user->getCredit() !== null) {
            throw new Exception('We already granted you a credit!');
        }

        $creditsTable = $this->fetchTable('Credits');
        $credit = $creditsTable->newEntity([
            'user_id' => $this->Authentication->getIdentity()->getIdentifier(),
            'amount' => $user->getCreditAmount(),
        ]);
        $creditsTable->saveOrFail($credit);

        return $this->redirect('/stonks');
    }
}
