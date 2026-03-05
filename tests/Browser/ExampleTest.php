<?php

use Laravel\Dusk\Browser;

test('basic example', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('https://laravel.com/docs/12.x/dusk')
                ->assertSee('Laravel');
    });
});
