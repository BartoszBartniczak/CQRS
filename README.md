BartoszBartniczak / CQRS [![Build Status](https://travis-ci.org/BartoszBartniczak/CQRS.svg?branch=master)](https://travis-ci.org/BartoszBartniczak/CQRS)
====
Command Query Responsibility Segregation in PHP
----------------------------------------

### Table of contents
[TOC]

### Preface

If you do not know what Command Query Responsibility Segregation is, you shoud read a very good article written by Martin Fowler. To read the article click [here](http://martinfowler.com/bliki/CQRS.html).

This library is an implementation of this pattern in PHP.

Below are described main components of the library.

### Components

#### Command

Command usualy represents some Domain logic. It can contain data validation, data procesing, etc.. The result of the Command usualy is saved in database. E.g. RegisterUser, SendEmail, etc..
You should treat the Command as a "data holder". In the constructor parameters, you should pass all the data required to handle the Command.

#### Query

Query is a special type of Command. It looks for data in the Repository, and returs it as a result. E.g. FindUser, FindProduct, etc..

#### Command Handler

It handles the Command. In this object you shoud use the parameters passed in the Command constructor. In this object you can validate, change and process the data.
CommandHandlers can be registered in CommandBus. See section [#how-to-register-commandhandler].

###### Example

```php
<?php

use BartoszBartniczak\CQRS\Command\Command;
use BartoszBartniczak\CQRS\Command\Handler\CommandHandler;

interface EmailSenderService
{

    public function sendEmail(string $receiver, string $subject, string $body);

}

class FakeEmailSenderService implements EmailSenderService
{
    public function sendEmail(string $receiver, string $subject, string $body)
    {
        // TODO: Here you should send email!
    }

}

class SendEmailCommand implements Command
{

    /**
     * @var string
     */
    private $receiver;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $body;

    /**
     * @var EmailSenderService
     */
    private $emailSenderService;

    /**
     * SendEmail constructor.
     * @param string $receiver
     * @param string $subject
     * @param string $body
     * @param EmailSenderService $emailSenderService
     */
    public function __construct(string $receiver, string $subject, string $body, EmailSenderService $emailSenderService)
    {
        $this->receiver = $receiver;
        $this->subject = $subject;
        $this->body = $body;
        $this->emailSenderService = $emailSenderService;
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return EmailSenderService
     */
    public function getEmailSenderService(): EmailSenderService
    {
        return $this->emailSenderService;
    }

}

class SendEmailHandler extends CommandHandler
{
    public function handle(Command $command)
    {
        //TODO: Add some validation here

        /* @var $command SendEmailCommand */
        $command->getEmailSenderService()->sendEmail(
            $command->getReceiver(),
            $command->getSubject(),
            $command->getBody()
        );
    }
}

$fakeEmailSenderService = new FakeEmailSenderService();
$sendEmailCommand = new SendEmailCommand('client@emial.com', 'Very important message!', 'Here is the body of the message.', $fakeEmailSenderService);

$sendEmailHandler = new SendEmailHandler();
$sendEmailHandler->handle($sendEmailCommand);
```

#### CommandBus

CommandBus can receive Commands and execute them using CommandHandlers. To do that you need to register CommandHandler.

##### How to register CommandHandler?

```php
use BartoszBartniczak\CQRS\Command\Bus\CommandBus;

class SimpleCommandBus extends CommandBus{

    protected function handleHandlerException(CommandHandler $handler)
    {
        // TODO: Here you can react on HandlerException and then you should throw the CannotExecuteTheCommandException
    }

    protected function saveDataInRepository($data)
    {
        // TODO: Here you shoud persist the data
    }

}

$simpleCommandBus = new SimpleCommandBus();
$simpleCommandBus->registerHandler(SendEmailCommand::class, $sendEmailHandler);
```

Now you can execute the Command using CommandBus:

```php
$simpleCommandBus->execute($sendEmailCommand);
```

##### How command is executed?

After you pass the Command for execution, CommandBus is looking for proper CommandHandler to handle the Command.
If the CommandHandler return data, it may be saved in the Repository.
CommandHandler may pass another Commands to the CommandBus for further execution.

![CommandExecution.svg](https://cdn.rawgit.com/BartoszBartniczak/CQRS/master/docs/CommandExecution.svg)

#### How query is executed?

You can pass Query for execution to the CommandBus. CommandBus looks for CommandHandler. In the handle() method, you can find data in Repository, and ten return as a Result. CommandBus knows that, in result of execution of Query, you return some results, so it saves it in the output. The output is returned as the result of the execute() method.

![QueryExecution.svg](https://cdn.rawgit.com/BartoszBartniczak/CQRS/master/docs/QueryExecution.svg)

##### Example

```php
use BartoszBartniczak\CQRS\Command\Handler\CannotHandleTheCommandException;
use BartoszBartniczak\CQRS\Command\Query;

interface ProductRepository
{

    /**
     * @param ProductId $productId
     * @return Product
     * @throws CannotFindProductException
     */
    public function findProductById(ProductId $productId);

}

class FindProductInRepositoryCommand implements Query
{

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductId
     */
    private $productId;

    /**
     * FindProductInRepositoryCommand constructor.
     * @param ProductId $productId
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductId $productId, ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        $this->productId = $productId;
    }


    /**
     * @return ProductRepository
     */
    public function getProductRepository(): ProductRepository
    {
        return $this->productRepository;
    }

    /**
     * @return ProductId
     */
    public function getProductId(): ProductId
    {
        return $this->productId;
    }

}

class FindProductInRepositoryHandler extends CommandHandler
{
    public function handle(Command $command): Product
    {
        /* @var $command FindProductInRepositoryCommand */
        try {
            $product = $command->getProductRepository()->findProductById(
                $command->getProductId()
            );
            return $product;
        } catch (CannotFindProductException $cannotFindProductException) {
            //TODO: Do some buisiness logic in here. E.g. Save the wrong phrase/id for further computing.
            throw new CannotHandleTheCommandException("Product cannot be found.", null, $cannotFindProductException);
        }
    }
}
```

### Tests

#### Unit tests

To run unit tests execute command:

```bash
php vendor/phpunit/phpunit/phpunit --configuration tests/unit-tests/configuration.xml
```