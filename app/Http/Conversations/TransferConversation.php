<?php

namespace App\Http\Conversations;

use App\Services\FireflyService;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Carbon;

class TransferConversation extends Conversation
{
    protected $fireflyService;

    public $amount;

    public $bank1;

    public $bank2;

    public $description;


    public function __construct()
    {
        $this->fireflyService = new FireflyService();
    }


    public function run()
    {
        $this->ask('Введи сумму трансфера', function (Answer $answer) {
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
        $this->ask('Опишите назначение перевода', function (Answer $answer) {
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
        $question = Question::create('С какого счёта вы переводите деньги ' . $this->amount . ' руб?')->addButtons($buttons);
        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->bank1 = $answer->getValue();
                $this->askBank2();
            } else {
                $this->repeat();
            }
        });
    }

    protected function askBank2()
    {
        $buttons = array();
        $banks = $this->fireflyService->getAccounts();
        foreach ($banks as $bank) {
            $buttons[] = Button::create($bank['attributes']['name'])->value($bank['id']);
        }
        $question = Question::create('На какой счёт вы переводите деньги ' . $this->amount . ' руб?')->addButtons($buttons);
        $this->ask($question, function(Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->bank2 = $answer->getValue();
                $result = $this->fireflyService->sendTransaction(array(
                    'transactions' => array([
                        'type' => 'transfer',
                        'date' => Carbon::now(),
                        'amount' => $this->amount,
                        'description' => $this->description,
                        'source_id' => $this->bank1,
                        'destination_id' => $this->bank2
                    ])
                ));
                $this->say('Принято! Номер трансакции ' . $result);
            } else {
                $this->repeat();
            }
        });
    }
}
