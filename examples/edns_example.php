#!/usr/bin/env php
<?php


declare( strict_types = 1 );


require_once __DIR__ . '/../vendor/autoload.php';


use JDWX\DNSQuery\Buffer\WriteBuffer;
use JDWX\DNSQuery\Client\SimpleClient;
use JDWX\DNSQuery\Codecs\Codec;
use JDWX\DNSQuery\Codecs\RFC1035Decoder;
use JDWX\DNSQuery\Codecs\RFC1035Encoder;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Transport\TransportFactory;


( function () : void {
// Create an EDNS-enabled DNS client
    $transport = TransportFactory::udp( '1.1.1.1' );
    $dec = new RFC1035Decoder();
    $enc = new RFC1035Encoder();
    $codec = new Codec( $enc, $dec );
    $client = new SimpleClient( $transport, $codec );

// Create an EDNS query with a larger payload size and DNSSEC OK bit
    $query = EDNSMessage::request(
        'example.com',
        'A',
        'IN',
        payloadSize: 4096,     // Support larger responses
    );

// You can add various EDNS options here
// For example, to add a cookie option (though this is just an example):
// $query->addOption( OptionCode::COOKIE, 'client-cookie-data' );

    echo 'Sending EDNS query with payload size ' . $query->getPayloadSize() . " and DNSSEC OK\n";
    echo $query . "\n";

// Show the encoded query
    $wri = new WriteBuffer();
    $enc->encodeMessage( $wri, $query );
    $encoded = $wri->end();
    echo 'Encoded query (' . strlen( $encoded ) . " bytes):\n";
    echo HexDump::dump( $encoded ) . "\n";

// Send the query
    $client->sendRequest( $query );

// Receive the response (with default 5 second timeout)
    $response = $client->receiveResponse();

    if ( $response ) {
        echo "Received response:\n";
        echo $response . "\n";

        // Check if the response is EDNS-enabled
        if ( $response instanceof EDNSMessage ) {
            echo "EDNS Information:\n";
            echo '- Payload Size: ' . $response->getPayloadSize() . "\n";
            echo '- EDNS Version: ' . $response->getVersion()->value . "\n";
            echo '- DNSSEC OK: ' . ( $response->getDo()->value ? 'Yes' : 'No' ) . "\n";

            $options = $response->getOptions();
            if ( count( $options ) > 0 ) {
                echo "- Options:\n";
                foreach ( $options as $option ) {
                    echo '  - Code ' . $option->code . ': ' . bin2hex( $option->data ) . "\n";
                }
            }
        } else {
            echo "Server did not return an EDNS response.\n";
        }
    } else {
        echo "No response received.\n";
    }

} )();