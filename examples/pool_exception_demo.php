<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Exceptions\ConnectionException;
use JDWX\DNSQuery\Exceptions\NetworkException;
use JDWX\DNSQuery\Exceptions\ProtocolException;
use JDWX\DNSQuery\Transport\Pool\DohPoolStrategy;
use JDWX\DNSQuery\Transport\Pool\PooledConnection;
use JDWX\DNSQuery\Transport\Pool\TcpPoolStrategy;
use JDWX\DNSQuery\Transport\Pool\UdpPoolStrategy;


require __DIR__ . '/../vendor/autoload.php';


echo "=== Pool Strategy Exception Handling Demo ===\n\n";

// Create mock connection
$mockTransport = new class implements \JDWX\DNSQuery\Transport\TransportInterface {


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        return null;
    }


    public function send( string|\JDWX\DNSQuery\Buffer\WriteBufferInterface $i_data ) : void {}


};

$connection = new PooledConnection( $mockTransport, 'test:127.0.0.1:53', 'test' );

echo "1. UDP Strategy Exception Handling:\n";
$udpStrategy = new UdpPoolStrategy();

$exceptions = [
    new NetworkException( 'Timeout' ),
    new ProtocolException( 'Port unreachable' ),
    new ConnectionException( 'Socket closed' ),
];

foreach ( $exceptions as $exception ) {
    $canReuse = $udpStrategy->handleError( $connection, $exception );
    echo "   - " . get_class( $exception ) . ": " .
        ( $canReuse ? "Can reuse connection" : "Must discard connection" ) . "\n";
}

echo "\n2. TCP Strategy Exception Handling:\n";
$tcpStrategy = new TcpPoolStrategy();

foreach ( $exceptions as $exception ) {
    $canReuse = $tcpStrategy->handleError( $connection, $exception );
    echo "   - " . get_class( $exception ) . ": " .
        ( $canReuse ? "Can reuse connection" : "Must discard connection" ) . "\n";
}

echo "\n3. DoH Strategy Exception Handling:\n";
$dohStrategy = new DohPoolStrategy();
DohPoolStrategy::clearFailureMemory();

foreach ( $exceptions as $exception ) {
    $canReuse = $dohStrategy->handleError( $connection, $exception );
    echo "   - " . get_class( $exception ) . ": " .
        ( $canReuse ? "Can reuse connection" : "Must discard connection" );

    if ( $exception instanceof ProtocolException ) {
        echo " (server marked as not supporting DoH)";
    }
    echo "\n";
}

echo "\n4. Summary:\n";
echo "   - UDP: Only ConnectionException invalidates the socket\n";
echo "   - TCP: Any exception invalidates the connection (stateful protocol)\n";
echo "   - DoH: ProtocolException marks server as unsupported\n";