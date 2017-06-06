<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\DB;
use Base\Meetup;
/**
 * Class AboutCommand.
 */
class AboutCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'about';

    /**
     * @var string Command Description
     */
    protected $description = 'About this bot';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();
        $this->replyWithMessage([
            'chat_id' => $message->getChat()->getId(),
            'text' =>
                "Just another bot to generate totp\n".
                "Source:\n".
                "https://github.com/vitormattos/bot-onetimepass",
        ]);
    }
}