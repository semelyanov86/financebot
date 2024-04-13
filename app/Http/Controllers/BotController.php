<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Services\DataBuilderService;
use App\Services\FireflyService;
use BotMan\BotMan\BotManFactory;
use BotMan\Drivers\Telegram\TelegramDriver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BotController extends Controller
{
    const IP_CACHE = 'LAST_IP';

    const BLACKLIST_ACCOUNTS = array(6, 16, 2, 18, 14, 13, 4, 25, 26, 27, 23, 24, 21, 22, 28, 29, 30, 31, 32, 33, 35, 36, 37, 38);

    public function balance(BotMan $bot)
    {
        $service = new FireflyService();
        $balance = $service->getBalance();
        $msg = '<b>Состояние счёта на текущий месяц</b>';
        if ($balance) {
            if ($balance->get('balance-in-EUR')) {
                $msg .= PHP_EOL . 'Баланс в евро: ' . $balance->get('balance-in-EUR')->get('value_parsed');
            }
            if ($balance->get('balance-in-RUB')) {
                $msg .= PHP_EOL . 'Баланс в рублях: ' . $balance->get('balance-in-RUB')->get('value_parsed');
            }
            if ($balance->get('spent-in-EUR')) {
                $msg .= PHP_EOL . 'Расходы в этом месяце в евро: ' . $balance->get('spent-in-EUR')->get('value_parsed');
            }
            if ($balance->get('spent-in-RUB')) {
                $msg .= PHP_EOL . 'Расходы в этом месяце в рублях: ' . $balance->get('spent-in-RUB')->get('value_parsed');
            }
            if ($balance->get('earned-in-EUR')) {
                $msg .= PHP_EOL . 'Заработано в этом месяце в евро: ' . $balance->get('earned-in-EUR')->get('value_parsed');
            }
            if ($balance->get('earned-in-RUB')) {
                $msg .= PHP_EOL . 'Заработано в этом месяце в рублях: ' . $balance->get('earned-in-RUB')->get('value_parsed');
            }
            if ($balance->get('net-worth-in-EUR')) {
                $msg .= PHP_EOL . 'Чистая прибыль (EUR): ' . $balance->get('net-worth-in-EUR')->get('value_parsed');
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
        $accounts->each(function ($account) use ($botMan, &$msg) {
            $attrs = $account->get('attributes');
            if ($attrs['type'] === 'asset') {
                $msg .= PHP_EOL . '- ' . $attrs['name'] . ': ' . $attrs['current_balance'] . ' ' . $attrs['currency_symbol'];
            }
        });
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function transactions(BotMan $botMan)
    {
        $service = new FireflyService();
        $transactions = $service->getTransactions();
        $msg = '<b>Операции за последний день:</b>';
        $totalEur = 0;
        $totalRub = 0;
        $transactions->each(function ($transaction) use ($botMan, &$msg, &$totalEur, &$totalRub) {
            $amount = (float) $transaction['amount'];
            $msg .= PHP_EOL . '- ' . $transaction['description'] . ': ' . number_format($amount, 2, ',', ' ') . ' ' . $transaction['currency_symbol'] . '. ' . Carbon::parse($transaction['date'])->diffInDays() . ' дней назад';
            if ($transaction['currency_id'] === '1') {
                $totalEur += $amount;
            }
            if ($transaction['currency_id'] === '20') {
                $totalRub += $amount;
            }
        });
        $msg .= PHP_EOL;
        $msg .= PHP_EOL . '<b>Итого в EUR</b>: ' . number_format($totalEur, 2, ',', ' ');
        $msg .= PHP_EOL . '<b>Итого в RUB</b>: ' . number_format($totalRub, 2, ',', ' ');
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function categories(BotMan $botMan, $num = false)
    {
        $service = new FireflyService();
        if ($num) {
            $num = intval($num);
            $start = \Illuminate\Support\Carbon::now()->subMonths($num)->firstOfMonth()->format('Y-m-d');
            $end = \Illuminate\Support\Carbon::now()->subMonths($num)->endOfMonth()->format('Y-m-d');
            $categories = $service->getCategoriesStat($start, $end);
            $msg = '<b>Статистика по категориям за период ' . $start . ' - ' . $end . '</b>';
        } else {
            $categories = $service->getCategoriesStat();
            $msg = '<b>Статистика по категориям за текущий месяц</b>';
        }
        $totalRub = 0;
        $totalEur = 0;
        $categories->each(function ($category) use ($botMan, &$msg, &$totalEur, &$totalRub) {
            $expense = $category->get('difference_float') * -1;
            if ($expense > 0) {
                $msg .= PHP_EOL . '- <b>' . $category->get('name') . '</b>: ' . number_format($expense, 2, ',', ' ') . ' ' . $category->get('currency_code');
                if ($category->get('currency_id') === '1') {
                    $totalEur += $expense;
                }
                if ($category->get('currency_id') === '20') {
                    $totalRub += $expense;
                }
            }
        });
        $msg .= PHP_EOL;
        $msg .= PHP_EOL . '<b>Итого в EUR</b>: ' . number_format($totalEur, 2, ',', ' ');
        $msg .= PHP_EOL . '<b>Итого в RUB</b>: ' . number_format($totalRub, 2, ',', ' ');
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function budgets(BotMan $botMan, $num = false)
    {
        $service = new FireflyService();
        if ($num) {
            $num = intval($num);
            $start = \Illuminate\Support\Carbon::now()->subMonths($num)->firstOfMonth()->format('Y-m-d H:i:s');
            $end = \Illuminate\Support\Carbon::now()->subMonths($num)->endOfMonth()->format('Y-m-d H:i:s');
            $categories = $service->getBudgetsStat($start, $end);
            $msg = '<b>Статистика по бюджетам за период ' . $start . ' - ' . $end . '</b>';
        } else {
            $categories = $service->getBudgetsStat();
            $msg = '<b>Статистика по бюджетам за текущий месяц</b>';
        }
        $categories->each(function ($category) use ($botMan, &$msg) {
            $amountVal = $category->get('difference_float') * -1;
            $msg .= PHP_EOL . '- ' . $category->get('name') . ': ' . number_format($amountVal, 2, ',', ' ') . ' ' . $category->get('currency_code');
        });
        $botMan->reply($msg, ['parse_mode' => 'HTML']);
    }

    public function subscribe(Request $request, DataBuilderService $builderService)
    {
        $data = $request->all();
        $builderService->validateData($data);
        if (isset($data['mautic.form_on_submit']['submission'])) {
            $submissionData = $data['mautic.form_on_submit']['submission'];
        } elseif (isset($data['mautic.form_on_submit'][0]['submission'])) {
            $submissionData = $data['mautic.form_on_submit'][0]['submission'];
        } else {
            throw new \DomainException('No data!');
        }
        $newIp = $submissionData['results']['ip'];
        if (Cache::has(self::IP_CACHE)) {
            $lastIp = Cache::get(self::IP_CACHE);
            if($lastIp == $newIp) {
                return response()->json('You already submit the form', 200);
            }
        }
        try {
            $msg = $builderService->generateMessage($submissionData);
            Cache::forget(self::IP_CACHE);
            Cache::put(self::IP_CACHE, $newIp);
            $builderService->sendMessage($msg);
        } catch (\DomainException $exception) {
            return response()->json($exception->getMessage(), 200);
        }
    }

    public function subscribeSergey(Request $request, DataBuilderService $builderService)
    {
        $data = $request->all();
        $builderService->validateData($data);
        if (isset($data['mautic.form_on_submit']['submission'])) {
            $submissionData = $data['mautic.form_on_submit']['submission'];
        } elseif (isset($data['mautic.form_on_submit'][0]['submission'])) {
            $submissionData = $data['mautic.form_on_submit'][0]['submission'];
        } else {
            throw new \DomainException('No data!');
        }
        $newIp = $submissionData['results']['ip'];
        if (Cache::has(self::IP_CACHE)) {
            $lastIp = Cache::get(self::IP_CACHE);
            if($lastIp == $newIp) {
                return response()->json('You already submit the form', 200);
            }
        }
        try {
            $msg = $builderService->generateMessage($submissionData, 'sergeyem');
            Cache::forget(self::IP_CACHE);
            Cache::put(self::IP_CACHE, $newIp);
            $builderService->sendMessage($msg);
        } catch (\DomainException $exception) {
            return response()->json($exception->getMessage(), 200);
        }
    }
}
