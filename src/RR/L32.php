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
 * L32 Resource Record - RFC6742 section 2.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |          Preference           |      Locator32 (16 MSBs)      |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |     Locator32 (16 LSBs)       |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class L32 extends RR
{
    /*
     * The preference
     */
    public string $preference;

    /*
     * The locator32 field
     */
    public string $locator32;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->preference . ' ' . $this->locator32;
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
        $this->preference = array_shift($rdata);
        $this->locator32 = array_shift($rdata);

        return true;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     * 
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            //
            // unpack the values
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('npreference/C4locator', $this->rdata);

            $this->preference = $x['preference'];

            //
            // build the locator value
            //
            $this->locator32 = $x['locator1'] . '.' . $x['locator2'] . '.' .
                $x['locator3'] . '.' . $x['locator4'];

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     * 
     * @param Packet $packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     * 
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->locator32) > 0) {

            //
            // break out the locator value
            //
            $n = explode('.', $this->locator32);

            //
            // pack the data
            //
            return pack('nC4', $this->preference, $n[0], $n[1], $n[2], $n[3]);
        }

        return null;
    }
}
