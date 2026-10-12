<?php

// Sin sesión, la página de inicio envía al login (todas las rutas están protegidas).
it('redirige al login a un visitante sin sesión', function () {
    $this->get('/')->assertRedirect(route('login'));
});
