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
 * @since     File available since Release 1.2.5
 *
 */


/**
 * TYPE65534 - Private space
 *
 * Since Bind 9.8 beta, it uses a private recode as documented
 * in the Bind ARM, chapter 4, "Private-type records."
 * Basically they store signing process state.
 *
 */
class TYPE65534 extends RR {


    /** @var string Private data field */
    public string $privateData;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->privateData = base64_decode( implode( '', $i_rData ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->privateData ) > 0 ) {

            $data = $this->privateData;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {
            $this->privateData = $this->rdata;

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return base64_encode( $this->privateData );
    }


}
