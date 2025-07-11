<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Client\PooledClient;
use JDWX\DNSQuery\Message\Message;


require __DIR__ . '/../vendor/autoload.php';


( function () : void {

    // Create a pooled client
    $client = new PooledClient();

    // Get the pool for statistics
    $pool = $client->getPool();

    echo "=== DNS Query with Connection Pooling ===\n\n";

    // Query multiple domains using the same nameserver
    $domains = [ 'example.com', 'google.com', 'cloudflare.com' ];
    $nameserver = '1.1.1.1';

    foreach ( $domains as $domain ) {
        echo "Querying {$domain} via {$nameserver}...\n";

        $request = Message::request( $domain, 'A' );

        // This will reuse the connection from the pool
        $client->sendRequestTo( $request, $nameserver );
        $response = $client->receiveResponse();

        if ( $response ) {
            echo "Response for {$domain}:\n";
            foreach ( $response->getAnswer() as $answer ) {
                if ( $answer->type() === 'A' ) {
                    echo '  - ' . $answer->tryGetRDataValue( 'address' ) . "\n";
                }
            }
        } else {
            echo "  - No response\n";
        }

        echo "\n";
    }

    // Show pool statistics
    echo "Pool Statistics:\n";
    echo '  Total connections: ' . $pool->count() . "\n";
    echo '  Active connections: ' . $pool->getActiveCount() . "\n";
    echo '  Idle connections: ' . $pool->getIdleCount() . "\n\n";

    // Try different nameservers
    echo "=== Querying different nameservers ===\n";
    $nameservers = [ '8.8.8.8', '1.1.1.1', '9.9.9.9' ];

    foreach ( $nameservers as $ns ) {
        $request = Message::request( 'example.com', 'A' );
        $client->sendRequestTo( $request, $ns );
        $response = $client->receiveResponse();

        echo "Response from {$ns}: ";
        if ( $response && count( $response->getAnswer() ) > 0 ) {
            echo 'OK (' . count( $response->getAnswer() ) . " answers)\n";
        } else {
            echo "No answers\n";
        }
    }

    echo "\nFinal Pool Statistics:\n";
    echo '  Total connections: ' . $pool->count() . "\n";
    echo '  Active connections: ' . $pool->getActiveCount() . "\n";
    echo '  Idle connections: ' . $pool->getIdleCount() . "\n";

    // Clean up
    $client->release();


} )();
