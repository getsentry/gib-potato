<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Cake\Http\Response;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class LoginController extends AppController
{
    /**
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login', 'startOpenId', 'openId']);
    }

    /**
     * @return \Cake\Http\Response|null|void
     */
    public function login()
    {
        $result = $this->Authentication->getResult();
        // If the user is logged in send them away.
        if ($result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/';

            return $this->redirect($target);
        }
    }

    /**
     * @param string $mode The auth source.
     * @return \Cake\Http\Response|null
     */
    public function startOpenId(string $mode = 'web'): ?Response
    {
        $url = 'https://slack.com/openid/connect/authorize' .
            '?scope=openid,email,profile' .
            '&response_type=code' .
            '&redirect_uri=' . env('SLACK_REDIRECT_URI') . ($mode === 'mobile' ? '/mobile' : '') .
            '&client_id=' . env('SLACK_CLIENT_ID');

        return $this->redirect($url);
    }

    /**
     * @param string $mode The auth source.
     * @return \Cake\Http\Response|null
     */
    public function openId(string $mode = 'web'): ?Response
    {
        $client = new Client();
        $response = $client->post('https://slack.com/api/openid.connect.token', [
            'client_id' => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'code' => $this->request->getQuery('code'),
            'redirect_uri' => env('SLACK_REDIRECT_URI') . ($mode === 'mobile' ? '/mobile' : ''),
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === true) {
                try {
                    $parser = new Parser(new JoseEncoder());
                    /** @var \Lcobucci\JWT\Token\Plain $jwt */
                    $jwt = $parser->parse($json['id_token']);
                } catch (Throwable $e) {
                    $this->Flash->error('Slack sign in failed');

                    return $this->redirect(['action' => 'login']);
                }

                $this->fetchTable('Users');
                $user = $this->fetchTable('Users')
                    ->findBySlackUserId(
                        $jwt->claims()->get('https://slack.com/user_id')
                    )
                    ->contain('ApiTokens')
                    ->first();

                if (
                    $user instanceof User
                    && env('SLACK_TEAM_ID') === $jwt->claims()->get('https://slack.com/team_id')
                ) {
                    if ($mode === 'web') {
                        $this->Authentication->setIdentity($user);

                        return $this->redirect(['controller' => 'Home', 'action' => 'index']);
                    }

                    if ($mode === 'mobile') {
                        $this->set('token', $user->api_token->token);

                        return $this->render('mobile');
                    }
                }
            }

            $this->Flash->error('You need to gib or recieve something to be able to sign in...');

            return $this->redirect(['action' => 'login']);
        }

        $this->Flash->error('Slack sign in failed');

        return $this->redirect(['action' => 'login']);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }
}
