<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Aura\SqlQuery\QueryFactory;

/**
 * Class HelpCommand.
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
        $message = $this->update->getMessage();

        $values['telegram_id'] = $message->getFrom()->getId();
        $values['original'] = $uri;
        $uri = parse_url($uri);
        $values['service'] = explode(':', trim($uri['path'], '/'));
        $values['label'] = $values['service'][1];
        $values['service'] = $values['service'][0];
        parse_str($uri['query'], $values['secret']);
        $values['secret'] = $values['secret'] = $values['secret']['secret'];

        $db = \Base\DB::getInstance();
        $sth = $db->prepare(
            'INSERT INTO keys (telegram_id, original, service, label, secret) '.
            'VALUES (:telegram_id, :original, :service, :label, :secret);');
        if ($sth->execute($values)) {
            $this->replyWithMessage([
                'telegram_id' => $values['telegram_id'],
                'text' => 'Uri to '.$values['service'].' added'
            ]);
        } else {
            $this->replyWithMessage([
                'telegram_id' => $values['telegram_id'],
                'text' => 'Invalid URI'
            ]);
        }
    }
}
