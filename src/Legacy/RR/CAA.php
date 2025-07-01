<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;
use JetBrains\PhpStorm\ArrayShape;


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
 * @since     File available since Release 1.2.0
 *
 */


/**
 * CAA Resource Record - http://tools.ietf.org/html/draft-ietf-pkix-caa-03
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |          FLAGS        |      TAG LENGTH       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      TAG                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      DATA                     /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CAA extends RR {


    /** @var int Critical flag */
    public int $flags;

    /** @var string Property identifier */
    public string $tag;

    /** @var string Property value */
    public string $value;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'flags' => 'int', 'tag' => 'string', 'value' => 'string' ] )] public function getPHPRData() : array {
        return [
            'flags' => $this->flags,
            'tag' => $this->tag,
            'value' => $this->value,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->flags = (int) array_shift( $i_rData );
        $this->tag = array_shift( $i_rData );

        $this->value = trim( $this->cleanString( implode( ' ', $i_rData ) ), '"' );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->value ) > 0 ) {

            $data = chr( $this->flags );
            $data .= chr( strlen( $this->tag ) ) . $this->tag . $this->value;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the flags and tag length.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Cflags/Ctag_length', $this->rdata );

            $this->flags = $parse[ 'flags' ];
            $offset = 2;

            $this->tag = substr( $this->rdata, $offset, $parse[ 'tag_length' ] );
            $offset += $parse[ 'tag_length' ];

            $this->value = substr( $this->rdata, $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->flags . ' ' . $this->tag . ' "' .
            trim( $this->cleanString( $this->value ), '"' ) . '"';
    }


}
