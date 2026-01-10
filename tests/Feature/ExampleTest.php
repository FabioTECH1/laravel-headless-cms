<?php

test('returns a successful response', function () {
    $response = $this->get('/');
    // it should redirect to admin login
    $response->assertRedirect('/admin/login');
});
