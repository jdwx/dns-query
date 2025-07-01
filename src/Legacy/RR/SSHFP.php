<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;


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
 * SSHFP Resource Record - RFC4255 section 3.1
 *
 *       0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *      |   algorithm   |    fp type    |                               /
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *      /                                                               /
 *      /                          fingerprint                          /
 *      /                                                               /
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class SSHFP extends RR {


    /** The algorithm used */
    public const int SSHFP_ALGORITHM_RES = 0;

    public const int SSHFP_ALGORITHM_RSA = 1;

    /** The fingerprint data */
    public const int SSHFP_ALGORITHM_DSS = 2;

    /** Algorithms */
    public const int SSHFP_ALGORITHM_ECDSA   = 3;

    public const int SSHFP_ALGORITHM_ED25519 = 4;

    public const int SSHFP_FPTYPE_RES        = 0;

    public const int SSHFP_FPTYPE_SHA1       = 1;

    public const int SSHFP_FPTYPE_SHA256     = 2;

    /** @var int ID of algorithm */
    public int $algorithm;

    /** @var int Fingerprint type */
    public int $fpType;

    /** @var string Fingerprint */
    public string $fingerprint;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # "The use of mnemonics instead of numbers is not allowed."
        #
        # RFC4255 section 3.2
        $algorithm = (int) array_shift( $i_rData );
        $fpType = (int) array_shift( $i_rData );
        $fingerprint = strtolower( implode( '', $i_rData ) );

        # There are only four algorithms defined.
        if ( ( $algorithm != self::SSHFP_ALGORITHM_RSA )
            && ( $algorithm != self::SSHFP_ALGORITHM_DSS )
            && ( $algorithm != self::SSHFP_ALGORITHM_ECDSA )
            && ( $algorithm != self::SSHFP_ALGORITHM_ED25519 )
        ) {
            return false;
        }

        # There are only two fingerprints defined.
        if ( ( $fpType != self::SSHFP_FPTYPE_SHA1 )
            && ( $fpType != self::SSHFP_FPTYPE_SHA256 )
        ) {
            return false;
        }

        $this->algorithm = $algorithm;
        $this->fpType = $fpType;
        $this->fingerprint = $fingerprint;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->fingerprint ) > 0 ) {

            $data = pack(
                'CCH*', $this->algorithm, $this->fpType, $this->fingerprint
            );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the algorithm and fingerprint type.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Calgorithm/Cfp_type', $this->rdata );

            $this->algorithm = $parse[ 'algorithm' ];
            $this->fpType = $parse[ 'fp_type' ];

            # There are only four algorithms defined.
            if ( ( $this->algorithm != self::SSHFP_ALGORITHM_RSA )
                && ( $this->algorithm != self::SSHFP_ALGORITHM_DSS )
                && ( $this->algorithm != self::SSHFP_ALGORITHM_ECDSA )
                && ( $this->algorithm != self::SSHFP_ALGORITHM_ED25519 )
            ) {
                return false;
            }

            # There are only two fingerprints defined.
            if ( ( $this->fpType != self::SSHFP_FPTYPE_SHA1 )
                && ( $this->fpType != self::SSHFP_FPTYPE_SHA256 )
            ) {
                return false;
            }

            # Parse the fingerprint; this assumes SHA-1.
            $fp = unpack( 'H*a', substr( $this->rdata, 2 ) );
            $this->fingerprint = strtolower( $fp[ 'a' ] );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->algorithm . ' ' . $this->fpType . ' ' . $this->fingerprint;
    }


}


