<?php

it('has reservations/createreservation page', function () {
    $response = $this->get('/reservations/createreservation');

    $response->assertStatus(200);
});
