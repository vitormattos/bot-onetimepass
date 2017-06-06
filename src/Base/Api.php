<?php
namespace Base;

use Telegram\Bot\Keyboard\Keyboard;
use Symfony\Component\Config\Util\XmlUtils;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Api extends \Telegram\Bot\Api
{
    public function processCallbackQuery(\Telegram\Bot\Objects\Update $update)
    {
        if(!$update->has('callback_query')) {
            return;
        }
        $params = [];
        $callbackQuery = $update->getCallbackQuery();
        if($query = $callbackQuery->getData()) {
            switch ($query) {
                case (preg_match('/^\/get (?<source>.*)/', $query, $matches) ? true : false):
                    $this->getCommandBus()->execute('get', $query, $update);
                    break;
                case (preg_match('/^\/delete[ ]?(?<secret>.*)?/', $query, $matches) ? true : false):
                    $telegram_id = $callbackQuery->getFrom()->getId();
                    if(isset($matches['secret'])) {
                        $db = \Base\DB::getInstance();
                        $sth = $db->prepare(
                            'UPDATE keys '.
                            'SET deleted = true '.
                            'WHERE telegram_id = :telegram_id '.
                            'AND MD5(secret) = :secret'
                            );
                        $sth->execute([
                            'telegram_id' => $telegram_id,
                            'secret' => $matches['secret']
                        ]);
                        $info = $sth->errorInfo();
                        $text = $matches['source'].' deleted with sucess';
                    } else {
                        $text = 'Invalid data to delete';
                    }
                    $this->sendMessage([
                        'chat_id' => $telegram_id,
                        'text' => $text,
                    ]);
                    break;
                case (preg_match('/^\/remaining[ ]?(?<source>.*)?/', $query, $matches) ? true : false):
                    $telegram_id = $callbackQuery->getFrom()->getId();
                    $token = \Commands\GetCommand::getToken($telegram_id, $matches['source']);
                    $reply_markup = Keyboard::make();
                    $reply_markup->inline();
                    $reply_markup->row(
                        Keyboard::inlineButton([
                            'text' => 'Remaining: '.$token['remaining'],
                            'callback_data' => '/remaining '.$matches['source']
                        ])
                    );
                    $params = [
                        'chat_id' => $telegram_id,
                        'reply_markup' => $reply_markup
                    ];
                    if ($matches['source']) {
                        $this->editMessageText(
                            [
                                'message_id' => $callbackQuery->getMessage()->getMessageId(),
                                'text' => $token['token'],
                                'callback_query_id' => $callbackQuery->getId(),
                                'cache_time' => 0,
                            ] +  $params
                        );
                    } else {
                        $this->sendMessage([
                            'text' => 'Token unavaliable',
                        ] +  $params);
                    }
                    break;
            }
            $this->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId()
            ]);
        }
    }
    
    public function processDocument(\Telegram\Bot\Objects\Update $update) {
        $message = $update->getMessage();
        if(!$message->has('document')) {
            return;
        }
        if($message->has('reply_to_message')) {
            $text = $message->getReplyToMessage()->getText();
            $bot_id = $message->getReplyToMessage()->getFrom()->getId();
            
            if (getenv('BOT_USERNAME') != $message->getReplyToMessage()->getFrom()->getUsername()) {
                $this->sendMessage([
                    'chat_id' => $message->getChat()->getId(),
                    'text' => 'Only send authy file in response to next message:'
                ]);
                $this->getCommandBus()->execute('importauthy', [], $update);
                return;
            }
            switch ($text) {
                case (preg_match('/authy/', $text) ? true : false):
                    $file = $this->getFile([
                        'file_id' => $message->getDocument()->getFileId()
                    ]);
                    $url = 'https://api.telegram.org/file/bot'.$this->getAccessToken().'/'.$file->getFilePath();
                    $this->sendMessage([
                        'chat_id' => $message->getChat()->getId(),
                        'text' => 'Wait the end of import process'
                    ]);
                    try {
                        $document = XmlUtils::loadFile($url);
                        $json = $document->getElementsByTagName('string')->item(0)->nodeValue;
                        $json = json_decode($json);
                        
                        $db = \Base\DB::getInstance();
                        $telegram_id = $message->getFrom()->getId();
                        $values['telegram_id'] = $telegram_id;
                        $imported = [];
                        foreach ($json as $source) {
                            $tmp = explode(':', $source->originalName);
                            $values['service'] = $tmp[0];
                            $values['label'] = $tmp[1];
                            $values['secret'] = $source->decryptedSecret;
                            $sth = $db->prepare(
                                'INSERT INTO keys (telegram_id, service, label, secret) '.
                                'VALUES (:telegram_id, :service, :label, :secret);'
                            );

                            $ok = $sth->execute($values);
                            if (!$ok) {
                                $sth = $db->prepare(
                                    'UPDATE keys SET deleted = false'.
                                    ' WHERE telegram_id = :telegram_id AND secret = :secret;');
                                $ok = $sth->execute([
                                    'telegram_id' => $values['telegram_id'],
                                    'secret' => $value['secret']
                                ]);
                                if ($ok) {
                                    $imported[] = $values['service'];
                                }
                            } else {
                                $imported[] = $values['service'];
                            }
                        }
                        if ($imported) {
                            $text = "Imported:\n".implode(", ", $imported);
                        } else {
                            $text = 'No data imported';
                        }
                        $this->sendMessage([
                            'chat_id' => $message->getChat()->getId(),
                            'text' => $text
                        ]);
                        return;
                    } catch (\Exception | TelegramSDKException $e) {
                        $this->sendMessage([
                            'chat_id' => $message->getChat()->getId(),
                            'text' => 'An error has occurred, please try again'
                        ]);
                        $this->getCommandBus()->execute('importauthy', [], $update);
                        return;
                    }
                    break;
            }
        }
        $this->sendMessage([
            'chat_id' => $message->getChat()->getId(),
            'text' => 'Invalid authy file, try again'
        ]);
        $this->getCommandBus()->execute('importauthy', [], $update);
    }
}