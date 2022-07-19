<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
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
 * OPT Resource Record - RFC2929 section 3.1
 *
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                          OPTION-CODE                          |
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                         OPTION-LENGTH                         |
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                                                               |
 *    /                          OPTION-DATA                          /
 *    /                                                               /
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *
 */
class OPT extends RR {


    /** @var ?int Option code - assigned by IANA */
    public ?int $optionCode = null;

    /** @var int Length of the option data */
    public int $optionLength;

    /** @var ?string The option data */
    public ?string $optionData = null;

    /** @var int Extended response code stored in the TTL */
    public int $extendedResponseCode;

    /** @var int Implementation level */
    public int $version;

    /** @var int "DO" bit used for DNSSEC - RFC3225 */
    public int $do;

    /** @var int Extended flags */
    public int $extFlags;


    /**
     * Constructor - builds a new OPT object; normally you wouldn't call
     * this directly, but OPT RRs are a little different
     *
     * @param ?Packet    $i_packet a Packet or null to create an empty object
     * @param ?array     $i_rr an array with RR parse values or null to
     *                           create an empty object
     *
     * @throws Exception
     */
    public function __construct( Packet $i_packet = null, array $i_rr = null ) {

        # This is for when we're manually building an OPT RR object; we aren't
        # passing in binary data to parse, we just want a clean/empty object.
        $this->name = '';
        $this->type = 'OPT';
        $this->rdLength = 0;

        $this->optionCode = 0;
        $this->optionLength = 0;
        $this->extendedResponseCode = 0;
        $this->version = 0;
        $this->do = 0;
        $this->extFlags = 0;

        # Everything else gets passed through to the parent.
        if ( ( ! is_null( $i_packet ) ) && ( ! is_null( $i_rr ) ) ) {

            parent::__construct( $i_packet, $i_rr );
        }
    }


    /**
     * pre-builds the TTL value for this record; we needed to separate this out
     * from the rrGet() function, as the logic in the RR class packs the TTL
     * value before it builds the rdata value.
     *
     * @return void
     */
    protected function preBuild() : void {

        # Build the TTL value based on the local values
        /** @noinspection SpellCheckingInspection */
        $ttl = unpack(
            'N',
            pack( 'CCCC', $this->extendedResponseCode, $this->version, ( $this->do << 7 ), 0 )
        );

        $this->ttl = $ttl[ 1 ];

    }


    /** {@inheritdoc} There is no
     * definition for parsing an OPT RR by string. This is just here to validate
     * the binary parsing / building routines.
     */
    protected function rrFromString( array $i_rData ) : bool {
        $this->optionCode = (int) array_shift( $i_rData );
        $this->optionData = array_shift( $i_rData );
        $this->optionLength = strlen( $this->optionData );

        $this->unpackTTL();

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {

        # If there is an option code, then pack that data too.
        if ( $this->optionCode ) {

            $data = pack( 'nn', $this->optionCode, $this->optionLength ) .
                $this->optionData;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return '';
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {

        $this->unpackTTL();

        # Parse the data, if there is any
        if ( $this->rdLength > 0 ) {

            # Unpack the code and length
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'noptionCode/noptionLength', $this->rdata );

            $this->optionCode = $parse[ 'optionCode' ];
            $this->optionLength = $parse[ 'optionLength' ];

            # Copy out the data based on the length.
            $this->optionData = substr( $this->rdata, 4 );
        }

        return true;
    }


    /** {@inheritdoc} There is no
     * definition for returning an OPT RR by string. This is just here to validate
     * the binary parsing / building routines.
     */
    protected function rrToString() : string {
        return $this->optionCode . ' ' . $this->optionData;
    }


    /** Unpack the TTL value, which has special meaning in OPT records.
     * @return void
     */
    protected function unpackTTL() : void {

        # Parse out the TTL value
        /** @noinspection SpellCheckingInspection */
        $parse = unpack( 'Cextended/Cversion/Cdo/Cz', pack( 'N', $this->ttl ) );

        $this->extendedResponseCode = $parse[ 'extended' ];
        $this->version = $parse[ 'version' ];
        $this->do = ( $parse[ 'do' ] >> 7 );
        $this->extFlags = $parse[ 'z' ];

    }


}

