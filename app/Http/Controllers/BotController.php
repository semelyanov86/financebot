<?php

namespace App\Http\Controllers;

use App\Services\FireflyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;

class BotController extends Controller
{
    public function balance(BotMan $bot)
    {
        $service = new FireflyService();
        $balance = $service->getBalance();
        $bot->reply('Состояние счёта на текущий месяц');
        if ($balance) {
            if ($balance->get('balance-in-RUB')) {
                $bot->reply('Баланс в рублях: ' . $balance->get('balance-in-RUB')->get('value_parsed'));
            }
            if ($balance->get('spent-in-RUB')) {
                $bot->reply('Расходы в этом месяце: ' . $balance->get('spent-in-RUB')->get('value_parsed'));
            }
            if ($balance->get('earned-in-RUB')) {
                $bot->reply('Заработано в этом месяце: ' . $balance->get('earned-in-RUB')->get('value_parsed'));
            }
            if ($balance->get('net-worth-in-RUB')) {
                $bot->reply('Чистая прибыль (₽): ' . $balance->get('net-worth-in-RUB')->get('value_parsed'));
            }
        }
    }

    public function accounts(BotMan $botMan)
    {
        $service = new FireflyService();
        $accounts = $service->getAvailableAccounts();
        $botMan->reply('Доступные остатки по счетам:');
        $accounts->each(function ($account) use ($botMan) {
            $attrs = $account->get('attributes');
            $botMan->reply($attrs['name'] . ' - ' . $attrs['current_balance'] . $attrs['currency_symbol']);
        });
    }

    public function transactions(BotMan $botMan)
    {
        $service = new FireflyService();
        $transactions = $service->getTransactions();
        $botMan->reply('Операции за последний день: ');
        $transactions->each(function ($transaction) use ($botMan) {
            $botMan->reply($transaction['description'] . ': ' . number_format(floatval($transaction['amount'])) . $transaction['currency_symbol'] . '. Дата ' . Carbon::parse($transaction['date'])->toDateTimeString());
        });
    }
}
