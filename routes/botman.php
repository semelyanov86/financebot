<?php

use App\Services\FireflyService;

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

$botman->hears('Транзакции', \App\Http\Controllers\BotController::class . '@transactions');

$botman->hears('Баланс', \App\Http\Controllers\BotController::class . '@balance');

$botman->hears('Счета', \App\Http\Controllers\BotController::class . '@accounts');

$botman->hears('Помощь', function($bot) {
    $bot->reply('Бот поддерживает следующие команды: Расход, Перевод, Баланс, Счета, Транзакции, Категории, Бюджеты. Для удаления пишите Удалить транзакцию 7. Для получения статистики по категориям за прошлые месяцы пишите Категории месяц назад 2 (где 2 - количество месяцев назад). Аналогично в случае с бюджетами. Если вы хотите прервать отправку, напишите Стоп');
})->skipsConversation();

$botman->hears('Удалить транзакцию {id}', function($bot, $id) {
    $id = intval($id);
    $service = new FireflyService();
    $result = $service->deleteTransaction($id);
    if ($result) {
        $bot->reply('Транзакция с номером ' . $id . ' успешно удалена');
    } else {
        $bot->reply('Ошибка удаления транзакции!');
    }
});

$botman->hears('Категории', \App\Http\Controllers\BotController::class . '@categories');

$botman->hears('Категории месяц назад {num}', \App\Http\Controllers\BotController::class . '@categories');

$botman->hears('Бюджеты', \App\Http\Controllers\BotController::class . '@budgets');
$botman->hears('Бюджеты месяц назад {num}', \App\Http\Controllers\BotController::class . '@budgets');

$botman->hears('Стоп', function($bot) {
    $bot->reply('Отправка расхода остановлена!');
})->stopsConversation();

$botman->fallback(function ($bot) {
    $bot->reply('Я вас не понимаю. Напишите Помощь, чтобы получить список доступных мне команд');
});
