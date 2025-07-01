<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;


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
 * NSEC3PARAM Resource Record - RFC5155 section 4.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   Hash Alg.   |     Flags     |          Iterations           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Salt Length  |                     Salt                      /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC3PARAM extends RR {


    /** @var int Algorithm to use
     *
     * TODO: same as the NSEC3
     */
    public int $algorithm;

    /** @var int Flags */
    public int $flags;

    /** @var int Defines the number of additional times the hash is performed */
    public int $iterations;

    /** @var int Length of the salt (not displayed) */
    public int $saltLength;

    /** @var string The salt */
    public string $salt;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->algorithm = (int) array_shift( $i_rData );
        $this->flags = (int) array_shift( $i_rData );
        $this->iterations = (int) array_shift( $i_rData );

        $salt = array_shift( $i_rData );
        if ( $salt == '-' ) {

            $this->saltLength = 0;
            $this->salt = '';
        } else {

            $this->saltLength = strlen( pack( 'H*', $salt ) );
            $this->salt = strtoupper( $salt );
        }

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $salt = pack( 'H*', $this->salt );
        $this->saltLength = strlen( $salt );

        $data = pack(
                'CCnC',
                $this->algorithm, $this->flags, $this->iterations, $this->saltLength
            ) . $salt;

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Calgorithm/Cflags/niterations/Csalt_length', $this->rdata );

            $this->algorithm = $parse[ 'algorithm' ];
            $this->flags = $parse[ 'flags' ];
            $this->iterations = $parse[ 'iterations' ];
            $this->saltLength = $parse[ 'salt_length' ];

            if ( $this->saltLength > 0 ) {

                $parse = unpack( 'H*', substr( $this->rdata, 5, $this->saltLength ) );
                $this->salt = strtoupper( $parse[ 1 ] );
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->algorithm . ' ' . $this->flags . ' ' . $this->iterations . ' ';

        # Per RFC5155, the salt_length value isn't displayed, and if the salt
        # is empty, the salt is displayed as "-"
        if ( $this->saltLength > 0 ) {
            $out .= $this->salt;
        } else {
            $out .= '-';
        }

        return $out;
    }


}
