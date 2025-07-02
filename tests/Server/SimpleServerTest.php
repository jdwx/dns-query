<?php /** @noinspection PhpUndefinedMethodInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Server;


use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\Server\SimpleServer;
use JDWX\DNSQuery\Transport\BufferInterface;
use JDWX\DNSQuery\Transport\TransportInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


#[CoversClass( SimpleServer::class )]
final class SimpleServerTest extends TestCase {


    public function testConstructor() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf( SimpleServer::class, $server );
    }


    public function testCreateUdp() : void {
        $server = SimpleServer::createUdp( '127.0.0.1', 5353 );

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf( SimpleServer::class, $server );
    }


    public function testCustomHandlerWithComplexResponse() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $handler = function ( MessageInterface $request ) : MessageInterface {
            $response = Message::response( $request, ReturnCode::SERVFAIL );
            $response->header()->setAA( AA::AUTHORITATIVE );

            // Add a custom resource record
            $response->addAnswer( ResourceRecord::fromString(
                'custom.example.com. 600 IN A 192.0.2.1'
            ) );

            return $response;
        };

        $server->setRequestHandler( $handler );
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertSame( 'SERVFAIL', $response->header()->rCode() );
        self::assertSame( 'AUTHORITATIVE', $response->header()->aa() );
        self::assertCount( 1, $response->getAnswer() );
        self::assertSame( 'A', $response->getAnswer()[ 0 ]->type() );
    }


    public function testEmptyRecordHandler() : void {
        $handler = SimpleServer::recordHandler( [] );
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
        self::assertSame( 'NOERROR', $response->header()->rCode() );
        self::assertCount( 0, $response->getAnswer() );
    }


    public function testHandleRequests() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $request1 = $this->createTestRequest();
        $request2 = $this->createTestRequest();
        $request3 = $this->createTestRequest();

        // Setup codec mock
        $codec = $this->createMock( CodecInterface::class );
        $buffer = $this->createMock( BufferInterface::class );

        $codec->expects( self::exactly( 3 ) )
            ->method( 'decode' )
            ->willReturnOnConsecutiveCalls( $request1, $request2, $request3 );

        $codec->expects( self::exactly( 3 ) )
            ->method( 'encode' )
            ->willReturn( 'encoded' );

        $transport->expects( self::exactly( 3 ) )
            ->method( 'send' );

        // Inject mocks
        $reflection = new \ReflectionClass( $server );
        $codecProp = $reflection->getProperty( 'codec' );
        $codecProp->setValue( $server, $codec );

        $bufferProp = $reflection->getProperty( 'buffer' );
        $bufferProp->setValue( $server, $buffer );

        $handledCount = $server->handleRequests( 3 );

        self::assertSame( 3, $handledCount );
    }


    public function testHandleRequestsWithTimeout() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $request = $this->createTestRequest();

        // Setup codec mock
        $codec = $this->createMock( CodecInterface::class );
        $buffer = $this->createMock( BufferInterface::class );

        $codec->expects( self::exactly( 2 ) )
            ->method( 'decode' )
            ->willReturnOnConsecutiveCalls( $request, null );

        $codec->expects( self::once() )
            ->method( 'encode' )
            ->willReturn( 'encoded' );

        $transport->expects( self::once() )
            ->method( 'send' );

        // Inject mocks
        $reflection = new \ReflectionClass( $server );
        $codecProp = $reflection->getProperty( 'codec' );
        $codecProp->setValue( $server, $codec );

        $bufferProp = $reflection->getProperty( 'buffer' );
        $bufferProp->setValue( $server, $buffer );

        $handledCount = $server->handleRequests( 5 );

        self::assertSame( 1, $handledCount );
    }


    public function testHandleRequestsZeroMax() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $transport->expects( self::never() )
            ->method( 'send' );

        $handledCount = $server->handleRequests( 0 );

        self::assertSame( 0, $handledCount );
    }


    public function testHandleSingleRequest() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $request = $this->createTestRequest();
        $encodedResponse = 'encoded-response';

        // Setup codec mock
        $codec = $this->createMock( CodecInterface::class );
        $buffer = $this->createMock( BufferInterface::class );

        $codec->expects( self::once() )
            ->method( 'decode' )
            ->with( $buffer )
            ->willReturn( $request );

        $codec->expects( self::once() )
            ->method( 'encode' )
            ->with( self::isInstanceOf( MessageInterface::class ) )
            ->willReturn( $encodedResponse );

        $transport->expects( self::once() )
            ->method( 'send' )
            ->with( $encodedResponse );

        // Inject mocks
        $reflection = new \ReflectionClass( $server );
        $codecProp = $reflection->getProperty( 'codec' );
        $codecProp->setValue( $server, $codec );

        $bufferProp = $reflection->getProperty( 'buffer' );
        $bufferProp->setValue( $server, $buffer );

        $result = $server->handleSingleRequest();

        self::assertTrue( $result );
    }


    public function testHandleSingleRequestTimeout() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        // Setup codec mock to return null (timeout)
        $codec = $this->createMock( CodecInterface::class );
        $buffer = $this->createMock( BufferInterface::class );

        $codec->expects( self::once() )
            ->method( 'decode' )
            ->with( $buffer )
            ->willReturn( null );

        $transport->expects( self::never() )
            ->method( 'send' );

        // Inject mocks
        $reflection = new \ReflectionClass( $server );
        $codecProp = $reflection->getProperty( 'codec' );
        $codecProp->setValue( $server, $codec );

        $bufferProp = $reflection->getProperty( 'buffer' );
        $bufferProp->setValue( $server, $buffer );

        $result = $server->handleSingleRequest();

        self::assertFalse( $result );
    }


    public function testHandlerException() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $handler = function () : MessageInterface {
            throw new \RuntimeException( 'Handler error' );
        };

        $server->setRequestHandler( $handler );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Handler error' );

        $this->invokeMethod( $server, 'processRequest', [ $request ] );
    }


    public function testNxDomainHandler() : void {
        $handler = SimpleServer::nxDomainHandler();
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
        self::assertSame( 'NXDOMAIN', $response->header()->rCode() );
    }


    public function testProcessRequestWithNullResponse() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $handler = function ( MessageInterface $request ) : ?MessageInterface {
            return null;
        };

        $server->setRequestHandler( $handler );
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertNull( $response );
    }


    public function testProcessRequestWithoutHandler() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
        self::assertSame( $request->getQuestion(), $response->getQuestion() );
    }


    public function testRecordHandler() : void {
        $records = [
            ResourceRecord::fromString( 'example.com 300 IN A 1.2.3.4' ),
            ResourceRecord::fromString( 'example.com 300 IN A 5.6.7.8' ),
        ];

        $handler = SimpleServer::recordHandler( $records );
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
        self::assertSame( 'NOERROR', $response->header()->rCode() );
        self::assertCount( 2, $response->getAnswer() );

        // Check that answers match the provided records
        $answers = $response->getAnswer();
        self::assertSame( '1.2.3.4', $answers[ 0 ]->tryGetRDataValue( 'address' ) );
        self::assertSame( '5.6.7.8', $answers[ 1 ]->tryGetRDataValue( 'address' ) );
    }


    public function testServFailHandler() : void {
        $handler = SimpleServer::servFailHandler();
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
        self::assertSame( 'SERVFAIL', $response->header()->rCode() );
    }


    public function testSetRequestHandler() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $handler = function ( MessageInterface $request ) : MessageInterface {
            return Message::response( $request );
        };

        $server->setRequestHandler( $handler );

        // Test that the handler is set by invoking it through processRequest
        $request = $this->createTestRequest();
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id(), $response->id() );
    }


    /**
     * @return TransportInterface&MockObject
     * @suppress PhanTypeMismatchReturnSuperType
     */
    private function createMockTransport() : TransportInterface {
        return $this->createMock( TransportInterface::class );
    }


    private function createTestRequest() : MessageInterface {
        return Message::request( new Question( 'example.com', 'A', 'IN' ) );
    }


    /**
     * Helper method to invoke protected/private methods for testing
     *
     * @param mixed[] $parameters
     */
    private function invokeMethod( object $object, string $methodName, array $parameters = [] ) : mixed {
        $reflection = new \ReflectionClass( get_class( $object ) );
        $method = $reflection->getMethod( $methodName );
        return $method->invokeArgs( $object, $parameters );
    }


}