<?php declare(strict_types=1);
# -*- coding: utf-8 -*-
/*
 * This file is part of the CrawlEngine Package
 *
 * (c) DeRavenedWriter
 *
 */

namespace CrawlEngine;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @author  DeRavenedWriter <deravenedwriter@gmail.com>
 * @package CrawlEngine
 * @license http://opensource.org/licenses/MIT MIT
 */
class EngineTest extends TestCase
{

    /**
     * Returns a mock client for Test purposes
     *
     * @param bool $willThrowException      Weather or not Client should throw Exception
     *
     * @return \GuzzleHttp\Client
     */
    public function client(bool $willThrowException= false) : \GuzzleHttp\Client
    {
        $response = !$willThrowException ?
            (new Response(200, ['X-Foo' => 'Bar'], 'SomeNiceTests')) :
            (new RequestException('Error Communicating with Server', new Request('GET', 'test')));
        $clientMocks = new MockHandler([$response]);

        $handlerStack = HandlerStack::create($clientMocks);
        return new \GuzzleHttp\Client(['handler' => $handlerStack]);
    }


    /** @test */
    public function if_constructor_works()
    {
        $inputDetail = $this->getMockBuilder(Engine::class)
            ->disableOriginalConstructor()
            ->getMock();


        $reflectedClass = new ReflectionClass(Engine::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke($inputDetail, 10);
        $this->assertSame(10, $inputDetail->timeOut);
    }


    /** @test */
    public function if_get_login_fields_works()
    {
        $url = 'https://example.com/login';
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pageContent', 'formInputs'])
            ->getMock();


        $engine->expects($this->once())
            ->method('pageContent')
            ->with($url)
            ->willReturn(file_get_contents(__DIR__ . '/htmltextpage.txt'));

        $engine->expects($this->once())
            ->method('formInputs')
            ->with(file_get_contents(__DIR__ . '/htmltextpage.txt'))
            ->willReturn(
                [
                    (new \CrawlEngine\InputDetail('somestuff')),
                    (new \CrawlEngine\InputDetail('anotherstuff'))
                ]
            );


        $loginDetails = $engine->getLoginFields($url);

        $this->assertInstanceOf(
            \CrawlEngine\InputDetail::class,
            $loginDetails[0]
        );

        $this->assertSame('somestuff', $loginDetails[0]->name);
        $this->assertSame('anotherstuff', $loginDetails[1]->name);
    }

    /** @test */
    public function if_form_inputs_works()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['inputDetail'])
            ->getMock();


        $engine->expects($this->exactly(8))
            ->method('inputDetail')
            ->willReturn(new \CrawlEngine\InputDetail('somename'));


        $fields = $engine->formInputs(file_get_contents(__DIR__ . '/htmltextpage.txt'));

