<?php
/**
 * Created by PhpStorm.
 * User: Bartosz Bartniczak <kontakt@bartoszbartniczak.pl>
 */

namespace BartoszBartniczak\CQRS\Command\Bus;


use BartoszBartniczak\CQRS\Command\Command;
use BartoszBartniczak\CQRS\Command\Handler\CommandHandler;

interface CommandBus
{
    public function registerHandler(string $commandClassName, CommandHandler $commandHandler);

    public function handle(Command $command);

}