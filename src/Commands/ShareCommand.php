<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\DB;
use Base\Meetup;
/**
 * Class ShareCommand.
 */
class ShareCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'share';

    /**
     * @var string Command Description
     */
    protected $description = 'Share your tokens with another person';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();

        $reply_markup = Keyboard::make([
            'force_reply' => true
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $this->update->getMessage()->getFrom()->getId(),
            'text' => 'Share the <code>@username</code> or contact of telegram account of person when you want share yours tokens.',
            'reply_markup' => $reply_markup,
            'parse_mode' => 'html'
        ]);
    }
}