        $this->assertInstanceOf(\CrawlEngine\InputDetail::class, $fields[0]);
        $this->assertSame(8, count($fields));
        $this->assertSame('somename', $fields[0]->name);
    }


    /** @test */
    public function if_headers_return_defaults()
    {
        $headers = (new \CrawlEngine\Engine())->headers();
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Origin', $headers);
        $this->assertArrayNotHasKey('Content-Length', $headers);
        $this->assertSame(9, count($headers));
    }


    /** @test */
    public function if_headers_return_length()
    {
        $headers = (new \CrawlEngine\Engine())->headers(10, 'http://somesite.com/');
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertSame(10, count($headers));
        $this->assertSame('10', $headers['Content-Length']);
        $this->assertSame('http://somesite.com/', $headers['Referer']);
    }


    /** @test */
    public function if_page_content_throws_exception()
    {
        $client = $this->client(true);
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['client', 'headers'])
            ->getMock();

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($client);

        $engine->expects($this->once())
            ->method('headers');

        $this->expectException('CrawlEngine\\CrawlEngineException');
        $engine->pageContent('http://somesite.com/');
    }

    /** @test */
    public function if_page_content_returns_string()
    {
        $client = $this->client();

        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['client', 'headers'])
            ->getMock();

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($client);

        $engine->expects($this->once())
            ->method('headers');

        $this->assertSame('SomeNiceTests', $engine->pageContent('http://somesite.com/'));
    }


    /** @test */
    public function inputs_to_assoc_form()
    {
        $inputs = [
            (new \CrawlEngine\InputDetail('somename', 'somevalue')),
            (new \CrawlEngine\InputDetail('anothername', 'anothervalue')),
        ];

        $assoc = (new \CrawlEngine\Engine())->inputsToAssocArray($inputs);
        $this->assertSame(2, count($assoc));
        $this->assertSame('somevalue', $assoc['somename']);
        $this->assertSame('anothervalue', $assoc['anothername']);
    }

    ///** @test */
    public function if_resolve_content_works()
    {
        $clientmocks = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'SomeNiceTests'),
        ]);

        $handlerStack = HandlerStack::create($clientmocks);
        $client = new Client(['handler' => $handlerStack]);

        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['isPropertyNull', 'client', 'headers', 'preFilledInputs', 'formPage'])
            ->getMock();

        $engine->expects($this->once())
            ->method('checkEssentials');

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($client);

        $engine->expects($this->once())
            ->method('formPage')
            ->willReturn('http://someurl.com/');

        $engine->expects($this->once())
            ->method('headers')
            ->willReturn(['X-Foo' => 'Bar']);

        $engine->expects($this->once())
            ->method('preFilledInputs')
            ->with('SomeNiceTests');


        $contents = $engine->resolveRequest();
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $contents[0]);
    }

    ///** @test */
    public function if_resolve_content_throws_bad_method_call()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['isPropertyNull'])
            ->getMock();

        $engine->expects($this->once())
            ->method('isPropertyNull')
            ->willReturn(true);

        $this->expectException('BadMethodCallException');
        $contents = $engine->resolveRequest();
    }

    /** @test */
    public function if_prefilled_input_returns_input ()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['formInputs'])
            ->getMock();

        $engine->expects($this->once())
            ->method('formInputs')
            ->with('SomeNiceTests')
            ->willReturn(
                [
                    (new \CrawlEngine\InputDetail('somename', 'somevalue')),
                    (new \CrawlEngine\InputDetail('anothername')),
                ]
            );

        $inputs = $engine->preFilledInputs('SomeNiceTests');
        $this->assertSame(1, count($inputs));
        $this->assertSame('somename', $inputs[0]->name);
        $this->assertSame('somevalue', $inputs[0]->value);
    }

    /** @test */
    public function if_resolve_form_data_throws_exception()
    {
        $client = $this->client(true);
        $userInputs = [
            (new \CrawlEngine\InputDetail('someuserinput', 'someuservalue')),
            (new \CrawlEngine\InputDetail('anotheruserinput', 'anotheruservalue')),
        ];

        $this->expectException('CrawlEngine\\CrawlEngineException');
        (new \CrawlEngine\Engine())->resolveFormData($client, 'http://somesite.com/', $userInputs);

    }


    /** @test */
    public function if_resolve_form_data_returns_data()
    {
        $client = $this->client();
        $userInputs = [
            (new \CrawlEngine\InputDetail('someuserinput', 'someuservalue')),
            (new \CrawlEngine\InputDetail('anotheruserinput', 'anotheruservalue')),
        ];
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['headers', 'preFilledInputs', 'inputsToAssocArray', 'getQualifiedInputs'])
            ->getMock();

        $engine->expects($this->once())
            ->method('headers');

        $engine->expects($this->once())
            ->method('preFilledInputs')
            ->with('SomeNiceTests')
            ->willReturn([(new \CrawlEngine\InputDetail('somename', 'somevalue'))]);

        $engine->expects($this->once())
            ->method('getQualifiedInputs')
            ->with([(new \CrawlEngine\InputDetail('somename', 'somevalue'))], $userInputs)
            ->willReturn([(new \CrawlEngine\InputDetail('somename', 'somevalue'))]);

        $engine->expects($this->once())
            ->method('inputsToAssocArray')
            ->with([(new \CrawlEngine\InputDetail('somename', 'somevalue'))])
            ->willReturn(['somename' => 'somevalue']);

        $formData = $engine->resolveFormData($client, 'http://somesite.com/', $userInputs);
        $this->assertSame('somevalue', $formData['somename']);
    }

    /** @test */
    public function if_form_length_works()
    {
        $formData = [
            'somename' => 'somevalue',
            'anothername' => 'anothervalue',
        ];

        $this->assertSame(43, (new \CrawlEngine\Engine())->formLength($formData));
    }


    /** @test */
    public function if_get_all_crawlers_throws_exception()
    {
        $clientmocks = new MockHandler([
            new RequestException('Error Communicating with Server', new Request('GET', 'test')),
        ]);

        $handlerStack = HandlerStack::create($clientmocks);
        $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);


        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['headers'])
            ->getMock();

        $engine->expects($this->once())
            ->method('headers');


        $this->expectException('CrawlEngine\\CrawlEngineException');
        $engine->getAllCrawlers($client, ['http://somesite.com/']);
    }


    /** @test */
    public function if_get_all_crawlers_returns_crawlers()
    {
        $client = $this->client();

        $crawler = new \Symfony\Component\DomCrawler\Crawler(file_get_contents(__DIR__ . '/htmltextpage.txt'));

        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['headers', 'crawler'])
            ->getMock();

        $engine->expects($this->once())
            ->method('headers');

        $engine->expects($this->once())
            ->method('crawler')
            ->with('SomeNiceTests')
            ->willReturn($crawler);

        $crawlers = $engine->getAllCrawlers($client, ['http://somesite.com/']);
        $this->assertSame(1, count($crawlers));
        $this->assertSame('uid', $crawlers[0]->filter('body > form > input' )->first()->attr('name'));
    }

    /** @test */
    public function if_resolve_request_throws_exception_from_form_data()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['client', 'resolveFormData'])
            ->getMock();

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($this->client());

        $engine->expects($this->once())
            ->method('resolveFormData')
            ->willThrowException(new \CrawlEngine\CrawlEngineException('sometestgonewrong'));

        $this->expectException('CrawlEngine\\CrawlEngineException');
        $engine->resolveRequest(
            'http://somesite.com/',
            'http://somesite.com/login',
            [],
            []
        );
    }

    /** @test */
    public function if_resolve_request_throws_exception_when_submitting_form()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['client', 'resolveFormData', 'headers'])
            ->getMock();

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($this->client(true));

        $engine->expects($this->once())
            ->method('resolveFormData')
            ->willReturn(['somename' => 'somevalue']);

        $engine->expects($this->once())
            ->method('headers')
            ->willReturn(['X-Foo' => 'Bar']);


        $this->expectException('CrawlEngine\\CrawlEngineException');
        //$this->getExpectedExceptionMessageRegExp('Request Failed in Submitting Form as Follows:');
        $engine->resolveRequest(
            'http://somesite.com/',
            'http://somesite.com/login',
            [],
            []
        );
    }

    /** @test */
    public function if_resolve_request_returns_crawler()
    {
        $engine = $this->getMockBuilder(\CrawlEngine\Engine::class)
            ->onlyMethods(['client', 'resolveFormData', 'headers', 'crawler', 'getAllCrawlers'])
            ->getMock();

        $engine->expects($this->once())
            ->method('client')
            ->willReturn($this->client());

        $engine->expects($this->once())
            ->method('resolveFormData')
            ->willReturn(['somename' => 'somevalue']);

        $engine->expects($this->once())
            ->method('headers')
            ->willReturn(['X-Foo' => 'Bar']);

        $engine->expects($this->once())
            ->method('getAllCrawlers')
            ->willReturn([new \Symfony\Component\DomCrawler\Crawler('<html><body>teststuffs</body></html>')]);

        $crawlers = $engine->resolveRequest(
            'http://somesite.com/',
            'http://somesite.com/login',
            [],
            []
        );
        $this->assertInstanceOf(\Symfony\Component\DomCrawler\Crawler::class, $crawlers[0]);
    }
}
