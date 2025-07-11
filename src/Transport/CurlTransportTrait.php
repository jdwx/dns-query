<?php /** @noinspection PhpComposerExtensionStubsInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


trait CurlTransportTrait {


    public function curlHandle( string $i_stURL ) : \CurlHandle {
        $ch = curl_init( $i_stURL );
        $uTimeoutSeconds = (int) $this->timeout() * 1000;
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $uTimeoutSeconds );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $uTimeoutSeconds );
        return $ch;
    }


    abstract public function timeout() : float;


}