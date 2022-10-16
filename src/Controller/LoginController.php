<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

class LoginController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login', 'startOpenId', 'openId']);
    }

    public function login()
    {
        $result = $this->Authentication->getResult();
        // If the user is logged in send them away.
        if ($result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/';

            return $this->redirect($target);
        }
    }

    public function startOpenId()
    {
        $url = 'https://slack.com/openid/connect/authorize' .
            '?scope=openid,email,profile' .
            '&response_type=code' .
            '&redirect_uri=' . env('SLACK_REDIRECT_URI') .
            '&client_id=' . env('SLACK_CLIENT_ID');

        return $this->redirect($url);
    }

    public function openId()
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
                    $this->Flash->error('Slack sign in failed');

                    return $this->redirect(['action' => 'login']);
                }

                $this->loadModel('Users');
                $user = $this->Users
                    ->findBySlackUserId(
                        $jwt->claims()->get('https://slack.com/user_id')
                    )
                    ->first();

                if (
                    $user instanceof User
                    && env('SLACK_TEAM_ID') === $jwt->claims()->get('https://slack.com/team_id')
                ) {
                    $this->Authentication->setIdentity($user);

                    return $this->redirect(['controller' => 'Home', 'action' => 'index']);
                }
            }
        }

        $this->Flash->error('Slack sign in failed');

        return $this->redirect(['action' => 'login']);
    }

    public function logout()
    {
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }
}
