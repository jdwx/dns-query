<?php


declare( strict_types = 1 );


use JDWX\DNSQuery\Codecs\RFC1035Codec;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\SocketTransport;


require __DIR__ . '/../vendor/autoload.php';


( function () : void {


    # This is aspirational code right now, describing how I want this module to work,
    # not how it actually works.

    $codec = new RFC1035Codec();
    $xpt = SocketTransport::udp( '1.1.1.1' );

    # Client sends request
    $request = Message::request( 'example.com', 'A' );
    echo HexDump::dump( $codec->encode( $request ) ), "\n";
    echo $request;

    $client = new JDWX\DNSQuery\Client\SimpleClient( $xpt, $codec );
    $client->sendRequest( $request );

    /*
    # Pseudo-server
    $request = $xpt->receiveRequest();
    echo $request;

    $response = Message::response( $request );
    $response->answer[] = JDWX\DNSQuery\RR\A::make(
        $request->question[ 0 ]->stName,
        i_rData: [ '127.0.0.1' ],
    );
    echo $response;
    echo HexDump::dump( $codec->encode( $response ) ), "\n";
    $xpt->sendResponse( $response );
    */

    # Client receives response
    $response = $client->receiveResponse();
    if ( ! $response instanceof Message ) {
        echo "No response received.\n";
        return;
    }
    echo HexDump::dump( $codec->encode( $response ) ), "\n";
    echo $response;

} )();