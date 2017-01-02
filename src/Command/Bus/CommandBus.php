<?php
/**
 * Created by PhpStorm.
 * User: Bartosz Bartniczak <kontakt@bartoszbartniczak.pl>
 */

namespace BartoszBartniczak\CQRS\Command\Bus;


use BartoszBartniczak\CQRS\Command\Command;
use BartoszBartniczak\CQRS\Command\CommandList;
use BartoszBartniczak\CQRS\Command\Handler\CommandHandler;
use BartoszBartniczak\CQRS\Command\Handler\Exception as HandlerException;
use BartoszBartniczak\CQRS\Command\Query;

abstract class CommandBus
{
    /**
     * @var array
     */
    private $commandHandlers;

    /**
     * @var mixed
     */
    private $output;

    /**
     * CommandBus constructor.
     */
    public function __construct()
    {
        $this->commandHandlers = [];
        $this->clearOutput();
    }

    /**
     * @return void
     */
    protected function clearOutput()
    {
        $this->output = null;
    }

    /**
     * @param string $commandClassName
     * @param CommandHandler $commandHandler
     */
    public function registerHandler(string $commandClassName, CommandHandler $commandHandler)
    {
        $this->commandHandlers[$commandClassName] = $commandHandler;
    }

    /**
     * @param Command $command
     * @throws CannotHandleTheCommandException
     * @throws CannotFindHandlerException
     * @return mixed
     */
    public function handle(Command $command)
    {
        if ($command instanceof Query) {
            return $this->handleQuery($command);
        } else {
            $this->handleCommand($command);
        }
    }

    /**
     * @param Command $command
     * @throws CannotHandleTheCommandException
     * @throws CannotFindHandlerException
     */
    protected function handleCommand(Command $command)
    {
        $handler = $this->findHandler($command);
        $data = $this->tryToHandleCommand($command, $handler);

        $this->saveDataInRepository($data);

        $this->passNextCommandsToTheBus($handler->getNextCommands());
    }

    /**
     * @param Query $query
     * @throws CannotHandleTheCommandException
     * @throws CannotFindHandlerException
     * @return mixed
     */
    protected function handleQuery(Query $query)
    {
        $this->clearOutput();
        $handler = $this->findHandler($query);
        $eventAggregate = $this->tryToHandleCommand($query, $handler);

        $this->saveOutput($eventAggregate);

        return $this->output;
    }

    /**
     * @param Command $command
     * @return CommandHandler
     * @throws CannotFindHandlerException
     */
    protected function findHandler(Command $command): CommandHandler
    {
        $className = get_class($command);

        if (isset($this->commandHandlers[$className]) && $this->commandHandlers[$className] instanceof CommandHandler) {
            return $this->commandHandlers[$className];
        } else {
            throw new CannotFindHandlerException(sprintf("Cannot find handler for command: '%s'.", get_class($command)));
        }
    }

    /**
     * @param $data
     */
    protected function saveOutput($data)
    {
        $this->output = $data;
    }

    /**
     * @param CommandList $commandList
     */
    protected function passNextCommandsToTheBus(CommandList $commandList)
    {
        if ($commandList->isNotEmpty()) {
            foreach ($commandList as $nextCommand)
                $this->handle($nextCommand);
        }
    }

    /**
     * @param $data
     */
    abstract protected function saveDataInRepository($data);

    /**
     * @param CommandHandler $handler
     * @throws CannotHandleTheCommandException
     */
    abstract protected function handleHandlerException(CommandHandler $handler);
    /**
     * @param Command $command
     * @param CommandHandler $handler
     * @return mixed
     * @throws CannotHandleTheCommandException
     */
    private function tryToHandleCommand(Command $command, CommandHandler $handler)
    {
        try {
            $eventAggregate = $handler->handle($command);
            return $eventAggregate;
        } catch (HandlerException $handlerException) {
            $this->handleHandlerException($handler);
            throw new CannotHandleTheCommandException(sprintf("Command '%s' cannot be handled.", get_class($command)), null, $handlerException);
        }
    }


}