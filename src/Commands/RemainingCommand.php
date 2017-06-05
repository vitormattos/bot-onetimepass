<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Goutte\Client;
use Aura\SqlQuery\QueryFactory;
use Telegram\Bot\Keyboard\Keyboard;
use OTPHP\TOTP;
/**
 * Class LoginCommand.
 */
class RemainingCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'remaining';

    /**
     * @var string Command Description
     */
    protected $description = 'Remaining time';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();
        
        $totp = new TOTP();
        $timestamp = time();
        $float = $timestamp  / $totp->getPeriod();
        $percent = (int)(($float -(int)$float)*100);
        $remainingSeconds =  $totp->getPeriod() - round(($totp->getPeriod()*$percent)/100);
        
        $reply_markup = Keyboard::make();
        $reply_markup->inline();
        $reply_markup->row(
            Keyboard::inlineButton([
                'text' => (string)$remainingSeconds,
                'callback_data' => '/remaining'
            ])
        );
        $this->telegram->sendMessage([
            'chat_id' => $telegram_id,
            'text' => $totp->at($timestamp),
            'reply_markup' => $reply_markup
        ]);
   }
}