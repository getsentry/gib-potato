<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class ApiController extends AppController
{

    public function index(): Response
    {
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'message' => 'Gib Potato! 🥔'
            ]));
    }

    public function user(): Response
    {
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'id' => 1,
                'name' => 'Krys',
                'picture' => 'https://',
                'count' => 12,
            ]));
    }

    public function users(): Response
    {
        usleep(500); // simulate slow api

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                [
                    'id' => '1',
                    'name' => 'Krys',
                    'picture' => 'https://',
                    'count' => 12,
                ],
                [
                    'id' => '2',
                    'name' => 'Michi',
                    'picture' => 'https://',
                    'count' => 3,
                ],
                [
                    'id' => '3',
                    'name' => 'Gino',
                    'picture' => 'https://',
                    'count' => 5,
                ],
                [
                    'id' => '4',
                    'name' => 'Tobias',
                    'picture' => 'https://',
                    'count' => 9,
                ],
            ]));
    }

    public function messages(): Response
    {
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                [
                    'id' => '1',
                    'sender_id' => '1',
                    'sender_name' => 'Krys',
                    'receiver_id' => '2',
                    'receiver_name' => 'Michi',
                    'amount' => 5,
                ],
                [
                    'id' => '2',
                    'sender_id' => '3',
                    'sender_name' => 'Gino',
                    'receiver_id' => '4',
                    'receiver_name' => 'Tobias',
                    'amount' => 2,
                ],
            ]));
    }
}
