<?php


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
 * EID Resource Record - undefined; the rdata is simply used as-is in its
 *                          binary format, so no processing has to be done.
 *
 */
class EID extends RR {


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        return $this->rdata;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        return true;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return '';
    }


}
