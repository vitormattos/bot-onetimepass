<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Aura\SqlQuery\QueryFactory;
use Telegram\Bot\Keyboard\Keyboard;
/**
 * Class LoginCommand.
 */
class ListCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'list';

    /**
     * @var string Command Description
     */
    protected $description = 'List totp';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $message = $this->update->getMessage();
        $telegram_id = $message->getFrom()->getId();

        $db = \Base\DB::getInstance();
        $sth = $db->prepare(
            'SELECT original, service, label, secret FROM keys WHERE telegram_id = :telegram_id ORDER BY service, label');
        $sth->execute([
            'telegram_id' =>$telegram_id
        ]);
        
        $reply_markup = Keyboard::make();
        $reply_markup->inline();
        $maxInColumn = 1;
        $buttons = [];
        while ($row = $sth->fetch()) {
            $buttons[] = Keyboard::inlineButton([
                'text' => $row['service'].
                    ($row['label']
                        ?"\n".$row['label']
                        :''
                    ),
                'callback_data' => '/get '.$row['service'].':'.$row['label']
            ]);
            if(count($buttons) == $maxInColumn) {
                call_user_func_array([$reply_markup, 'row'], $buttons);
                $buttons = [];
            }
        }
        if ($buttons && count($buttons) < $maxInColumn) {
            call_user_func_array([$reply_markup, 'row'], $buttons);
        }
        
        $this->telegram->sendMessage([
            'chat_id' => $telegram_id,
            'text' => 'List of totp',
            'reply_markup' => $reply_markup
        ]);
   }
}