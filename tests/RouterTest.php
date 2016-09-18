<?php

namespace PhowerTest\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{

    protected $routes;
    protected $router;

    protected function setUp()
    {
        $this->routes = [];

        for ($i = 0; $i < 10; $i++) {
            $this->routes[] = new \Phower\Router\Route("route#{$i}", "/path/to/page{$i}");
        }

        $this->router = new \Phower\Router\Router($this->routes);
    }

    public function testRouterImplementsRouterInterface()
    {
        $this->assertInstanceOf(\Phower\Router\RouterInterface::class, $this->router);
    }

    public function testRouterImplementsStackInterface()
    {
        $this->assertInstanceOf(\Phower\Arrays\StackInterface::class, $this->router);
    }

    public function testHasChecksIfNamedRouteExists()
    {
        $name = $this->routes[3]->getName();
        $this->assertTrue($this->router->has($name));
        $this->assertFalse($this->router->has('not_there'));
    }

    public function testSetAddsNewRouteToRouter()
    {
        $name = 'home';
        $route = new \Phower\Router\Route($name, '/');

        $this->router->set($name, $route);

        $this->assertSame($route, $this->router->get($name));
        $this->assertNull($this->router->get('none'));
    }

    public function testRemoveCanRemoveAnExistingRoute()
    {
        $route = $this->routes[2];
        $name = $route->getName();

        $this->assertTrue($this->router->has($name));
        $this->router->remove($name);
        $this->assertFalse($this->router->has($name));
    }

