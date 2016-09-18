<?php

namespace PhowerTest\Router;

class RouteTest extends \PHPUnit_Framework_TestCase
{

    public function testRouteImplementsRouteInterface()
    {
        $route = $this->getMockBuilder(\Phower\Router\Route::class)
                        ->disableOriginalConstructor()->getMock();
        $this->assertInstanceOf(\Phower\Router\RouteInterface::class, $route);
    }

    /**
     * @dataProvider constructProvider
     */
    public function testConstructCanInstantiate($name, $definition, $constraints, $defaults, $regex)
    {
        $route = new \Phower\Router\Route($name, $definition, $constraints, $defaults);

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($definition, $route->getDefinition());
        $this->assertEquals($constraints, $route->getConstraints());
        $this->assertEquals($defaults, $route->getDefaults());
        $this->assertEquals($regex, $route->getRegex());
    }

    public function constructProvider()
    {
        return [
            ['home', '/', [], [], '/'],
            ['route_with_path', '/path', [], [], '/path'],
            ['route_with_path', '/path/to/folder', [], [], '/path/to/folder'],
            ['route_with_param', '/path/:param1', [], [], '/path/(?P<param1>[^/]+)'],
            ['route_with_param', '/path/:param1', ['param1' => '[\d]+'], ['param1' => 100], '/path/(?P<param1>[\d]+)'],
            ['route_with_params', '/path/:param1/:param2', [], [], '/path/(?P<param1>[^/]+)/(?P<param2>[^/]+)'],
            ['route_with_params', '/path/:param1/folder/:param2', [], [], '/path/(?P<param1>[^/]+)/folder/(?P<param2>[^/]+)'],
            ['route_with_params', '/:param1/folder/:param2', [], [], '/(?P<param1>[^/]+)/folder/(?P<param2>[^/]+)'],
            ['route_with_params', '/:param1/:param2', ['param1' => '[\d]+', 'param2' => '[0-9a-z\.]+'], [], '/(?P<param1>[\d]+)/(?P<param2>[0-9a-z\.]+)'],
            ['route_with_optional', '/path[/:optional1]', ['optional1' => '[\d]+'], ['optional1' => 1], '/path(?:/(?P<param1>[\d]+))?'],
            ['route_with_optional', '/path[/:optional[/:optional2]1]', ['optional1' => '[\d]+'], ['optional1' => 1], '/path(?:/(?P<param1>[^/]+)(?:/(?P<param2>[^/]+))?1)?'],
            ['route_with_optional', '/:name{-}[-:id]', [], [], '/(?P<param1>[^-]+)(?:\-(?P<param2>[^/]+))?'],
        ];
    }

