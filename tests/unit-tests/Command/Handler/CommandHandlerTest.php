<?php
/**
 * Created by PhpStorm.
 * User: Bartosz Bartniczak <kontakt@bartoszbartniczak.pl>
 */

namespace BartoszBartniczak\CQRS\Command\Handler;


use BartoszBartniczak\CQRS\Command\Command;
use BartoszBartniczak\CQRS\Command\CommandList;
use BartoszBartniczak\CQRS\Event\Event;
use BartoszBartniczak\CQRS\Event\EventStream;
use BartoszBartniczak\CQRS\Event\Id;
use BartoszBartniczak\CQRS\UUID\Generator;

/**
 * Class CommandHandlerMock
 * @package Shop\Basket\Command\Handler
 */
class CommandHandlerMock extends CommandHandler
{

    /**
     * @inheritDoc
     */
    public function handle(Command $command)
    {
    }

    public function addNextCommand(Command $command)
    {
        parent::addNextCommand($command);
    }

}


class CommandHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Handler\CommandHandler::__construct
     * @covers \BartoszBartniczak\CQRS\Command\Handler\CommandHandler::getNextCommands
     */
    public function testConstructor()
    {

        $commandHandler = $this->getMockBuilder(CommandHandler::class)
            ->getMockForAbstractClass();
        /* @var $commandHandler CommandHandler */

        $this->assertInstanceOf(CommandList::class, $commandHandler->getNextCommands());
        $this->assertEquals(0, $commandHandler->getNextCommands()->count());

    }

    /**
     * @covers \BartoszBartniczak\CQRS\Command\Handler\CommandHandler::addNextCommand
     * @covers \BartoszBartniczak\CQRS\Command\Handler\CommandHandler::getNextCommands
     */
    public function testAddNextCommand()
    {
        $command = $this->getMockBuilder(Command::class)
            ->getMockForAbstractClass();
        /* @var $command Command */

        $commandHandlerMock = new CommandHandlerMock();
        $commandHandlerMock->addNextCommand($command);
        $commandHandlerMock->addNextCommand($command);
        $this->assertEquals(2, $commandHandlerMock->getNextCommands()->count());
    }

}