    public function testMatchAttemptsToMatchRequestAgainstARoute()
    {
        $routes = [
            new \Phower\Router\Route('home', '/'),
            new \Phower\Router\Route('path', '/path'),
            new \Phower\Router\Route('param', '/path/:param'),
        ];

        $router = new \Phower\Router\Router($routes);

        // invalid path
        $path = '/not/a/valid/path';

        $uri = $this->getMockBuilder(\Psr\Http\Message\UriInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $uri->expects($this->any())->method('getPath')
                ->will($this->returnValue($path));

        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getUri')
                ->will($this->returnValue($uri));

        $this->assertFalse($router->match($request));

        // valid
        $path = '/path/123';

        $uri = $this->getMockBuilder(\Psr\Http\Message\UriInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $uri->expects($this->any())->method('getPath')
                ->will($this->returnValue($path));

        $request = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)
                        ->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getUri')
                ->will($this->returnValue($uri));

        $expected = $routes[2];

        $this->assertTrue($router->match($request));
        $this->assertTrue($router->hasMatched());
        $this->assertSame($expected, $router->getMatched());
    }

    public function testGetMatchedRaisesExceptionWhenThereIsNotMatchedRoute()
    {
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $this->router->getMatched();
    }

    public function testAssembleCanAssembleANamedRoute()
    {
        $routes = [
            ['definition' => '/', 'name' => 'home'],
            ['definition' => '/path', 'name' => 'path'],
            ['definition' => '/path/:param', 'name' => 'param'],
        ];

        $router = new \Phower\Router\Router($routes);

        $this->assertEquals('/path/123', $router->assemble('param', ['param' => 123]));
    }

    public function testAssembleThrowsExceptionWhenNameIsInvalid()
    {
        $router = new \Phower\Router\Router();
        $this->setExpectedException(\InvalidArgumentException::class);
        $router->assemble(true);
    }

    public function testAssembleThrowsExceptionWhenNameIsNotFound()
    {
        $router = new \Phower\Router\Router();
        $this->setExpectedException(\InvalidArgumentException::class);
        $router->assemble('name');
    }

    public function testConstainsReturnsTrueWhenRouterContainsGivenRoute()
    {
        $this->assertFalse($this->router->contains(new \Phower\Router\Route('home', '/')));
        $this->assertTrue($this->router->contains($this->routes[5]));
    }

    public function testAddRaisesExceptionWhenTryToAddRouteWithExistingName()
    {
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $this->router->add($this->routes[3]);
    }

    public function testIndexOfReturnsNameOfAGivenRoute()
    {
        $route = $this->routes[8];
        $this->assertEquals($route->getName(), $this->router->indexOf($route));
    }

    public function testIndexOfReturnsNullWhenRouteIsNotPresent()
    {
        $route = new \Phower\Router\Route('home', '/');
        $this->assertNull($this->router->indexOf($route));
    }

    public function testDeleteCanRemoveAGivenRouteFromRouter()
    {
        $route = $this->routes[8];
        $this->assertTrue($this->router->contains($route));
        $this->router->delete($route);
        $this->assertFalse($this->router->contains($route));
    }

    public function testToArrayReturnsRoutesAsAnArray()
    {
        $this->assertEquals(array_reverse($this->routes), $this->router->toArray());
    }

    public function testGetKeysReturnsAllNamesInAnArray()
    {
        $keys = [];
        foreach ($this->routes as $route) {
            $keys[] = $route->getName();
        }

        $this->assertEquals(array_reverse($keys), $this->router->getKeys());
    }

    public function testGetValuesReturnsRoutesAsAnArray()
    {
        $this->assertEquals(array_reverse($this->routes), $this->router->getValues());
    }

    public function testFilterReturnsNewFilteredRouter()
    {
        $callback = function ($route) {
            return substr($route->getName(), -1) < 3;
        };
        $routes = array_filter($this->routes, $callback);
        $this->assertEquals(array_reverse($routes), $this->router->filter($callback)->toArray());
    }

    public function testSliceReturnsNewSlicedRouter()
    {
        $routes = array_slice(array_reverse($this->routes), 5, 3);
        $this->assertEquals($routes, $this->router->slice(5, 3)->toArray());
    }

    public function testMapAppliesACallbackOverAllRoutes()
    {
        $callback = function ($route) {
            return $route->setName(strtoupper($route->getName()));
        };
        $routes = array_map($callback, $this->routes);
        $this->assertEquals(array_reverse($routes), $this->router->map($callback)->toArray());
    }

    public function testRouterImplementsArrayAccess()
    {
        $router = new \Phower\Router\Router();
        $this->assertInstanceOf(\ArrayAccess::class, $this->router);
        foreach ($this->routes as $route) {
            $router->add($route);
        }
        $this->assertTrue(isset($router['route#1']));
        $this->assertSame($this->routes[1], $router['route#1']);
        $this->assertEquals(count($this->routes), $router->count());
        $router['home'] = new \Phower\Router\Route('home', '/');
        $this->assertEquals(count($this->routes) + 1, $router->count());
        unset($router['route#1']);
        $this->assertFalse(isset($router['route#1']));
    }

    public function testRouterImplementsCountable()
    {
        $this->assertInstanceOf(\Countable::class, $this->router);
    }

    public function testRouterImplementsIterator()
    {
        $this->assertInstanceOf(\Iterator::class, $this->router);
        foreach ($this->router as $name => $route) {
            $this->assertInstanceOf(\Phower\Router\RouteInterface::class, $route);
            $this->assertEquals($name, $route->getName());
        }
    }

    public function testRouterImplementsSerializable()
    {
        $routes = $this->router->toArray();
        $serialized = $this->router->serialize();
        $this->router = new \Phower\Router\Router();
        $this->router->unserialize($serialized);
        $this->assertEquals($routes, $this->router->toArray());
    }

    public function testUnserializeRaisesExceptionWhenSerializedStringIsInvalid()
    {
        $serialized = serialize([]);
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $this->router->unserialize($serialized);
    }

    public function testUnserializeRaisesExceptionWhenUnserializedValueIsNotArray()
    {
        $serialized = \Phower\Router\Router::class . '@' . serialize(true);
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $this->router->unserialize($serialized);
    }

    public function testUnserializeRaisesExceptionWhenUnserializedArrayDoesNotContainElementNames()
    {
        $routes = new \Phower\Arrays\Stack([new \Phower\Router\Route('home', '/')]);
        $serialized = \Phower\Router\Router::class . '@' . serialize(['routes' => $routes->serialize()]);
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $this->router->unserialize($serialized);
    }

    public function testUnserializeRaisesExceptionWhenUnserializedArrayDoesNotContainElementRoutes()
    {
        $names = new \Phower\Arrays\Stack(['home', 'named']);
        $serialized = \Phower\Router\Router::class . '@' . serialize(['names' => $names->serialize()]);
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $this->router->unserialize($serialized);
    }

    public function testUnserializeRaisesExceptionWhenUnserializedNumberOfNamesDoesNotMatchNumberOfRoutes()
    {
        $names = new \Phower\Arrays\Stack(['home', 'named']);
        $routes = new \Phower\Arrays\Stack([new \Phower\Router\Route('home', '/')]);
        $serialized = \Phower\Router\Router::class . '@' . serialize(['names' => $names->serialize(), 'routes' => $routes->serialize()]);
        $this->setExpectedException(\Phower\Router\Exception\RuntimeException::class);
        $this->router->unserialize($serialized);
    }

    public function testValidateRouteRaisesExceptionWhenRouteIsInvalid()
    {
        $this->setExpectedException(\Phower\Router\Exception\InvalidArgumentException::class);
        $this->router->contains('not_a_route');
    }
}