    public function testConstructRaisesExceptionWhenNameIsNotString()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = new \Phower\Router\Route(123, '/');
    }

    public function testConstructRaisesExceptionWhenDefinitionIsNotString()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = new \Phower\Router\Route('bad', 123);
    }

    public function testParseDefinitionRaisesExceptionWhenAnEmptyParameterNameIsFound()
    {
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $route = new \Phower\Router\Route('name', '/:/:param');
    }

    public function testParseDefinitionRaisesExceptionWhenUnbalancedOpenBracketsAreFound()
    {
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $route = new \Phower\Router\Route('name', '[/:param');
    }

    public function testParseDefinitionRaisesExceptionWhenUnbalancedCloseBracketsAreFound()
    {
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $route = new \Phower\Router\Route('name', '/:param]');
    }

    public function testFactoryCanInstantiateNewRouteFromArray()
    {
        $route = \Phower\Router\Route::factory(['name' => 'my_route', 'definition' => '/path']);
        $this->assertInstanceOf(\Phower\Router\Route::class, $route);
    }

    public function testFactoryRaisesExceptionWhenNameIsMissingInConfig()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        \Phower\Router\Route::factory([]);
    }

    public function testFactoryRaisesExceptionWhenDefinitionIsMissingInConfig()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        \Phower\Router\Route::factory(['name' => 'my_route']);
    }

    public function testSetMethodsCanAcceptAgumentTrue()
    {
        $route = new \Phower\Router\Route('home', '/');
        $route->setMethods(true);
        $this->assertTrue($route->getMethods());
    }

    public function testSetMethodsCanAcceptAgumentString()
    {
        $route = new \Phower\Router\Route('home', '/');
        $route->setMethods('get');
        $this->assertEquals(['GET'], $route->getMethods());
    }

    public function testSetMethodsCanAcceptAgumentArray()
    {
        $route = new \Phower\Router\Route('home', '/');
        $methods = ['GET', 'POST'];
        $route->setMethods($methods);
        $this->assertEquals($methods, $route->getMethods());
    }

    public function testSetMethodsRaisesExceptionWhenArgumentDoesNotEvaluateToArray()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = \Phower\Router\Route::factory(['name' => 'home', 'definition' => '/']);
        $route->setMethods(123);
    }

    public function testSetMethodsRaisesExceptionWhenAnyElementOfMethodsIsNotString()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = \Phower\Router\Route::factory(['name' => 'home', 'definition' => '/']);
        $methods = ['get', 123];
        $route->setMethods($methods);
    }

    public function testSetMethodsRaisesExceptionWhenAnyMethodIsInvalid()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = \Phower\Router\Route::factory(['name' => 'home', 'definition' => '/']);
        $methods = ['get', 'test'];
        $route->setMethods($methods);
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatchReturnsTrueWhenRouteMatchesGivenRequest($name, $definition, $constraints, $path, $offset, $expected)
    {
        $uri = $this->getMockBuilder(\Psr\Http\Message\UriInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $uri->expects($this->any())
                ->method('getPath')
                ->will($this->returnValue($path));

        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $request->expects($this->any())
                ->method('getUri')
                ->will($this->returnValue($uri));

        $route = new \Phower\Router\Route($name, $definition, $constraints);
        $this->assertTrue($expected === $route->match($request, $offset));
    }

    public function matchProvider()
    {
        return [
            ['home', '/', [], '/', null, true],
            ['post', '/post/123', [], '/post/123', null, true],
            ['post', '/post/:id', ['id' => '[\d]+'], '/post/123', null, true],
            ['post', '/post[/:id]', ['id' => '[\d]+'], '/post', null, true],
            ['post', '/post[/:id]', ['id' => '[\d]+'], '/post/123', null, true],
            ['path', '/path', [], '/base/path', '/base', true],
            ['path', '/path', [], '/other/path', '/base', false],
        ];
    }

    /**
     * @dataProvider paramsProvider
     */
    public function testParamsReturnsArrayOfParamsUponSuccesfulMatch($name, $definition, $constraints, $defaults, $path, $expected)
    {
        $uri = $this->getMockBuilder(\Psr\Http\Message\UriInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $uri->expects($this->any())
                ->method('getPath')
                ->will($this->returnValue($path));

        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $request->expects($this->any())
                ->method('getUri')
                ->will($this->returnValue($uri));

        $route = new \Phower\Router\Route($name, $definition, $constraints, $defaults);
        $route->match($request);

        $this->assertEquals($expected, $route->params());
    }

    public function paramsProvider()
    {
        return [
            ['home', '/', [], ['foo' => 'bar'], '/', ['foo' => 'bar']],
            ['param', '/:id', [], ['foo' => 'bar'], '/123', ['foo' => 'bar', 'id' => 123]],
            ['path', '/path[/:foo]', [], ['foo' => 'bar'], '/path', ['foo' => 'bar']],
            ['path', '/path[/:foo]', [], ['foo' => 'bar'], '/path/ooz', ['foo' => 'ooz']],
        ];
    }

    /**
     * @dataProvider allowProvider
     */
    public function testAllowReturnsTrueWhenMethodIsAllowed($method, $methods, $expected)
    {
        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $request->expects($this->any())
                ->method('getMethod')
                ->will($this->returnValue(true));

        $route = new \Phower\Router\Route('home', '/');
        $this->assertTrue($route->allow($request));

        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $request->expects($this->any())
                ->method('getMethod')
                ->will($this->returnValue($method));

        $route = new \Phower\Router\Route('home', '/', [], [], $methods);
        $this->assertTrue($expected === $route->allow($request));
    }

    public function allowProvider()
    {
        return [
            ['GET', true, true],
            ['GET', ['GET', 'POST'], true],
            ['PUT', ['GET', 'POST'], false],
        ];
    }

    /**
     * @dataProvider assembleProvider
     */
    public function testAssembleReturnsAssembledPath($name, $definition, $defaults, $params, $path)
    {
        $route = new \Phower\Router\Route($name, $definition, [], $defaults);

        $this->assertEquals($path, $route->assemble($params));
    }

    public function assembleProvider()
    {
        return [
            [
                'path',
                '/path',
                [],
                [],
                '/path'
            ],
            [
                'path',
                '/path/:mandatory',
                [],
                ['mandatory' => 'foo'],
                '/path/foo'
            ],
            [
                'path',
                '/path/:mandatory[/:optional]',
                [],
                ['mandatory' => 'foo', 'optional' => 123],
                '/path/foo/123'
            ],
            [
                'path',
                '/:param',
                [],
                ['param' => 'foo', 'optional' => 123],
                '/foo'
            ],
        ];
    }

    public function testAssembleReturnsEmptyWhenParameterIsOptionalAndItIsNotProvided()
    {
        $name = 'name';
        $definition = '/path/:mandatory[/:optional]';
        $defaults = [];
        $params = ['mandatory' => 'foo'];
        $path = '/path/foo';

        $route = new \Phower\Router\Route($name, $definition, [], $defaults);

        $this->assertEquals($path, $route->assemble($params));
    }

    public function testAssembleRaisesExceptionWhenRequiredParameterIsMissing()
    {
        $name = 'name';
        $definition = '/path/:mandatory[/:optional]';
        $defaults = [];
        $params = [];

        $route = new \Phower\Router\Route($name, $definition, [], $defaults);

        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route->assemble($params);
    }

    public function testSetAndGetName()
    {
        $route = new \Phower\Router\Route('home', '/');
        $this->assertEquals('home', $route->getName());
        $name = 'new_name';
        $route->setName($name);
        $this->assertEquals($name, $route->getName());
    }

    public function testSetNameRaisesExceptionWhenNameIsNotString()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = new \Phower\Router\Route(true, '/');
    }

    public function testSetNameRaisesExceptionWhenNameContainsSlashes()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $route = new \Phower\Router\Route('some/name', '/');
    }
}
