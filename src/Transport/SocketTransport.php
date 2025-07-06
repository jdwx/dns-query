<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Exceptions\TransportException;


class SocketTransport extends AbstractSocketTransport {


    private bool $bIsStream = false;


    /**
     * @param int $i_uBufferSize
     * @return string|null
     *
     * If this method returns null, you should assume that the socket is
     * closed or otherwise unusable.
     */
    public function receive( int $i_uBufferSize = 65_536 ) : ?string {

        if ( $this->bIsStream ) {
            // For TCP, first read the 2-byte length prefix
            $lengthData = $this->readTimed( 2 );
            if ( strlen( $lengthData ) < 2 ) {
                return null;
            }

            // Unpack the length (network byte order)
            $length = Binary::unpackUINT16( $lengthData );
            if ( $length > $i_uBufferSize ) {
                throw new TransportException( 'Received data length exceeds buffer size' );
            }

            $data = $this->readTimed( $length );
            return ( strlen( $data ) < $length ) ? null : $data;
        }

        return $this->readTimed( $i_uBufferSize );
    }


    public function send( string|WriteBufferInterface $i_stData ) : void {
        if ( $i_stData instanceof WriteBufferInterface ) {
            $i_stData = $i_stData->end();
        }

        if ( $this->bIsStream ) {
            // For TCP, prepend the 2-byte length prefix
            $length = strlen( $i_stData );
            $i_stData = pack( 'n', $length ) . $i_stData;
        }

        parent::send( $i_stData );
    }


}
