<?php
declare(strict_types=1);

namespace App\Controller;

use Exception;

/**
 * Credits Controller
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
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

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
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
