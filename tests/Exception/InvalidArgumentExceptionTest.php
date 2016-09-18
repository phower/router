<?php

namespace PhowerTest\Router\Exception;

class InvalidArgumentExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function testExceptionImplementsRouterExceptionInterface()
    {
        $exception = new \Phower\Router\Exception\InvalidArgumentException();
        $this->assertInstanceOf(\Phower\Router\Exception\RouterExceptionInterface::class, $exception);
    }
}
