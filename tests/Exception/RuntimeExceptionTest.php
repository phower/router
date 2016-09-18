<?php

namespace PhowerTest\Router\Exception;

class RuntimeExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function testExceptionImplementsRouterExceptionInterface()
    {
        $exception = new \Phower\Router\Exception\RuntimeException();
        $this->assertInstanceOf(\Phower\Router\Exception\RouterExceptionInterface::class, $exception);
    }
}
