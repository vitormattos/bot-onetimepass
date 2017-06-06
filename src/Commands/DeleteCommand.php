<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Helpers\Emojify;
/**
 * Class DeleteCommand.
 */
class DeleteCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'delete';

    /**
     * @var string Command Description
     */
    protected $description = 'Delete entry';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $telegram_id = $this->update->getMessage()->getFrom()->getId();

        $reply_markup = ListCommand::getListReplyMarkupKeyboard($telegram_id, true);
        if ($reply_markup->get('inline_keyboard')) {
            $this->telegram->sendMessage([
                'chat_id' => $telegram_id,
                'text' => 'Touch into '.Emojify::text(':no_entry:').' to delete',
                'reply_markup' => $reply_markup
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $telegram_id,
                'text' => 'No entry to delete'
            ]);
        }
   }
}