<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Goutte\Client;
use Telegram\Bot\Keyboard\Keyboard;
/**
 * Class ImportAuthyCommand.
 */
class ImportAuthyCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'importauthy';

    /**
     * @var string Command Description
     */
    protected $description = 'Import Auty file';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();

        $reply_markup = Keyboard::make([
            'force_reply' => true
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $telegram_id,
            'text' => 'Upload the xml file of authy app and send to import',
            'reply_markup' => $reply_markup
        ]);
        
   }
}