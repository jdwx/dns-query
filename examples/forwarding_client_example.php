<?php

declare( strict_types = 1 );


use JDWX\DNSQuery\Client\ForwardingClient;
use JDWX\DNSQuery\Message\Message;


require __DIR__ . '/../vendor/autoload.php';


// Set up HTTP client for DoH (if available)
if ( class_exists( 'GuzzleHttp\Client' ) ) {
    \JDWX\DNSQuery\Transport\TransportFactory::setHttpClient(
        new \GuzzleHttp\Client(),
        new \GuzzleHttp\Psr7\HttpFactory()
    );
}

echo "=== DNS Forwarding Client Demo ===\n\n";

// Create a forwarding client for Cloudflare DNS
$client = new ForwardingClient( '1.1.1.1' );

echo "1. Testing with HTTPS enabled (default):\n";
$request = Message::request( 'example.com', 'A' );

try {
    $client->sendRequest( $request );
    $response = $client->receiveResponse();
    
    if ( $response ) {
        echo "   Response received via HTTPS:\n";
        foreach ( $response->getAnswer() as $answer ) {
            if ( $answer->type() === 'A' ) {
                echo "   - " . $answer->tryGetRDataValue( 'address' ) . "\n";
            }
        }
    }
} catch ( \Exception $e ) {
    echo "   HTTPS failed: " . $e->getMessage() . "\n";
}

echo "\n2. Simulating HTTPS failure (using non-HTTPS server):\n";
// Use a server that likely doesn't support DNS over HTTPS
$client2 = new ForwardingClient( '8.8.8.8' );

try {
    $client2->sendRequest( $request );
    $response = $client2->receiveResponse();
    
    if ( $response ) {
        echo "   Response received (fallback to UDP):\n";
        foreach ( $response->getAnswer() as $answer ) {
            if ( $answer->type() === 'A' ) {
                echo "   - " . $answer->tryGetRDataValue( 'address' ) . "\n";
            }
        }
    }
} catch ( \Exception $e ) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Try again - should go straight to UDP this time
echo "\n3. Second query to same server (should skip HTTPS):\n";
try {
    $client2->sendRequest( Message::request( 'google.com', 'A' ) );
    $response = $client2->receiveResponse();
    
    if ( $response ) {
        echo "   Response received (direct UDP, no HTTPS attempt):\n";
        foreach ( $response->getAnswer() as $answer ) {
            if ( $answer->type() === 'A' ) {
                echo "   - " . $answer->tryGetRDataValue( 'address' ) . "\n";
            }
        }
    }
} catch ( \Exception $e ) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing TC flag handling:\n";
// Query for a large response that might be truncated
$largeDnsQuery = Message::request( 'google.com', 'ANY' );

$client3 = new ForwardingClient( '8.8.8.8' );
$client3->disableHttps(); // Use UDP to potentially get TC flag

try {
    $client3->sendRequest( $largeDnsQuery );
    $response = $client3->receiveResponse();
    
    if ( $response ) {
        $tc = $response->header()->tc();
        echo "   Response received, TC flag: " . $tc . "\n";
        echo "   Answer count: " . count( $response->getAnswer() ) . "\n";
        
        if ( $tc === 'TRUNCATED' ) {
            echo "   (Client automatically retried with TCP)\n";
        }
    }
} catch ( \Exception $e ) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n5. Manual HTTPS control:\n";
$client4 = new ForwardingClient( '1.1.1.1' );

// Disable HTTPS
$client4->disableHttps();
echo "   HTTPS disabled - using UDP\n";

$client4->sendRequest( Message::request( 'cloudflare.com', 'A' ) );
$response = $client4->receiveResponse();
if ( $response ) {
    echo "   Got " . count( $response->getAnswer() ) . " answers via UDP\n";
}

// Re-enable HTTPS
$client4->enableHttps();
echo "   HTTPS re-enabled\n";

$client4->sendRequest( Message::request( 'cloudflare.com', 'AAAA' ) );
$response = $client4->receiveResponse();
if ( $response ) {
    echo "   Got " . count( $response->getAnswer() ) . " answers (via HTTPS if supported)\n";
}

echo "\nDone!\n";