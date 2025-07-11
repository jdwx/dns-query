<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\Strict\TypeIs;


/**
 * Simple transport useful for testing and emulation.
 */
class PseudowireTransport implements TransportInterface {


    /** @var list<string> */
    private array $rSendBuffer = [];

    /** @var list<string> */
    private array $rReceiveBuffer = [];


    public function __construct() {}


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        if ( empty( $this->rReceiveBuffer ) ) {
            return null;
        }
        $st = TypeIs::string( array_shift( $this->rReceiveBuffer ) );
        if ( strlen( $st ) > $i_uBufferSize ) {
            $stRest = substr( $st, $i_uBufferSize );
            array_unshift( $this->rReceiveBuffer, $stRest );
            $st = substr( $st, 0, $i_uBufferSize );
        }
        return $st;
    }


    public function receiveFarEnd() : ?string {
        return array_shift( $this->rSendBuffer );
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        if ( $i_data instanceof WriteBufferInterface ) {
            $i_data = $i_data->end();
        }
        $this->rSendBuffer[] = $i_data;
    }


    public function sendFarEnd( string $i_stData ) : void {
        $this->rReceiveBuffer[] = $i_stData;
    }


}
