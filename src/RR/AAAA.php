<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Net_DNS2;
use JDWX\DNSQuery\Packet\Packet;
use JetBrains\PhpStorm\ArrayShape;


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
 *    |                                               |       
 *    |                                               |       
 *    |                                               |       
 *    |                    ADDRESS                    |       
 *    |                                               |       
 *    |                   (128 bit)                   |       
 *    |                                               |       
 *    |                                               |       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class AAAA extends RR
{
    /*
     * the IPv6 address in the preferred hexadecimal values of the eight 
     * 16-bit pieces 
     * per RFC1884
     *
     */
    public string $address;


    /** {@inheritdoc}
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'ipv6' => "string" ] )] public function getPHPRData() : array {
        return [
            'ipv6' => $this->address,
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return $this->address;
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        //
        // expand out compressed formats
        //
        $value = array_shift($rdata);
        if ( Net_DNS2::isIPv6( $value ) ) {

            $this->address = $value;
            return true;
        }
            
        return false;
    }


    /** {@inheritdoc} */
    protected function rrSet( Packet $packet) : bool
    {
        //
        // must be 8 x 16bit chunks, or 16 x 8bit
        //
        if ($this->rdLength == 16) {

            //
            // PHP's inet_ntop returns IPv6 addresses in their compressed form,
            // but we want to keep with the preferred standard, so we'll parse
            // it manually.
            //
            $x = unpack('n8', $this->rdata);
            if (count($x) == 8) {

                $this->address = vsprintf('%x:%x:%x:%x:%x:%x:%x:%x', $x);
                return true;
            }
        }
        
        return false;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string
    {
        $packet->offset += 16;
        return inet_pton($this->address);
    }


}
