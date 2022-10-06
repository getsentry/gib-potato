<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

class OpenIdController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['index']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $client = new Client();
        $response = $client->post('https://slack.com/api/openid.connect.token', [
            'client_id' => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'code' => $this->request->getQuery('code'),
            'redirect_uri' => env('SLACK_REDIRECT_URI'),
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === true) {
                try {
                    $parser = new Parser(new JoseEncoder());
                    $jwt = $parser->parse($json['id_token']);
                } catch (Throwable $e) {
                    // Panic!
                }

                $this->loadModel('Users');
                $user = $this->Users->findBySlackUserId($jwt->claims()->get('https://slack.com/user_id'))
                    ->first();

                if ($user instanceof User && env('SLACK_TEAM_ID') === $jwt->claims()->get('https://slack.com/team_id')) {
                    $this->Authentication->setIdentity($user);

                    return $this->redirect(env('APP_FRONTEND_URL'));
                }
            }
        }

        return $this->redirect(env('APP_FRONTEND_URL') . 'error');
    }
}
