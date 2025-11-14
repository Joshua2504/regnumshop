<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Session.php';
require_once __DIR__ . '/../../src/helpers.php';

$session = new Session();
$session->destroy();
redirect('/admin/index.php');
