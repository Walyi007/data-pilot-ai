<?php
require __DIR__ . '/vendor/autoload.php';
$user = App\Models\User::where('email','walyiyessoufou@gmail.com')->first();
echo $user->password . PHP_EOL;
