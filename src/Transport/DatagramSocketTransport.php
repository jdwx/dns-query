<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;


class DatagramSocketTransport extends AbstractSocketTransport {


    /**
     * @param int $i_uBufferSize
     * @return string|null
     *
     * If this method returns null, you should assume that the socket is
     * closed or otherwise unusable.
     */
    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        return $this->read( $i_uBufferSize );
    }


    public function send( string|WriteBufferInterface $i_stData ) : void {
        if ( $i_stData instanceof WriteBufferInterface ) {
            $i_stData = $i_stData->end();
        }
        parent::send( $i_stData );
    }


}
