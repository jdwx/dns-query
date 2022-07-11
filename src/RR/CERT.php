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
 * @category  Networking
 * @package   Net_DNS2
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
class CERT extends RR
{
    /*
     * format's allowed for certificates
     */
    public const CERT_FORMAT_RES = 0;
    public const CERT_FORMAT_PKIX = 1;
    public const CERT_FORMAT_SPKI = 2;
    public const CERT_FORMAT_PGP = 3;
    public const CERT_FORMAT_IPKIX = 4;
    public const CERT_FORMAT_ISPKI = 5;
    public const CERT_FORMAT_IPGP = 6;
    public const CERT_FORMAT_ACPKIX = 7;
    public const CERT_FORMAT_IACPKIX = 8;
    public const CERT_FORMAT_URI = 253;
    public const CERT_FORMAT_OID = 254;

    public array $cert_format_name_to_id = [];
    public array $cert_format_id_to_name = [

        self::CERT_FORMAT_RES       => 'Reserved',
        self::CERT_FORMAT_PKIX      => 'PKIX',
        self::CERT_FORMAT_SPKI      => 'SPKI',
        self::CERT_FORMAT_PGP       => 'PGP',
        self::CERT_FORMAT_IPKIX     => 'IPKIX',
        self::CERT_FORMAT_ISPKI     => 'ISPKI',
        self::CERT_FORMAT_IPGP      => 'IPGP',
        self::CERT_FORMAT_ACPKIX    => 'ACPKIX',
        self::CERT_FORMAT_IACPKIX   => 'IACPKIX',
        self::CERT_FORMAT_URI       => 'URI',
        self::CERT_FORMAT_OID       => 'OID'
    ];

    /*
      * certificate format
     */
    public int $format;

    /*
     * key tag
     */
    public int $keytag;

    /*
     * The algorithm used for the CERT
     */
    public int $algorithm;

    /*
     * certificate
     */
    public string $certificate;


    /**
     * we have our own constructor so that we can load our certificate
     * information for parsing.
     *
     * @param ?Packet $packet a Packet to parse the RR from
     * @param ?array            $rr an array with parsed RR values
     *
     * @throws Exception
     */
    public function __construct(?Packet $packet = null, array $rr = null)
    {
        parent::__construct($packet, $rr);
    
        //
        // load the lookup values
        //
        $this->cert_format_name_to_id = array_flip($this->cert_format_id_to_name);
    }

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->format . ' ' . $this->keytag . ' ' . $this->algorithm . 
            ' ' . base64_encode($this->certificate);
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param string[] $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        //
        // load and check the format; can be an int, or a mnemonic symbol
        //
        $format = array_shift( $rdata );
        if ( ! is_numeric( $format ) ) {
            $mnemonic = strtoupper( trim( $format ) );
            if ( ! isset( $this->cert_format_name_to_id[ $mnemonic ] ) ) {
                return false;
            }
            $format = $this->cert_format_name_to_id[$mnemonic];
        } elseif ( ! isset( $this->cert_format_id_to_name[ (int) $format ] ) ) {
             return false;
        } else {
            $format = (int) $format;
        }
        $this->format = $format;

        $this->keytag = (int) array_shift($rdata);

        //
        // parse and check the algorithm; can be an int, or a mnemonic symbol
        //
        $algorithm = array_shift($rdata);
        if ( ! is_numeric( $algorithm ) ) {
            $mnemonic = strtoupper( trim( $algorithm ) );
            if ( ! isset( Lookups::$algorithm_name_to_id[ $mnemonic ] ) ) {
                return false;
            }
            $algorithm = Lookups::$algorithm_name_to_id[
                $mnemonic
            ];
        } elseif ( ! isset( Lookups::$algorithm_id_to_name[ $algorithm ] ) ) {
            return false;
        } else {
            $algorithm = (int) $algorithm;
        }
        $this->algorithm = $algorithm;

        //
        // parse and base64 decode the certificate
        //
        // certificates MUST be provided base64 encoded, if not, everything will
        // be broken after this point, as we assume it's base64 encoded.
        //
        $this->certificate = base64_decode(implode(' ', $rdata));

        return true;
    }

    /**
     * parses the rdata of the Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet(Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            //
            // unpack the format, keytag and algorithm
            //
            $x = unpack('nformat/nkeytag/Calgorithm', $this->rdata);

            $this->format       = $x['format'];
            $this->keytag       = $x['keytag'];
            $this->algorithm    = $x['algorithm'];

            //
            // copy the certificate
            //
            $this->certificate  = substr($this->rdata, 5, $this->rdLength - 5);

            return true;
        }

        return false;
    }


    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     * @return ?string either returns a binary packed
     *                                 string or null on failure
     * @access protected
     */
    protected function rrGet(Packet $packet) : ?string
    {
        if (strlen($this->certificate) > 0) {

            $data = pack('nnC', $this->format, $this->keytag, $this->algorithm) . $this->certificate;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}