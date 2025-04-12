<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
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
 * CERT Resource Record - RFC4398 section 2
 *
 *  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |            format             |             key tag           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   algorithm   |                                               /
 *  +---------------+            certificate or CRL                 /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-|
 *
 */
class CERT extends RR {


    # Formats allowed for certificates
    public const CERT_FORMAT_RES     = 0;

    public const CERT_FORMAT_PKIX    = 1;

    public const CERT_FORMAT_SPKI    = 2;

    public const CERT_FORMAT_PGP     = 3;

    public const CERT_FORMAT_IPKIX   = 4;

    public const CERT_FORMAT_ISPKI   = 5;

    public const CERT_FORMAT_IPGP    = 6;

    public const CERT_FORMAT_ACPKIX  = 7;

    public const CERT_FORMAT_IACPKIX = 8;

    public const CERT_FORMAT_URI     = 253;

    public const CERT_FORMAT_OID     = 254;

    /** @var array<string, int> Map format names to IDs */
    public array $certFormatNameToId = [];

    /** @var array<int, string> Map format IDs to names */
    public array $certFormatIdToName = [
        self::CERT_FORMAT_RES => 'Reserved',
        self::CERT_FORMAT_PKIX => 'PKIX',
        self::CERT_FORMAT_SPKI => 'SPKI',
        self::CERT_FORMAT_PGP => 'PGP',
        self::CERT_FORMAT_IPKIX => 'IPKIX',
        self::CERT_FORMAT_ISPKI => 'ISPKI',
        self::CERT_FORMAT_IPGP => 'IPGP',
        self::CERT_FORMAT_ACPKIX => 'ACPKIX',
        self::CERT_FORMAT_IACPKIX => 'IACPKIX',
        self::CERT_FORMAT_URI => 'URI',
        self::CERT_FORMAT_OID => 'OID',
    ];

    /** @var int Certificate format */
    public int $format;

    /** @var int Key tag */
    public int $keytag;

    /** @var int Algorithm used for the CERT */
    public int $algorithm;

    /** @var string Certificate */
    public string $certificate;


    /**
     * we have our own constructor so that we can load our certificate
     * information for parsing.
     *
     * @param ?Packet $i_packet a Packet to parse the RR from
     * @param array<string, mixed>|null $i_rr an array with parsed RR values
     *
     * @throws Exception
     */
    public function __construct( ?Packet $i_packet = null, ?array $i_rr = null ) {
        parent::__construct( $i_packet, $i_rr );

        # Load the lookup values
        $this->certFormatNameToId = array_flip( $this->certFormatIdToName );
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # Load and check the format; can be an int, or a mnemonic symbol.
        $format = array_shift( $i_rData );
        if ( ! is_numeric( $format ) ) {
            $mnemonic = strtoupper( trim( $format ) );
            if ( ! isset( $this->certFormatNameToId[ $mnemonic ] ) ) {
                return false;
            }
            $format = $this->certFormatNameToId[ $mnemonic ];
        } elseif ( ! isset( $this->certFormatIdToName[ (int) $format ] ) ) {
            return false;
        } else {
            $format = (int) $format;
        }
        $this->format = $format;

        $this->keytag = (int) array_shift( $i_rData );

        # Parse and check the algorithm; can be an int, or a mnemonic symbol.
        $algorithm = array_shift( $i_rData );
        if ( ! is_numeric( $algorithm ) ) {
            $mnemonic = strtoupper( trim( $algorithm ) );
            if ( ! isset( Lookups::$algorithmNameToID[ $mnemonic ] ) ) {
                return false;
            }
            $algorithm = Lookups::$algorithmNameToID[ $mnemonic ];
        } elseif ( ! isset( Lookups::$algorithmIdToName[ $algorithm ] ) ) {
            return false;
        } else {
            $algorithm = (int) $algorithm;
        }
        $this->algorithm = $algorithm;

        # Parse and base64 decode the certificate.
        #
        # Certificates MUST be provided base64 encoded.  If not, everything will
        # be broken after this point, as we assume it's base64 encoded.
        $this->certificate = base64_decode( implode( ' ', $i_rData ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->certificate ) > 0 ) {

            $data = pack( 'nnC', $this->format, $this->keytag, $this->algorithm ) . $this->certificate;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the format, keytag and algorithm.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nformat/nkeytag/Calgorithm', $this->rdata );

            $this->format = $parse[ 'format' ];
            $this->keytag = $parse[ 'keytag' ];
            $this->algorithm = $parse[ 'algorithm' ];

            # Copy the certificate.
            $this->certificate = substr( $this->rdata, 5, $this->rdLength - 5 );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->format . ' ' . $this->keytag . ' ' . $this->algorithm .
            ' ' . base64_encode( $this->certificate );
    }


}
