<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Session.php';
require_once __DIR__ . '/../src/helpers.php';

$session = new Session();

if ($session->isLoggedIn()) {
    $auth = new Auth();
    $auth->logout($session->get('session_token'));
}

$session->destroy();
redirect('/');
