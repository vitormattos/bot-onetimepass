<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Helpers\Emojify;
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
        $telegram_id = $this->update->getMessage()->getFrom()->getId();

        $reply_markup = self::getListReplyMarkupKeyboard($telegram_id);
        if ($reply_markup->get('inline_keyboard')) {
            $this->telegram->sendMessage([
                'chat_id' => $telegram_id,
                'text' => 'List of totp',
                'reply_markup' => $reply_markup
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $telegram_id,
                'text' => 'You don\'t has any entry, please, first import from Authy xml file or add one by one entry'
            ]);
        }
   }

   /**
    * @param int $telegram_id
    * @param bool $delete
    * @param int $maxInColumn
    * @return \Telegram\Bot\Keyboard\Keyboard
    */
   public static function getListReplyMarkupKeyboard(int $telegram_id, bool $delete = false, int $maxInColumn = 1)
   {
       $db = \Base\DB::getInstance();
       $sth = $db->prepare(
           'SELECT original, service, label, secret '.
           'FROM keys '.
           'WHERE telegram_id = :telegram_id AND deleted = false '.
           'ORDER BY service, label'
       );
       $sth->execute([
           'telegram_id' => $telegram_id
       ]);
       
       $reply_markup = Keyboard::make();
       $reply_markup->inline();
       $buttons = [];
       while ($row = $sth->fetch()) {
           $label = $row['label']
               ? "\n".$row['label']
               : '';
           if ($delete) {
               $buttons[] = Keyboard::inlineButton([
                   'text' => ($delete?Emojify::text(':no_entry:'):'').$row['service'].$label,
                   'callback_data' => '/delete '.md5($row['secret'])
               ]);
           } else {
               $buttons[] = Keyboard::inlineButton([
                   'text' => $row['service'].$label,
                   'callback_data' => '/get '.md5($row['secret'])
               ]);
           }
           if(count($buttons) == $maxInColumn) {
               $reply_markup->row(...$buttons);
               $buttons = [];
           }
       }
       if ($buttons && count($buttons) < $maxInColumn) {
           $reply_markup->row(...$buttons);
       }
       return $reply_markup;
   }
}