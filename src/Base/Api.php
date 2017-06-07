<?php
namespace Base;

use Telegram\Bot\Keyboard\Keyboard;
use Symfony\Component\Config\Util\XmlUtils;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Commands\ImportAuthyCommand;
use Commands\ListCommand;

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
                            'SELECT service, label, secret '.
                            'FROM keys '.
                            'WHERE telegram_id = :telegram_id '.
                            'AND MD5(secret) = :secret'
                            );
                        $sth->execute([
                            'telegram_id' => $telegram_id,
                            'secret' => $matches['secret']
                        ]);
                        if ($row = $sth->fetch()) {
                            $sth = $db->prepare(
                                'UPDATE keys '.
                                'SET deleted = true '.
                                'WHERE telegram_id = :telegram_id '.
                                'AND secret = :secret'
                                );
                            $sth->execute([
                                'telegram_id' => $telegram_id,
                                'secret' => $row['secret']
                            ]);
                            $text = $row['service'].
                                ($row['label']
                                    ?':'.$row['label']
                                    :''
                                ).
                                ' deleted with sucess';
                        } else {
                            $text = 'Entry not found';
                        }
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
        $chat_id = $message->getChat()->getId();
        if($message->has('photo')) {
            $file_id = $message->getPhoto()->last()['file_id'];
        } elseif($message->has('document')) {
            $file_id = $message->getDocument()->getFileId();
        } else {
            return;
        }

        $fileInfo = $this->getFile([
            'file_id' => $file_id
        ]);
        $fileContent = file_get_contents(
            'https://api.telegram.org/file/bot'.$this->getAccessToken().'/'.
            $fileInfo->getFilePath()
        );
        
        $prefix = substr($fileContent,0,3);
        if ($message->has('photo') || substr($fileContent,0,3)=="\xff\xd8\xff") {
            $qrcode = new \QrReader($fileContent, \QrReader::SOURCE_TYPE_BLOB);
            $text = $qrcode->text();
            $this->getCommandBus()->execute('adduri', $text, $update);
        } elseif (substr($fileContent,0,5)=='<?xml') {
            $this->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Wait the end of import process'
            ]);
            try {
                $this->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => ImportAuthyCommand::importDocument(
                        $fileContent,
                        $message->getFrom()->getId()
                    )
                ]);
                return;
            } catch (\Exception | TelegramSDKException $e) {
                $this->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'An error has occurred, please try again'
                ]);
                $this->getCommandBus()->execute('importauthy', [], $update);
                return;
            }
        } else {
            $this->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Invalid file format, accept only picture, jpg or xml file.'
            ]);
        }
    }
    
    public function processMessage(\Telegram\Bot\Objects\Update $update)
    {
        $message = $update->getMessage();

        if ($message !== null && $message->has('text')) {
            $db = \Base\DB::getInstance();
            $sth = $db->prepare(
                'SELECT original, service, label, secret '.
                'FROM keys '.
                'WHERE telegram_id = :telegram_id '.
                'AND deleted = false '.
                'AND (lower(service) LIKE :text OR lower(label) LIKE :text)'.
                'ORDER BY service, label'
                );
            $telegram_id = $update->getMessage()->getFrom()->getId();
            $ok = $sth->execute([
                'telegram_id' => $telegram_id,
                'text' => '%'.strtolower($message->getText()).'%'
            ]);
            if (!$ok) return;
            $reply_markup = ListCommand::generateButtonsFromSth($sth);
            if ($reply_markup->get('inline_keyboard')) {
                $this->sendMessage([
                    'chat_id' => $telegram_id,
                    'text' => 'List of totp to '.
                        '<strong>'.$message->getText().'</strong>',
                    'reply_markup' => $reply_markup,
                    'parse_mode' => 'html'
                ]);
            }
        }
    }
    
    public function processContact(\Telegram\Bot\Objects\Update $update)
    {
        $message = $update->getMessage();
        if(!$message->has('contact')) {
            return;
        }
        $contact = $message->getContact();
    }
}
