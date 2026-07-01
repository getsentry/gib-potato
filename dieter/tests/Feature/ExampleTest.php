<?php

test('the application returns a successful response', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
