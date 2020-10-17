<?php

namespace App\Http\Conversations;

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

    public function __construct()
    {
        $this->fireflyService = new FireflyService();
    }


    public function run()
    {
        $this->ask('Введи сумму расхода', function (Answer $answer) {
            $converted = floatval($answer->getText());
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
            $buttons[] = Button::create($bank['attributes']['name'])->value($bank['id']);
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
                        'date' => Carbon::now(),
                        'amount' => $this->amount,
                        'description' => $this->description,
                        'category_id' => $this->category,
                        'source_id' => $this->bank,
                        'destination_id' => 6
                    ])
                ));
                $this->say('Принято! Номер трансакции ' . $result);
            } else {
                $this->repeat();
            }
        });
    }
}