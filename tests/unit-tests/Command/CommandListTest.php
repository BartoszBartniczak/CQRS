<?php
/**
 * Created by PhpStorm.
 * User: Bartosz Bartniczak <kontakt@bartoszbartniczak.pl>
 */

namespace BartoszBartniczak\CQRS\Command;


use BartoszBartniczak\ArrayObject\ArrayOfObjects;

class CommandListTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \BartoszBartniczak\CQRS\Command\CommandList::__construct
     */
    public function testConstructor()
    {
        $commandList = new CommandList();
        $this->assertInstanceOf(ArrayOfObjects::class, $commandList);
        $this->assertEquals(Command::class, $commandList->getClassName());
    }

}
