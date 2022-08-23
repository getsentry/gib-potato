<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

class OpenIDController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $client = new Client;
        $response = $client->post('https://slack.com/api/openid.connect.token', [
            'client_id' => env('SLACK_CLIENT_ID'),
            'client_secret' => env('SLACK_CLIENT_SECRET'),
            'code' => $this->request->getQuery('code'),
            'redirect_uri' => 'https://f91b-213-164-1-114.eu.ngrok.io/open-id'
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
                $user = $this->Users->newEntity([
                    'slack_user_id' => $jwt->claims()->get('https://slack.com/user_id'),
                    'slack_name' => $jwt->claims()->get('name'),
                    'slack_picture' => $jwt->claims()->get('picture'),
                ]);

                try {
                    $this->Users->saveOrFail($user);

                    return $this->redirect(env('APP_FRONTEND_URL'));
                } catch (Throwable $e) {
                    // Panic!
                }
            }
        }

        return $this->redirect(env('APP_FRONTEND_URL') . 'error');
    }
}
