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
 * @since     File available since Release 0.6.0
 *
 */

/**
 * DS Resource Record - RFC4034 section 5.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |           Key Tag             |  Algorithm    |  Digest Type  |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Digest                             /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class DS extends RR
{
    /*
     * key tag
     */
    public int $keytag;

    /*
     * algorithm number
     */
    public int $algorithm;

    /*
     * algorithm used to construct the digest
     */
    public int $digestType;

    /*
     * the digest data
     */
    public string $digest;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->keytag . ' ' . $this->algorithm . ' ' . $this->digestType . ' ' . $this->digest;
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
        $this->keytag       = (int) array_shift( $rdata );
        $this->algorithm    = (int) array_shift( $rdata );
        $this->digestType   = (int) array_shift( $rdata );
        $this->digest       = implode('', $rdata );

        return true;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            //
            // unpack the keytag, algorithm and digest type
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('nkeytag/Calgorithm/Cdigesttype/H*digest', $this->rdata);

            $this->keytag       = $x['keytag'];
            $this->algorithm    = $x['algorithm'];
            $this->digestType   = $x['digesttype'];
            $this->digest       = $x['digest'];

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->digest) > 0) {

            $data = pack('nCCH*', $this->keytag, $this->algorithm, $this->digestType, $this->digest);

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
