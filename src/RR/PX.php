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
 * PX Resource Record - RFC2163 section 4
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  PREFERENCE                   |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    MAP822                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    MAP X400                   /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--
 *
 */
class PX extends RR
{
    /*
     * preference
     */
    public string $preference;

    /* 
     * the RFC822 part of the MIXER-conformant Global Address Mapping
     */
    public string $map822;

    /*
     * the X.400 part of the MIXER-conformant Global Address Mapping
     */
    public string $mapX400;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->cleanString($this->map822) . '. ' . 
            $this->cleanString($this->mapX400) . '.';
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool {
        $this->preference   = $rdata[0];
        $this->map822       = $this->cleanString($rdata[1]);
        $this->mapX400      = $this->cleanString($rdata[2]);

        return true;
    }


    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet &$packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {

            //
            // parse the preference
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('npreference', $this->rdata);
            $this->preference = $x['preference'];

            $offset         = $packet->offset + 2;

            $this->map822   = $packet->expandEx( $offset );
            $this->mapX400  = $packet->expandEx( $offset );

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet &$packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return null|string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string {
        if (strlen($this->map822) > 0) {
            
            $data = pack('n', $this->preference);
            $packet->offset += 2;

            $data .= $packet->compress($this->map822, $packet->offset);
            $data .= $packet->compress($this->mapX400, $packet->offset);

            return $data;
        }

        return null;
    }
}
