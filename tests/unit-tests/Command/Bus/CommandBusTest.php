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


class CommandBusTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handle
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handleCommand
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::findHandler
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::__construct
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::registerHandler
     */
    public function testHandle()
    {

        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->getMockForAbstractClass();
        /* @var $commandHandler CommandHandler */

        $command = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->getMockForAbstractClass();
        /* @var $commandBus CommandBus */
        $commandBus->registerHandler('CommandMock', $commandHandler);
        $commandBus->handle($command);
    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::findHandler
     */
    public function testFindHandlerThrowsCannotFindHandlerException()
    {
        $this->expectException(CannotFindHandlerException::class);
        $this->expectExceptionMessage("Cannot find handler for command: 'CommandMock'.");

        $command = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->getMockForAbstractClass();
        /* @var $commandBus CommandBus */
        $commandBus->handle($command);
    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::saveOutput
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handle
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handleQuery
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::tryToHandleCommand
     */
    public function testOutputForQuery()
    {
        $dateTime = new \DateTime();

        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->setConstructorArgs([
            ])
            ->setMethods([
                'handle'
            ])
            ->getMockForAbstractClass();
        $commandHandler->method('handle')
            ->willReturn($dateTime);
        /* @var $commandHandler CommandHandler */

        $command = $this->getMockBuilder(Query::class)
            ->setMockClassName('QueryMock')
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->getMockForAbstractClass();
        /* @var $commandBus CommandBus */
        $commandBus->registerHandler('QueryMock', $commandHandler);
        $output = $commandBus->handle($command);

        $this->assertSame($dateTime, $output);
    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handle
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handleCommand
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::saveDataInRepository
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::clearOutput
     */
    public function testDataIsSavedInRepository()
    {
        $dateTime = new \DateTime();

        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->setMethods([
                'handle',
            ])
            ->getMockForAbstractClass();
        $commandHandler->method('handle')
            ->willReturn($dateTime);
        /* @var $commandHandler CommandHandler */

        $command = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->setMethods([
                'saveDataInRepository'
            ])
            ->getMockForAbstractClass();
        $commandBus->expects($this->once())
            ->method('saveDataInRepository')
            ->with($dateTime);

        /* @var $commandBus CommandBus */
        $commandBus->registerHandler('CommandMock', $commandHandler);
        $commandBus->handle($command);
    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::handleHandlerException
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::tryToHandleCommand
     */
    public function testHandleError()
    {
        $this->expectException(CannotHandleTheCommandException::class);
        $this->expectExceptionMessage("Command 'CommandMock' cannot be handled.");

        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->setMethods([
                'handle'
            ])
            ->getMock();
        $commandHandler->method('handle')
            ->willThrowException(new HandlerException());
        /* @var $commandHandler CommandHandler */

        $command = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->getMockForAbstractClass();
        /* @var $commandBus CommandBus */
        $commandBus->registerHandler('CommandMock', $commandHandler);
        $commandBus->handle($command);

    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Bus\CommandBus::passNextCommandsToTheBus
     */
    public function testPassNextCommandsToTheBus()
    {
        $command1 = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command1 Command */

        $command2 = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command2 Command */

        $command3 = $this->getMockBuilder(Command::class)
            ->setMockClassName('CommandMock')
            ->getMockForAbstractClass();
        /* @var $command3 Command */

        $commandList = new CommandList();
        $commandList[] = $command2;
        $commandList[] = $command3;


        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->setMethods([
                'handle',
                'getNextCommands'
            ])
            ->getMock();
        $commandHandler->expects($this->at(1))
            ->method('getNextCommands')
            ->willReturn($commandList);
        /* @var $commandHandler CommandHandler */

        $commandBus = $this->getMockBuilder(CommandBus::class)
            ->setMethods([
                'findHandler'
            ])
            ->getMockForAbstractClass();
        $commandBus->expects($this->exactly(3))
            ->method('findHandler')
            ->withConsecutive(
                $command1,
                $command2,
                $command3
            )
            ->willReturn($commandHandler);
        /* @var $commandBus CommandBus */

        $commandBus->registerHandler('CommandMock', $commandHandler);
        $commandBus->handle($command1);

    }
}
