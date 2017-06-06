<?php

namespace Commands;

use Telegram\Bot\Commands\Command;
use Base\UserMeta;
use Telegram\Bot\Helpers\Emojify;

/**
 * Class HelpCommand.
 */
class HelpCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'help';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['listcommands'];

    /**
     * @var string Command Description
     */
    protected $description = 'Help command, Get a list of commands';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $commands = $this->telegram->getCommands();
        $text = $this->getUpdate()->getMessage()->getText();
        $matches = $this->getCommandBus()->parseCommand($text);
        if (!array_key_exists($matches[1], $commands)) {
            return;
        }

        $telegram_id = $this->update->getMessage()->getFrom()->getId();
        $reply_markup= ListCommand::getListReplyMarkupKeyboard($telegram_id);
        if (!$reply_markup->get('inline_keyboard')) {
            unset($commands['delete'], $commands['list']);
        }

        $text = "To create new entry, you has 4 options:\n".
            Emojify::text(':one:')." - Use the command \importauthy to import a xml of app Authy `com.authy.storage.tokens.authenticator.xml`\n".
            Emojify::text(':two:')." - Upload directly to the bot a xml of app Authy `com.authy.storage.tokens.authenticator.xml`\n".
            Emojify::text(':three:')." - Upload directly to the bot a picture with qrcode containing a uri of totp service\n".
            Emojify::text(':four:')." - Use the command `\adduri <uri>` to input manualy a new entry\n\n";
        foreach ($commands as $name => $handler) {
            if (in_array($name, ['remaining', 'start', 'get'])) {
                continue;
            }
            $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(compact('text'));
    }
}
