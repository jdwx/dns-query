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
 * @since     File available since Release 1.3.1
 *
 */

/**
 * LP Resource Record - RFC6742 section 2.4
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |          Preference           |                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *  /                                                               /
 *  /                              FQDN                             /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class LP extends RR
{
    /*
     * The preference
     */
    public string $preference;

    /*
     * The FQDN field
     */
    public string $fqdn;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->preference . ' ' . $this->fqdn . '.';
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
    protected function rrFromString(array $rdata) : bool
    {
        $this->preference = array_shift($rdata);
        $this->fqdn = trim(array_shift($rdata), '.');

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
            $offset = $packet->offset + 2;

            //
            // get the hostname
            //
            $this->fqdn = $packet->expandEx( $offset );

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
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     * 
     */
    protected function rrGet( Packet $packet) : ?string {
        if (strlen($this->fqdn) > 0) {
     
            $data = pack('n', $this->preference);
            $packet->offset += 2;

            $data .= $packet->compress($this->fqdn, $packet->offset);
            return $data;
        }

        return null;
    }
}
