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
 * DHCID Resource Record - RFC4701 section 3.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  ID Type Code                 |       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |       Digest Type     |                       /       
 *    +--+--+--+--+--+--+--+--+                       /
 *    /                                               /       
 *    /                    Digest                     /       
 *    /                                               /       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class DHCID extends RR
{
    /*
     * Identifier type
     */
    public int $idType;

    /*
     * Digest Type
     */
    public int $digestType;

    /*
     * The digest
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
        $out = pack('nC', $this->idType, $this->digestType);
        $out .= base64_decode($this->digest);

        return base64_encode($out);
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
        $data = base64_decode(array_shift($rdata));
        if (strlen($data) > 0) {

            //
            // unpack the id type and digest type
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('nid_type/Cdigest_type', $data);

            $this->idType      = $x['id_type'];
            $this->digestType  = $x['digest_type'];

            //
            // copy out the digest
            //
            $this->digest = base64_encode(substr($data, 3, strlen($data) - 3));

            return true;
        }

        return false;
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
            // unpack the id type and digest type
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('nid_type/Cdigest_type', $this->rdata);

            $this->idType      = $x['id_type'];
            $this->digestType  = $x['digest_type'];

            //
            // copy out the digest
            //
            $this->digest = base64_encode(
                substr($this->rdata, 3, $this->rdLength - 3)
            );

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
        if (strlen($this->digest) > 0) {

            $data = pack('nC', $this->idType, $this->digestType) .
                base64_decode($this->digest);

            $packet->offset += strlen($data);

            return $data;
        }
    
        return null;
    }
}