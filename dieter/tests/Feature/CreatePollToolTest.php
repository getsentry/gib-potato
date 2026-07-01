<?php

use App\Ai\Tools\CreatePoll;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    config([
        'services.backend.url' => 'http://backend.test',
        'services.backend.token' => 'test-token',
    ]);
});

test('it creates a poll successfully', function () {
    Http::fake([
        'backend.test/polls' => Http::response([
            'id' => 1,
            'title' => 'Best potato dish?',
        ], 201),
    ]);

    $tool = new CreatePoll;

    $result = $tool->handle(new Request([
        'title' => 'Best potato dish?',
        'options' => ['Mashed', 'Baked', 'Fried'],
        'channel' => 'C12345',
        'user_slack_id' => 'U12345',
    ]));

    expect((string) $result)->toContain('Best potato dish?');

    Http::assertSent(function ($request) {
        return $request->url() === 'http://backend.test/polls'
            && $request['title'] === 'Best potato dish?'
            && $request['options'] === ['Mashed', 'Baked', 'Fried']
            && $request['channel'] === 'C12345'
            && $request['user_slack_id'] === 'U12345'
            && $request->hasHeader('Authorization', 'test-token');
    });
});

test('it returns an error when the backend rejects the poll', function () {
    Http::fake([
        'backend.test/polls' => Http::response([
            'error' => 'At least two options are required.',
        ], 422),
    ]);

    $tool = new CreatePoll;

    $result = $tool->handle(new Request([
        'title' => 'Best potato?',
        'options' => ['Only one'],
        'channel' => 'C12345',
        'user_slack_id' => 'U12345',
    ]));

    expect((string) $result)->toContain('At least two options are required.');
});

test('it returns an error when the backend is unavailable', function () {
    Http::fake([
        'backend.test/polls' => Http::response(null, 500),
    ]);

    $tool = new CreatePoll;

    $result = $tool->handle(new Request([
        'title' => 'Best potato?',
        'options' => ['Mashed', 'Baked'],
        'channel' => 'C12345',
        'user_slack_id' => 'U12345',
    ]));

    expect((string) $result)->toContain('Failed to create the poll');
});

test('it returns an error when backend is not configured', function () {
    config([
        'services.backend.url' => null,
        'services.backend.token' => null,
    ]);

    $tool = new CreatePoll;

    $result = $tool->handle(new Request([
        'title' => 'Best potato?',
        'options' => ['Mashed', 'Baked'],
        'channel' => 'C12345',
        'user_slack_id' => 'U12345',
    ]));

    expect((string) $result)->toContain('not configured');
});
