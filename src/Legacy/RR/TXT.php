<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exceptions\Exception;
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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * TXT Resource Record - RFC1035 section 3.3.14
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   TXT-DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class TXT extends RR {


    /** @var string[] an array of the text strings */
    public array $text = [];


    /**
     * @inheritdoc
     * @noinspection PhpMissingParentCallCommonInspection
     * @return array<string, string>
     */
    #[ArrayShape( [ 'txt' => 'string' ] )] public function getPHPRData() : array {
        return [
            'txt' => $this->rrToString(),
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = $this->buildString( $i_rData );
        if ( count( $data ) > 0 ) {
            $this->text = $data;
        }

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $data = '';

        foreach ( $this->text as $txt ) {
            $data .= chr( strlen( $txt ) ) . $txt;
        }

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc
     * @throws Exception
     */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $length = $i_packet->offset + $this->rdLength;
            $offset = $i_packet->offset;

            while ( $length > $offset ) {
                $this->text[] = $i_packet->labelEx( $offset );
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        if ( count( $this->text ) == 0 ) {
            return '""';
        }

        $data = '';

        foreach ( $this->text as $txt ) {
            $data .= static::formatString( $txt ) . ' ';
        }

        return trim( $data );
    }


}
