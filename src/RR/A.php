<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Net_DNS2;
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
 * A Resource Record - RFC1035 section 3.4.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ADDRESS                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class A extends RR
{
    /*
     * The IPv4 address in quad-dotted notation
     */
    public string $address;

    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return $this->address;
    }

    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        $value = array_shift($rdata);

        if ( Net_DNS2::isIPv4( $value ) ) {
            
            $this->address = $value;
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            $this->address = inet_ntop($this->rdata);
            if ($this->address !== false) {
            
                return true;
            }
        }

        return false;
    }

    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string
    {
        $packet->offset += 4;
        return inet_pton($this->address);
    }
}
