<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Transport\Pool\DohPoolStrategy;
use JDWX\DNSQuery\Transport\Pool\TransportPool;


require __DIR__ . '/../vendor/autoload.php';


echo "=== Transport Pool Behavior Demo ===\n\n";

$pool = TransportPool::default();

echo "1. UDP Behavior (connectionless, no timeout):\n";
try {
    $udp1 = $pool->acquire( 'udp', '8.8.8.8' );
    echo "   - Acquired UDP transport\n";
    $pool->release( $udp1 );
    echo "   - Released back to pool\n";

    // Wait a bit
    sleep( 1 );

    $udp2 = $pool->acquire( 'udp', '8.8.8.8' );
    echo "   - Reacquired same transport (no timeout for UDP)\n";
    $pool->release( $udp2 );
} catch ( Exception $e ) {
    echo '   - Error: ' . $e->getMessage() . "\n";
}

echo "\n2. TCP Behavior (stateful, 2-minute timeout):\n";
try {
    $tcp1 = $pool->acquire( 'tcp', '8.8.8.8' );
    echo "   - Acquired TCP transport\n";
    $pool->release( $tcp1 );
    echo "   - Released back to pool\n";

    // TCP should still be reusable immediately
    $tcp2 = $pool->acquire( 'tcp', '8.8.8.8' );
    echo "   - Reacquired same transport (within timeout)\n";
    $pool->release( $tcp2 );
} catch ( Exception $e ) {
    echo '   - Error: ' . $e->getMessage() . "\n";
}

echo "\n3. DoH Behavior (server-level failure tracking):\n";
try {
    // Clear any previous failures
    DohPoolStrategy::clearFailureMemory();

    $doh = $pool->acquire( 'doh', '8.8.8.8' );
    echo "   - Would acquire DoH transport (not implemented yet)\n";
} catch ( RuntimeException $e ) {
    echo '   - Expected error: ' . $e->getMessage() . "\n";
}

echo "\n4. Error Handling:\n";
echo "   - UDP errors don't invalidate the connection\n";
echo "   - TCP errors immediately invalidate the connection\n";
echo "   - DoH failures are remembered at the server level\n";

echo "\nPool Statistics:\n";
echo '  Total connections: ' . $pool->count() . "\n";
echo '  Active connections: ' . $pool->getActiveCount() . "\n";
echo '  Idle connections: ' . $pool->getIdleCount() . "\n";