<?php
/**
 * Created by PhpStorm.
 * User: Bartosz Bartniczak <kontakt@bartoszbartniczak.pl>
 */

namespace BartoszBartniczak\CQRS\Command\Handler;


use BartoszBartniczak\CQRS\Command\Command;
use BartoszBartniczak\CQRS\Command\CommandList;

abstract class CommandHandler
{

    /**
     * @var CommandList;
     */
    private $nextCommands;

    /**
     * CommandHandler constructor.
     */
    public function __construct()
    {
        $this->nextCommands = new CommandList();
    }

    /**
     * @param Command $command
     */
    abstract public function handle(Command $command);

    /**
     * @return CommandList
     */
    public function getNextCommands(): CommandList
    {
        return $this->nextCommands;
    }

    /**
     * @param Command $command
     */
    protected function addNextCommand(Command $command)
    {
        $this->nextCommands[] = $command;
    }

}