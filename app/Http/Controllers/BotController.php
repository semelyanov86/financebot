<?php

namespace App\Http\Controllers;

use App\Services\FireflyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;

class BotController extends Controller
{
    const BLACKLIST_ACCOUNTS = array(6, 16, 2, 18, 14, 13, 4);

    public function balance(BotMan $bot)
    {
        $service = new FireflyService();
        $balance = $service->getBalance();
        $msg = '<b>Состояние счёта на текущий месяц</b>';
        if ($balance) {
            if ($balance->get('balance-in-RUB')) {
                $msg .= PHP_EOL . 'Баланс в рублях: ' . $balance->get('balance-in-RUB')->get('value_parsed');
            }
            if ($balance->get('spent-in-RUB')) {
                $msg .= PHP_EOL . 'Расходы в этом месяце: ' . $balance->get('spent-in-RUB')->get('value_parsed');
            }
            if ($balance->get('earned-in-RUB')) {
                $msg .= PHP_EOL . 'Заработано в этом месяце: ' . $balance->get('earned-in-RUB')->get('value_parsed');
            }
            if ($balance->get('net-worth-in-RUB')) {
                $msg .= PHP_EOL . 'Чистая прибыль (₽): ' . $balance->get('net-worth-in-RUB')->get('value_parsed');
            }
        }
        $bot->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function accounts(BotMan $botMan)
    {
        $service = new FireflyService();
        $accounts = $service->getAvailableAccounts();
        $msg = '<b>Доступные остатки по счетам:</b>';
        $blacklist = self::BLACKLIST_ACCOUNTS;
        $accounts->each(function ($account) use ($botMan, $blacklist, &$msg) {
            if (!in_array($account->get('id'), $blacklist)) {
                $attrs = $account->get('attributes');
                $msg .= PHP_EOL . $attrs['name'] . ' - ' . $attrs['current_balance'] . $attrs['currency_symbol'];
            }
        });
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function transactions(BotMan $botMan)
    {
        $service = new FireflyService();
        $transactions = $service->getTransactions();
        $msg = '<b>Операции за последний день:</b>';
        $transactions->each(function ($transaction) use ($botMan, &$msg) {
            $msg .= PHP_EOL . $transaction['description'] . ': ' . number_format(floatval($transaction['amount'])) . $transaction['currency_symbol'] . '. Дата ' . Carbon::parse($transaction['date'])->toDateTimeString();
        });
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }
}
