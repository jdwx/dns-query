<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\PseudowireTransport;


require __DIR__ . '/../vendor/autoload.php';

( function () : void {


    # This is aspirational code right now, describing how I want this module to work,
    # not how it actually works.

    $codec = new RFC1035Codec();
    $pseudo = new PseudowireTransport( $codec );

    # Client sends request
    $request = Message::request( 'example.com', 'A' );
    echo HexDump::dump( $codec->encode( $request ) ), "\n";

    $client = new JDWX\DNSQuery\Client\Client( $pseudo );
    $client->sendRequest( $request );

    # Pseudo-server
    $request = $pseudo->receiveRequest();
    echo $request;

    $response = Message::response( $request );
    $response->answer[] = JDWX\DNSQuery\RR\A::make(
        $request->question[ 0 ]->stName,
        i_rData: [ '127.0.0.1' ],
    );
    echo $response;
    echo HexDump::dump( $codec->encode( $response ) ), "\n";
    $pseudo->sendResponse( $response );

    # Client receives response
    $response = $client->receiveResponse();
    echo $response;

} )();