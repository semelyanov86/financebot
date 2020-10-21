<?php

namespace App\Http\Conversations;

use App\Http\Controllers\BotController;
use App\Services\FireflyService;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Carbon;

class ExpenseConversation extends Conversation
{
    protected $fireflyService;

    public $amount;

    public $bank;

    public $description;

    public $category;

    public $budget;

    public function __construct()
    {
        $this->fireflyService = new FireflyService();
    }


    public function run()
    {
        $this->ask('Введи сумму расхода', function (Answer $answer) {
            $text = $answer->getText();
            $sums = explode('+', $text);
            if (count($sums) < 2) {
                $converted = floatval($text);
            } else {
                $value = 0;
                foreach ($sums as $sum) {
                    $value += floatval($sum);
                }
                $converted = floatval($value);
            }

            if ($converted && $converted > 0) {
                $this->amount = $converted;

                $this->say('Принята сумма ' . $this->amount);
                $this->askDescription();
            } else {
                $this->say('Вы ввели какую-то странную сумму. Попробуйте снова.');
                $this->repeat();
            }
        });
    }

    protected function askDescription()
    {
        $this->ask('Опишите на что вы потратили деньги', function (Answer $answer) {
            $this->description = $answer->getText();
            $this->askBank();
        });
    }

    protected function askBank()
    {
        $buttons = array();
        $banks = $this->fireflyService->getAccounts();
        foreach ($banks as $bank) {
            if (!in_array($bank['id'], BotController::BLACKLIST_ACCOUNTS)) {
                $buttons[] = Button::create($bank['attributes']['name'])->value($bank['id']);
            }
        }
        $question = Question::create('С какого счёта вы потратили ' . $this->amount . ' руб?')->addButtons($buttons);
        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->bank = $answer->getValue();
                $this->askCategory();
            } else {
                $this->repeat();
            }
        });
    }

    protected function askBudget()
    {
        $buttons = array();
        $budgets = $this->fireflyService->getBudgets();
        foreach ($budgets as $budget) {
            $buttons[] = Button::create($budget['attributes']['name'])->value($budget['id']);
        }
        $question = Question::create('На какой бюджет записать расход в ' . $this->amount . ' руб?')->addButtons($buttons);
        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->budget = $answer->getValue();
                $this->askCategory();
            } else {
                $this->repeat();
            }
        });
    }

    protected function askCategory()
    {
        $buttons = array();
        $categories = $this->fireflyService->getCategories();
        foreach ($categories as $category) {
            $buttons[] = Button::create($category['attributes']['name'])->value($category['id']);
        }
        $question = Question::create('Выберите категорию, куда зачислить расход')->addButtons($buttons);
        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->category = $answer->getValue();
                $result = $this->fireflyService->sendTransaction(array(
                    'transactions' => array([
                        'type' => 'withdrawal',
                        'date' => Carbon::now()->format('Y-m-d H:i:s'),
                        'amount' => $this->amount,
                        'description' => $this->description,
                        'category_id' => $this->category,
                        'source_id' => $this->bank,
                        'destination_id' => 6,
//                        'budget_id' => $this->budget
                    ])
                ));
                $this->say('Принято! Номер транзакции ' . $result);
            } else {
                $this->repeat();
            }
        });
    }
}
