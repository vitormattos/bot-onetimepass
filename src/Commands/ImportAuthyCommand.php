<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Goutte\Client;
use Telegram\Bot\Keyboard\Keyboard;
use Symfony\Component\Config\Util\XmlUtils;
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
    /**
     * @param string $source
     * @param int $telegram_id
     * @return string
     */
    public static function importDocument(string $source, int $telegram_id)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($source);
        $json = $dom->getElementsByTagName('string')->item(0)->nodeValue;
        $json = json_decode($json);
        if (!$json) {
            return 'Invalid file content. Please, send a xml from Authy';
        }
        
        $db = \Base\DB::getInstance();
        $values['telegram_id'] = $telegram_id;
        $imported = [];
        foreach ($json as $source) {
            if (strpos(':', $source->name)) {
                $tmp = explode(':', $source->name);
                $service = $tmp[0];
                $label = $tmp[1];
            } else {
                if ($source->accountType != 'authenticator') {
                    $service = $source->accountType;
                } elseif ($source->originalName != 'screenconnect') {
                    $service = $source->originalName;
                } else {
                    $service = $source->name;
                }
                $label = $source->name;
            }
            $values['service'] = trim($service);
            $values['label'] = trim($label);
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
                    'secret' => $values['secret']
                ]);
                if ($ok) {
                    $imported[] = $values['service'];
                }
            } else {
                $imported[] = $values['service'];
            }
        }
        if ($imported) {
            return "Imported:\n".implode(", ", $imported);
        }
        return 'No data imported';
    }
}