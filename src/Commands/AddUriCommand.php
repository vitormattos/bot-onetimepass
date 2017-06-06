<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Aura\SqlQuery\QueryFactory;

/**
 * Class AddUriCommand.
 */
class AddUriCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'adduri';

    /**
     * @var string Command Description
     */
    protected $description = 'Add new totp by uri';

    /**
     * {@inheritdoc}
     */
    public function handle($uri)
    {
        $values['telegram_id'] = $this->update->getMessage()->getFrom()->getId();
        $values['original'] = $uri;
        $uri = parse_url($uri);
        $values['service'] = explode(':', trim($uri['path'], '/'));
        $values['label'] = $values['service'][1];
        $values['service'] = $values['service'][0];
        parse_str($uri['query'], $values['secret']);
        $values['secret'] = $values['secret'] = $values['secret']['secret'];
        
        $errors = [];
        foreach (['service', 'label', 'secret'] as $necessary) {
            if (!$values[$necessary]) {
                $errors[] = $necessary.' is necessary';
            }
        }
        if ($errors) {
            $this->replyWithMessage([
                'telegram_id' => $values['telegram_id'],
                'text' => implode(",\n", $errors).'.'
            ]);
            return;
        }

        $db = \Base\DB::getInstance();
        $sth = $db->prepare(
            'INSERT INTO keys (telegram_id, original, service, label, secret) '.
            'VALUES (:telegram_id, :original, :service, :label, :secret);');
        $ok = $sth->execute($values);
        if (!$ok) {
            $sth = $db->prepare(
                'UPDATE keys SET deleted = false'.
                ' WHERE telegram_id = :telegram_id AND secret = :secret;');
            $ok = $sth->execute([
                'telegram_id' => $values['telegram_id'],
                'secret' => $value['secret']
            ]);
        }
        if ($ok) {
            $this->replyWithMessage([
                'telegram_id' => $values['telegram_id'],
                'text' => 'Uri to '.$values['service'].' added'
            ]);
        } else {
            $this->replyWithMessage([
                'telegram_id' => $values['telegram_id'],
                'text' => 'Fail to add'
            ]);
        }
    }
}
