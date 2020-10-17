<?php

$botman = app('botman');

$botman->hears('foo', function($bot) {
    $bot->reply('bar');
});
