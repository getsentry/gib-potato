<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

class TestController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['throw']);
    }

    /**
     * Controller action that throws an exception on purpose,
     * so the issue experience team can test things.
     */
    public function throw()
    {
        throw new \Exception("Foo::bar");
    }
}
