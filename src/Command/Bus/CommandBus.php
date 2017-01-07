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
     * @throws CannotExecuteTheCommandException
     * @throws CannotFindHandlerException
     * @return mixed
     */
    public function execute(Command $command)
    {
        if ($command instanceof Query) {
            return $this->executeQuery($command);
        } else {
            $this->executeCommand($command);
        }
    }

    /**
     * @param Query $query
     * @throws CannotExecuteTheCommandException
     * @throws CannotFindHandlerException
     * @return mixed
     */
    protected function executeQuery(Query $query)
    {
        $this->clearOutput();
        $handler = $this->findHandler($query);
        $data = $this->tryToHandleCommand($query, $handler);

        $this->saveOutput($data);

        return $this->getOutput();
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
     * @param Command $command
     * @param CommandHandler $handler
     * @return mixed
     * @throws CannotExecuteTheCommandException
     */
    protected function tryToHandleCommand(Command $command, CommandHandler $handler)
    {
        try {
            $data = $handler->handle($command);
            return $data;
        } catch (HandlerException $handlerException) {
            $this->handleHandlerException($handler);
            throw new CannotExecuteTheCommandException(sprintf("Command '%s' cannot be handled.", get_class($command)), null, $handlerException);
        }
    }

    /**
     * @param CommandHandler $handler
     * @throws CannotExecuteTheCommandException
     */
    abstract protected function handleHandlerException(CommandHandler $handler);

    /**
     * @param $data
     */
    protected function saveOutput($data)
    {
        $this->output = $data;
    }

    /**
     * @return mixed
     */
    protected function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Command $command
     * @throws CannotExecuteTheCommandException
     * @throws CannotFindHandlerException
     */
    protected function executeCommand(Command $command)
    {
        $handler = $this->findHandler($command);
        $data = $this->tryToHandleCommand($command, $handler);

        $this->saveDataInRepository($data);

        $this->executeNextCommands($handler->getNextCommands());
    }

    /**
     * @param $data
     */
    abstract protected function saveDataInRepository($data);

    /**
     * @param CommandList $commandList
     */
    protected function executeNextCommands(CommandList $commandList)
    {
        if ($commandList->isNotEmpty()) {
            foreach ($commandList as $nextCommand)
                $this->execute($nextCommand);
        }
    }


}