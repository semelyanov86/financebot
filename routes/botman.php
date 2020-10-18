<?php

$botman = app('botman');

$botman->middleware->received(new \App\Http\Middleware\ReceivedMiddleware());

$botman->hears('/start', function($bot) {
    $bot->reply('Добро пожаловать в бот по управлению финансами. Чтобы отправить расход, напишите Расход. Для получения месячного баланса, пишите Баланс. Для остатков по счетам - Счета.');
});

$botman->hears('Расход', function($bot) {
    $bot->startConversation(new \App\Http\Conversations\ExpenseConversation);
});

$botman->hears('Перевод', function($bot) {
    $bot->startConversation(new \App\Http\Conversations\TransferConversation);
});

$botman->hears('Трансакции', \App\Http\Controllers\BotController::class . '@transactions');

$botman->hears('Баланс', \App\Http\Controllers\BotController::class . '@balance');

$botman->hears('Счета', \App\Http\Controllers\BotController::class . '@accounts');

$botman->hears('Помощь', function($bot) {
    $bot->reply('Бот поддерживает следующие команды: Расход, Перевод, Баланс, Счета, Трансакции. Если вы хотите прервать отправку, напишите Стоп');
})->skipsConversation();

$botman->hears('Стоп', function($bot) {
    $bot->reply('Отправка расхода остановлена!');
})->stopsConversation();

$botman->fallback(function ($bot) {
    $bot->reply('Я вас не понимаю. Напишите Помощь, чтобы получить список доступных мне команд');
});
