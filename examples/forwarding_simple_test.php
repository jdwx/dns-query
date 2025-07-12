<?php

declare( strict_types = 1 );


use JDWX\DNSQuery\Client\ForwardingClient;
use JDWX\DNSQuery\Message\Message;


require __DIR__ . '/../vendor/autoload.php';


echo "=== Simple ForwardingClient Test ===\n\n";

// Create client without HTTPS (just UDP/TCP)
$client = new ForwardingClient( '8.8.8.8' );
$client->disableHttps();

echo "Testing basic UDP query:\n";
$request = Message::request( 'example.com', 'A' );

try {
    $client->sendRequest( $request );
    $response = $client->receiveResponse();
    
    if ( $response ) {
        echo "Got response!\n";
        echo "- Answers: " . count( $response->getAnswer() ) . "\n";
        foreach ( $response->getAnswer() as $answer ) {
            if ( $answer->type() === 'A' ) {
                echo "- IP: " . $answer->tryGetRDataValue( 'address' ) . "\n";
            }
        }
    } else {
        echo "No response received\n";
    }
} catch ( \Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nPool statistics:\n";
$pool = $client->getPool();
echo "- Total connections: " . $pool->count() . "\n";
echo "- Active: " . $pool->getActiveCount() . "\n";
echo "- Idle: " . $pool->getIdleCount() . "\n";