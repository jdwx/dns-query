<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Packet\Packet;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 0.6.0
 *
 */


/**
 * DNSKEY Resource Record - RFC4034 section 2.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |              Flags            |    Protocol   |   Algorithm   |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Public Key                         /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class DNSKEY extends RR {


    /** @var int Flags */
    public int $flags;

    /** @var int Protocol */
    public int $protocol;

    /** @var int Algorithm used */
    public int $algorithm;

    /** @var string Public key */
    public string $key;

    /** @var int Calculated key tag */
    public int $keytag;


    /**
     * compute keytag from rdata (rfc4034)
     * (invalid for algorithm 1, but it's not recommended)
     *
     * @return int
     */
    protected function getKeyTag() : int {
        $key = array_values( unpack( "C*", $this->rdata ) );
        $keySize = $this->rdLength;

        $ac = 0;
        for ( $ii = 0 ; $ii < $keySize ; $ii++ )
            $ac += ( $ii & 1 ) ? $key[ $ii ] : $key[ $ii ] << 8;

        $ac += ( $ac >> 16 ) & 0xFFFF;
        return $ac & 0xFFFF;
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->flags = (int) array_shift( $i_rData );
        $this->protocol = (int) array_shift( $i_rData );
        $this->algorithm = (int) array_shift( $i_rData );
        $this->key = implode( ' ', $i_rData );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->key ) > 0 ) {

            $data = pack( 'nCC', $this->flags, $this->protocol, $this->algorithm );
            $data .= base64_decode( $this->key );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the flags, protocol and algorithm.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nflags/Cprotocol/Calgorithm', $this->rdata );

            # TODO: right now we're just displaying what's in DNS; we really
            # should be parsing bit 7 and bit 15 of the flags field, and store
            # those separately.
            #
            # Right now the DNSSEC implementation is really just for display,
            # we don't validate or handle any of the keys.
            $this->flags = $parse[ 'flags' ];
            $this->protocol = $parse[ 'protocol' ];
            $this->algorithm = $parse[ 'algorithm' ];

            $this->key = base64_encode( substr( $this->rdata, 4 ) );

            $this->keytag = $this->getKeyTag();

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->flags . ' ' . $this->protocol . ' ' .
            $this->algorithm . ' ' . $this->key;
    }


}
