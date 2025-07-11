<?php


declare( strict_types = 1 );


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use JDWX\DNSQuery\Codecs\RFC1035Decoder;
use JDWX\DNSQuery\Codecs\RFC1035Encoder;
use JDWX\DNSQuery\HexDump;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\TransportFactory;


require __DIR__ . '/../vendor/autoload.php';


/** @suppress PhanTypeSuspiciousEcho */
( static function ( array $argv ) : void {

    TransportFactory::setHttpClient(
        new Client(),
        new HttpFactory()
    );

    array_shift( $argv ); // Remove script name
    $stProtocol = array_shift( $argv ) ?? 'udp';
    $stHost = array_shift( $argv ) ?? '1.1.1.1';
    $stQuestionHost = array_shift( $argv ) ?? 'www.example.com';
    $stQuestionType = array_shift( $argv ) ?? 'A';

    $codec = new JDWX\DNSQuery\Codecs\Codec( new RFC1035Encoder(), new RFC1035Decoder() );
    /** @noinspection SpellCheckingInspection */
    $xpt = match ( strtolower( trim( $stProtocol ) ) ) {
        'https', 'httpspost' => TransportFactory::httpsPost( $stHost ),
        'httpsget' => TransportFactory::httpsGet( $stHost ),
        'tcp' => TransportFactory::tcp( $stHost ),
        'udp' => TransportFactory::udp( $stHost ),
        'unix' => TransportFactory::unix( $stHost, SOCK_DGRAM ),
        default => throw new InvalidArgumentException( "Unknown protocol: {$stProtocol}" ),
    };
    echo 'Using transport: ', $xpt::class, "\n";

    # Client sends request
    $request = Message::request( $stQuestionHost, $stQuestionType );
    echo HexDump::dump( $codec->encodeMessage( $request )->end() ), "\n";
    echo $request;

    $client = new JDWX\DNSQuery\Client\SimpleClient( $xpt, $codec );
    $client->sendRequest( $request );

    # Client receives response
    $response = $client->receiveResponse();
    if ( ! $response instanceof Message ) {
        echo "No response received.\n";
        return;
    }
    echo HexDump::dump( $codec->encodeMessage( $response )->end() ), "\n";
    echo $response;

} )( $argv );