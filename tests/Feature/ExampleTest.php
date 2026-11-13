<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()->assertSee('من أول مسحة QR.');
});
