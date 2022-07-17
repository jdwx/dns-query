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
 * @since     File available since Release 1.0.0
 *
 */


/**
 * HIP Resource Record - RFC5205 section 5
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  HIT length   | PK algorithm  |          PK length            |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                                                               |
 *  ~                           HIT                                 ~
 *  |                                                               |
 *  +                     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                     |                                         |
 *  +-+-+-+-+-+-+-+-+-+-+-+                                         +
 *  |                           Public Key                          |
 *  ~                                                               ~
 *  |                                                               |
 *  +                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                               |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               +
 *  |                                                               |
 *  ~                       Rendezvous Servers                      ~
 *  |                                                               |
 *  +             +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |             |
 *  +-+-+-+-+-+-+-+
 *
 */
class HIP extends RR {


    /** @var int Length of the HIT field */
    public int $hitLength;

    /** @var int Public key cryptographic algorithm ID */
    public int $pubkeyAlgorithm;

    /** @var int Length of the public key field */
    public int $pubkeyLength;

    /** @var string HIT is stored as a binary value in network byte order */
    public string $hit;

    /** @var string Public key */
    public string $publicKey;

    /** @var string[] List of rendezvous servers */
    public array $rendezvousServers = [];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->pubkeyAlgorithm = (int) array_shift( $i_rData );
        $this->hit = strtoupper( array_shift( $i_rData ) );
        $this->publicKey = array_shift( $i_rData );

        # Anything left on the array, must be one or more rendezvous servers. Add
        # them and strip off the trailing dot.
        if ( count( $i_rData ) > 0 ) {

            $this->rendezvousServers = preg_replace( '/\.$/', '', $i_rData );
        }

        # Store the lengths.
        $this->hitLength = strlen( pack( 'H*', $this->hit ) );
        $this->pubkeyLength = strlen( base64_decode( $this->publicKey ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( ( strlen( $this->hit ) > 0 ) && ( strlen( $this->publicKey ) > 0 ) ) {

            # Pack the length, algorithm and HIT values.
            $data = pack(
                'CCnH*',
                $this->hitLength,
                $this->pubkeyAlgorithm,
                $this->pubkeyLength,
                $this->hit
            );

            # Add the public key.
            $data .= base64_decode( $this->publicKey );

            # Add the offset.
            $i_packet->offset += strlen( $data );

            # Add each rendezvous server.
            foreach ( $this->rendezvousServers as $server ) {
                $data .= $i_packet->compress( $server, $i_packet->offset );
            }

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the algorithm and length values.
            $parse = unpack( 'ChitLength/CpkAlgorithm/npkLength', $this->rdata );

            $this->hitLength = $parse[ 'hit_length' ];
            $this->pubkeyAlgorithm = $parse[ 'pk_algorithm' ];
            $this->pubkeyLength = $parse[ 'pk_length' ];

            $offset = 4;

            # Copy out the HIT value.
            $hit = unpack( 'H*', substr( $this->rdata, $offset, $this->hitLength ) );

            $this->hit = strtoupper( $hit[ 1 ] );
            $offset += $this->hitLength;

            # Copy out the public key.
            $this->publicKey = base64_encode(
                substr( $this->rdata, $offset, $this->pubkeyLength )
            );
            $offset += $this->pubkeyLength;

            # Copy out any possible rendezvous servers.
            $offset = $i_packet->offset + $offset;

            while ( ( $offset - $i_packet->offset ) < $this->rdLength ) {
                $this->rendezvousServers[] = $i_packet->expandEx( $offset );
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->pubkeyAlgorithm . ' ' .
            $this->hit . ' ' . $this->publicKey . ' ';

        foreach ( $this->rendezvousServers as $server ) {
            $out .= $server . '. ';
        }

        return trim( $out );
    }
}
