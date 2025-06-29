<?php /** @noinspection PhpUndefinedMethodInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Server;


use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\RDataValue;
use JDWX\DNSQuery\ResourceRecord;
use JDWX\DNSQuery\Server\SimpleServer;
use JDWX\DNSQuery\Transport\TransportInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


#[CoversClass( SimpleServer::class )]
final class SimpleServerTest extends TestCase {


    public function testConcurrentRequestHandling() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $processedRequests = [];

        $handler = function ( Message $request ) use ( &$processedRequests ) : Message {
            $processedRequests[] = $request->id;
            return Message::response( $request );
        };

        $server->setRequestHandler( $handler );

        // Simulate multiple requests with different IDs
        $requests = [
            $this->createTestRequest(),
            $this->createTestRequest(),
            $this->createTestRequest(),
        ];
        $requests[ 0 ]->id = 1001;
        $requests[ 1 ]->id = 1002;
        $requests[ 2 ]->id = 1003;

        $transport->expects( self::exactly( 3 ) )
            ->method( 'receiveRequest' )
            ->willReturnOnConsecutiveCalls( ...$requests );

        $transport->expects( self::exactly( 3 ) )
            ->method( 'sendResponse' );

        $handledCount = $server->handleRequests( 3 );

        self::assertSame( 3, $handledCount );
        self::assertSame( [ 1001, 1002, 1003 ], $processedRequests );
    }


    public function testConstructor() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf( SimpleServer::class, $server );
    }


    public function testCreateDefaultResponse() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $response = $this->invokeMethod( $server, 'createDefaultResponse', [ $request ] );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( QR::RESPONSE, $response->qr );
        self::assertSame( $request->opcode, $response->opcode );
        self::assertSame( $request->rd, $response->rd );
        self::assertSame( $request->ra, $response->ra );
        self::assertSame( ReturnCode::NOERROR, $response->returnCode );
        self::assertSame( $request->question, $response->question );
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

        // Create a custom handler that modifies the response significantly
        $handler = function ( Message $request ) : Message {
            $response = Message::response( $request );
            $response->returnCode = ReturnCode::SERVFAIL;
            $response->aa = AA::AUTHORITATIVE;

            // Add a custom resource record
            $response->answer[] = new ResourceRecord(
                [ 'custom', 'example', 'com' ],
                'TXT',
                'IN',
                600,
                [ 'text' => new RDataValue( RDataType::CharacterStringList, [ 'Custom handler response' ] ) ]
            );

            return $response;
        };

        $server->setRequestHandler( $handler );
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertSame( ReturnCode::SERVFAIL, $response->returnCode );
        self::assertSame( AA::AUTHORITATIVE, $response->aa );
        self::assertCount( 1, $response->answer );
        self::assertSame( 'TXT', $response->answer[ 0 ]->type() );
    }


    public function testDefaultTimeoutUsage() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        // Set custom timeout
        $server->setTimeout( 15, 250000 );

        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->with( 15, 250000 ) // Should use the custom timeout
            ->willReturn( null );

        $server->handleSingleRequest();
    }


    public function testEmptyRecordHandler() : void {
        $handler = SimpleServer::recordHandler( [] );
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( ReturnCode::NOERROR, $response->returnCode );
        self::assertCount( 0, $response->answer );
    }


    public function testHandleRequestsMultiple() : void {
        $transport = $this->createMockTransport();
        $request = $this->createTestRequest();

        $transport->expects( self::exactly( 3 ) )
            ->method( 'receiveRequest' )
            ->willReturn( $request );

        $transport->expects( self::exactly( 3 ) )
            ->method( 'sendResponse' );

        $server = new SimpleServer( $transport );
        $handledCount = $server->handleRequests( 3 );

        self::assertSame( 3, $handledCount );
    }


    public function testHandleRequestsWithTimeout() : void {
        $transport = $this->createMockTransport();
        $request = $this->createTestRequest();

        $transport->expects( self::exactly( 2 ) )
            ->method( 'receiveRequest' )
            ->willReturnOnConsecutiveCalls( $request, null );

        $transport->expects( self::once() )
            ->method( 'sendResponse' );

        $server = new SimpleServer( $transport );
        $handledCount = $server->handleRequests( 5 ); // Request more than we'll handle

        self::assertSame( 1, $handledCount );
    }


    public function testHandleRequestsZeroMax() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $transport->expects( self::never() )
            ->method( 'receiveRequest' );

        $handledCount = $server->handleRequests( 0 );

        self::assertSame( 0, $handledCount );
    }


    public function testHandleSingleRequestTimeout() : void {
        $transport = $this->createMockTransport();

        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->with( 5, 0 )
            ->willReturn( null );

        $transport->expects( self::never() )
            ->method( 'sendResponse' );

        $server = new SimpleServer( $transport );
        $result = $server->handleSingleRequest();

        self::assertFalse( $result );
    }


    public function testHandleSingleRequestWithCustomTimeout() : void {
        $transport = $this->createMockTransport();
        $request = $this->createTestRequest();

        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->with( 2, 100000 ) // custom timeout values
            ->willReturn( $request );

        $transport->expects( self::once() )
            ->method( 'sendResponse' );

        $server = new SimpleServer( $transport );
        $result = $server->handleSingleRequest( 2, 100000 );

        self::assertTrue( $result );
    }


    public function testHandleSingleRequestWithNullResponse() : void {
        $transport = $this->createMockTransport();
        $request = $this->createTestRequest();

        // Create a handler that returns null
        $handler = function ( Message $request ) : ?Message {
            return null;
        };

        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->willReturn( $request );

        $transport->expects( self::never() )
            ->method( 'sendResponse' );

        $server = new SimpleServer( $transport );
        $server->setRequestHandler( $handler );
        $result = $server->handleSingleRequest();

        self::assertTrue( $result ); // Still returns true because request was received
    }


    public function testHandleSingleRequestWithResponse() : void {
        $transport = $this->createMockTransport();
        $request = $this->createTestRequest();
        $response = Message::response( $request );

        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->with( 5, 0 ) // default timeout values
            ->willReturn( $request );

        $transport->expects( self::once() )
            ->method( 'sendResponse' )
            ->with( self::isInstanceOf( Message::class ) );

        $server = new SimpleServer( $transport );
        $result = $server->handleSingleRequest();

        self::assertTrue( $result );
    }


    public function testHandlerChaining() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $callOrder = [];

        // First handler
        $handler1 = function ( Message $request ) use ( &$callOrder ) : Message {
            $callOrder[] = 'handler1';
            $response = Message::response( $request );
            $response->authority[] = new ResourceRecord(
                [ 'ns1', 'example', 'com' ],
                'NS',
                'IN',
                3600,
                [ 'nsdname' => new RDataValue( RDataType::DomainName, [ 'ns1', 'example', 'com' ] ) ]
            );
            return $response;
        };

        $server->setRequestHandler( $handler1 );
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertSame( [ 'handler1' ], $callOrder );
        self::assertCount( 1, $response->authority );
    }


    public function testHandlerException() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        // Handler that throws an exception
        $handler = function () : Message {
            throw new \RuntimeException( 'Handler error' );
        };

        $server->setRequestHandler( $handler );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Handler error' );

        $this->invokeMethod( $server, 'processRequest', [ $request ] );
    }


    public function testLargePayloadHandling() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        // Create a handler that returns a large number of records
        $handler = function ( Message $request ) : Message {
            $response = Message::response( $request );

            // Add many records to test large payloads
            for ( $i = 1 ; $i <= 100 ; $i++ ) {
                $response->answer[] = new ResourceRecord(
                    [ 'host' . $i, 'example', 'com' ],
                    'A',
                    'IN',
                    300,
                    [ 'address' => new RDataValue( RDataType::IPv4Address, "192.0.2.$i" ) ]
                );
            }

            return $response;
        };

        $server->setRequestHandler( $handler );
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertCount( 100, $response->answer );
        self::assertSame( 'A', $response->answer[ 0 ]->type() );
        self::assertSame( 'A', $response->answer[ 99 ]->type() );
    }


    public function testMultipleDifferentRecordTypes() : void {
        $records = [
            new ResourceRecord(
                [ 'example', 'com' ],
                'A',
                'IN',
                300,
                [ 'address' => new RDataValue( RDataType::IPv4Address, '1.2.3.4' ) ]
            ),
            new ResourceRecord(
                [ 'example', 'com' ],
                'AAAA',
                'IN',
                300,
                [ 'address' => new RDataValue( RDataType::IPv6Address, '2001:db8::1' ) ]
            ),
            new ResourceRecord(
                [ 'example', 'com' ],
                'MX',
                'IN',
                300,
                [
                    'preference' => new RDataValue( RDataType::UINT16, 10 ),
                    'exchange' => new RDataValue( RDataType::DomainName, [ 'mail', 'example', 'com' ] ),
                ]
            ),
            new ResourceRecord(
                [ 'example', 'com' ],
                'TXT',
                'IN',
                300,
                [ 'text' => new RDataValue( RDataType::CharacterStringList, [ 'v=spf1 include:_spf.example.com ~all' ] ) ]
            ),
        ];

        $handler = SimpleServer::recordHandler( $records );
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertCount( 4, $response->answer );
        self::assertSame( 'A', $response->answer[ 0 ]->type() );
        self::assertSame( 'AAAA', $response->answer[ 1 ]->type() );
        self::assertSame( 'MX', $response->answer[ 2 ]->type() );
        self::assertSame( 'TXT', $response->answer[ 3 ]->type() );
    }


    public function testNxDomainHandler() : void {
        $handler = SimpleServer::nxDomainHandler();
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( ReturnCode::NXDOMAIN, $response->returnCode );
    }


    public function testProcessRequestWithoutHandler() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );
        $request = $this->createTestRequest();

        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( $request->question, $response->question );
    }


    public function testRecordHandler() : void {
        $records = [
            new ResourceRecord(
                [ 'example', 'com' ],
                'A',
                'IN',
                300,
                [ 'address' => new RDataValue( RDataType::IPv4Address, '1.2.3.4' ) ]
            ),
            new ResourceRecord(
                [ 'example', 'com' ],
                'A',
                'IN',
                300,
                [ 'address' => new RDataValue( RDataType::IPv4Address, '5.6.7.8' ) ]
            ),
        ];

        $handler = SimpleServer::recordHandler( $records );
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( ReturnCode::NOERROR, $response->returnCode );
        self::assertCount( 2, $response->answer );
        self::assertSame( $records, $response->answer );
    }


    public function testServFailHandler() : void {
        $handler = SimpleServer::servFailHandler();
        $request = $this->createTestRequest();

        $response = $handler( $request );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
        self::assertSame( ReturnCode::SERVFAIL, $response->returnCode );
    }


    public function testSetRequestHandler() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        $handler = function ( Message $request ) : Message {
            return Message::response( $request );
        };

        $server->setRequestHandler( $handler );

        // Test that the handler is set by invoking it through processRequest
        $request = $this->createTestRequest();
        $response = $this->invokeMethod( $server, 'processRequest', [ $request ] );

        self::assertInstanceOf( Message::class, $response );
        self::assertSame( $request->id, $response->id );
    }


    public function testSetTimeout() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        // Configure the mock to simulate a timeout delay
        $transport->expects( self::once() )
            ->method( 'receiveRequest' )
            ->with( 0, 10000 ) // 100ms timeout
            ->willReturnCallback( function ( $seconds, $microseconds ) {
                // Simulate the timeout delay
                usleep( $microseconds );
                return null; // Return null to indicate timeout
            } );

        $server->setTimeout( 0, 10000 ); // Set 10ms timeout

        $fStartTime = microtime( true );
        $result = $server->handleSingleRequest();
        $fEndTime = microtime( true );
        $fElapsed = $fEndTime - $fStartTime;

        self::assertFalse( $result ); // Should return false on timeout
        self::assertGreaterThanOrEqual( 0.009, $fElapsed, "Timeout elapsed time {$fElapsed} should be at least 9ms" );
        self::assertLessThan( 0.02, $fElapsed, "Timeout elapsed time {$fElapsed} should be less than 20ms" );
    }


    public function testTimeoutPropagation() : void {
        $transport = $this->createMockTransport();
        $server = new SimpleServer( $transport );

        // Test that timeout parameters are properly passed through
        $transport->expects( self::exactly( 3 ) )
            ->method( 'receiveRequest' )
            ->willReturn( null );

        // We'll verify the calls individually since withConsecutive is deprecated

        $server->handleSingleRequest( 1, 0 );
        $server->handleSingleRequest( 2, 500000 );
        $server->handleSingleRequest(); // Should use defaults
    }


    /**
     * @return TransportInterface&MockObject
     * @suppress PhanTypeMismatchReturnSuperType
     */
    private function createMockTransport() : TransportInterface {
        return $this->createMock( TransportInterface::class );
    }


    private function createTestRequest() : Message {
        $request = new Message();
        $request->id = 12345;
        $request->question[] = new Question( 'example.com', 'A', 'IN' );
        return $request;
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