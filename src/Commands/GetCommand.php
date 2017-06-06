<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use OTPHP\TOTP;
/**
 * Class GetCommand.
 */
class GetCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'get';

    /**
     * @var string Command Description
     */
    protected $description = 'Return the totp token';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        if (!preg_match('/^\/get (?<source>.*)/', $arguments, $source)) {
            return;
        }
        $source = $source['source'];
        $message = $this->update->getMessage();
        if(!$message) {
            $message = $this->update->getCallbackQuery();
        }
        $telegram_id = $message->getFrom()->getId();
        
        $token = self::getToken($telegram_id, $source);
        
        $reply_markup = Keyboard::make();
        $reply_markup->inline();
        $reply_markup->row(
            Keyboard::inlineButton([
                'text' => 'Remaining: '.$token['remaining'],
                'callback_data' => '/remaining '.$source
            ])
        );
        if($token['service']) {
            $this->telegram->sendMessage([
                'chat_id' => $telegram_id,
                'text' => $token['service'].
                    ($token['label']
                        ?':'.$token['service']
                        :''
                    ),
            ]);
        }
        $this->telegram->sendMessage([
            'chat_id' => $telegram_id,
            'text' => $token['token'],
            'reply_markup' => $reply_markup
        ]);
   }
   
   public static function getToken($telegram_id, $secret)
   {
       if ($secret) {
           $db = \Base\DB::getInstance();
           $sth = $db->prepare(
               'SELECT service, label, secret '.
               ' FROM keys '.
               'WHERE telegram_id = :telegram_id '.
               'AND MD5(secret) = :secret '.
               'AND deleted = false'
           );

           $sth->execute([
               'telegram_id' =>$telegram_id,
               'secret' => $secret
           ]);
           $row = $sth->fetch();
           $totp = new TOTP(null, $row['secret']);
       } else {
           $totp = new TOTP();
       }
       if(!$row) {
           $row['service'] =  null;
           $row['label'] =  null;
       }

       $timestamp = time();
       $float = $timestamp  / $totp->getPeriod();
       $percent = (int)(($float -(int)$float)*100);
       $remainingSeconds =  $totp->getPeriod() - round(($totp->getPeriod()*$percent)/100);
       return [
           'remaining' => $remainingSeconds,
           'token' => $totp->at($timestamp),
           'service' => $row['service'],
           'label' => $row['label']
       ];
   }
}