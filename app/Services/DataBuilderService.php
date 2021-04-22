<?php

namespace App\Services;


use BotMan\Drivers\Telegram\TelegramDriver;

class DataBuilderService
{
    public function generateMessage(array $submissionData): string
    {
        $mauticData = $submissionData['results'];
        $msg = '<b>Новый подписчик на сайт itvolga!</b>';
        if (isset($data['mautic.form_on_submit']['timestamp'])) {
            $msg .= PHP_EOL . 'Date' . ': ' . $data['mautic.form_on_submit']['timestamp'];
        } elseif (isset($data['mautic.form_on_submit'][0]['timestamp'])) {
            $msg .= PHP_EOL . 'Date' . ': ' . $data['mautic.form_on_submit'][0]['timestamp'];
        }
        $msg .= PHP_EOL . 'Email' . ': ' . $mauticData['email'];
        if (isset($mauticData['phone'])) {
            $msg .= PHP_EOL . 'Phone' . ': ' . $mauticData['phone'];
        }
        if (isset($mauticData['f_name'])) {
            $msg .= PHP_EOL . 'Name' . ': ' . $mauticData['f_name'];
        }
        if (isset($mauticData['subject'])) {
            $msg .= PHP_EOL . 'Subject' . ': ' . $mauticData['subject'];
        }
        if (isset($mauticData['comment'])) {
            $msg .= PHP_EOL . 'Subject' . ': ' . $mauticData['comment'];
        }
        if (isset($mauticData['ip'])) {
            $msg .= PHP_EOL . 'IP address' . ': ' . $mauticData['ip'];
        }
        if (isset($mauticData['country'])) {
            $msg .= PHP_EOL . 'Country' . ': ' . $mauticData['country'];
        }
        if (isset($mauticData['zipcode'])) {
            $msg .= PHP_EOL . 'Zipcode' . ': ' . $mauticData['zipcode'];
        }
        if (isset($mauticData['referer'])) {
            $msg .= PHP_EOL . 'Referer' . ': ' . $mauticData['referer'];
        }
        return $msg;
    }

    public function sendMessage(string $message): void
    {
        $botMan = app('botman');
        $users = config('services.telegram.allowed');
        foreach ($users as $user) {
            $botMan->say($message, $user, TelegramDriver::class, ['parse_mode' => 'HTML']);
        }
    }
}
