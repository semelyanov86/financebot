<?php

namespace App\Http\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Received;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Closure;
use Illuminate\Support\Facades\Log;

class ReceivedMiddleware implements Received
{

    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $user = $message->getSender();
        if (in_array($user, config('services.telegram.allowed'))) {
            return $next($message);
        } else {
            Log::alert('Denied user from bot with id: ' . $user);
        }
    }
}
