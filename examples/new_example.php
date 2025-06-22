<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\PseudowireTransport;
use JDWX\DNSQuery\Transport\SerializeCodec;


require __DIR__ . '/../vendor/autoload.php';

( function () : void {


    # This is aspirational code right now, describing how I want this module to work,
    # not how it actually works.

    # Client sends request
    $request = Message::request( 'example.com' );
    $pseudo = new PseudowireTransport( new SerializeCodec() );
    $client = new JDWX\DNSQuery\Client\Client( $pseudo );
    $client->sendRequest( $request );

    # Pseudo-server
    $request = $pseudo->receiveRequest();
    var_dump( $request );
    $response = Message::response( $request );
    $response->answer[] = JDWX\DNSQuery\RR\A::make(
        $request->question[ 0 ]->stName,
        i_rData: [ '127.0.0.1' ],
    );
    $pseudo->sendResponse( $response );

    # Client receives response
    $response = $client->receiveResponse();
    var_dump( $response );

} )();