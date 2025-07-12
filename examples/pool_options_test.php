<?php

declare( strict_types = 1 );


use JDWX\DNSQuery\Transport\Pool\TransportPool;


require __DIR__ . '/../vendor/autoload.php';


echo "=== Transport Pool Options Test ===\n\n";

$pool = TransportPool::default();

// Test with various options
$options = [
    'timeout_seconds' => 5,
    'timeout_microseconds' => 500000,
    'local_address' => '0.0.0.0',
    'local_port' => 0,
];

echo "1. Testing UDP with options:\n";
try {
    $transport = $pool->acquire( 'udp', '8.8.8.8', 53, $options );
    echo "   ✓ UDP transport created successfully\n";
    $pool->release( $transport );
} catch ( \Exception $e ) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing TCP with options:\n";
try {
    $transport = $pool->acquire( 'tcp', '8.8.8.8', 53, $options );
    echo "   ✓ TCP transport created successfully\n";
    $pool->release( $transport );
} catch ( \Exception $e ) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing HTTPS with method option:\n";
$httpsOptions = [
    'method' => 'get',
    'timeout_seconds' => 10,
];
try {
    $transport = $pool->acquire( 'https', '1.1.1.1', 443, $httpsOptions );
    echo "   ✓ HTTPS GET transport created successfully\n";
    $pool->release( $transport );
} catch ( \Exception $e ) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing HTTPS POST (default):\n";
$httpsPostOptions = [
    'timeout_seconds' => 10,
];
try {
    $transport = $pool->acquire( 'https', '1.1.1.1', 443, $httpsPostOptions );
    echo "   ✓ HTTPS POST transport created successfully\n";
    $pool->release( $transport );
} catch ( \Exception $e ) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\nPool statistics:\n";
echo "- Total connections: " . $pool->count() . "\n";
echo "- Active: " . $pool->getActiveCount() . "\n";
echo "- Idle: " . $pool->getIdleCount() . "\n";

echo "\nDone!\n";