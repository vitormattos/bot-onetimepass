<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Base\UserMeta;
/**
 * Class StartCommand.
 */
class StartCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'start';

    /**
     * @var string Command Description
     */
    protected $description = 'Start bot usage';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();
        $this->replyWithMessage([
            'chat_id' => $message->getChat()->getId(),
            'text' => "Welcome!\nIn this bot you is identified by your Telegram ID:\n".$telegram_id
        ]);
        $this->triggerCommand('help', $this->update);
    }
}