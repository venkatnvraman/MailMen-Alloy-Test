<?php

$envFile = realpath(dirname(__FILE__) . '/..') . '/.env';
$env = parse_ini_file($envFile);

return [
    'host' => $env['DB_HOST'] . ':' . $env['DB_PORT'],
    'name' => $env['DB_DATABASE'],
    'user' => $env['DB_USERNAME'],
    'pw' => $env['DB_PASSWORD'],
    'mail-host' => $env['MAIL_HOST'],
    'mail-port' => $env['MAIL_PORT'],
    'mail-username' => $env['MAIL_USERNAME'],
    'mail-password' => $env['MAIL_PASSWORD'],
    'mail-encryption' => $env['MAIL_ENCRYPTION'],
];