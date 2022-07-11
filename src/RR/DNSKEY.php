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
 * DNSKEY Resource Record - RFC4034 section 2.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |              Flags            |    Protocol   |   Algorithm   |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Public Key                         /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class DNSKEY extends RR
{
    /*
     * flags
     */
    public int $flags;

    /*
     * protocol
     */
    public int $protocol;

    /*
     * algorithm used
     */
    public int $algorithm;

    /*
     * the public key
     */
    public string $key;

    /*
     * calculated key tag
     */
    public int $keytag;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->flags . ' ' . $this->protocol . ' ' . 
            $this->algorithm . ' ' . $this->key;
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
        $this->flags        = (int) array_shift($rdata);
        $this->protocol     = (int) array_shift($rdata);
        $this->algorithm    = (int) array_shift($rdata);
        $this->key          = implode(' ', $rdata);
    
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
            // unpack the flags, protocol and algorithm
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('nflags/Cprotocol/Calgorithm', $this->rdata);

            //
            // TODO: right now we're just displaying what's in DNS; we really 
            // should be parsing bit 7 and bit 15 of the flags field, and store
            // those separately.
            //
            // right now the DNSSEC implementation is really just for display,
            // we don't validate or handle any of the keys
            //
            $this->flags        = $x['flags'];
            $this->protocol     = $x['protocol'];
            $this->algorithm    = $x['algorithm'];

            $this->key          = base64_encode(substr($this->rdata, 4));

            $this->keytag       = $this->getKeyTag();

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Net_DNS2_Packet packet to use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->key) > 0) {

            $data = pack('nCC', $this->flags, $this->protocol, $this->algorithm);
            $data .= base64_decode($this->key);

            $packet->offset += strlen($data);

            return $data;
        }
        
        return null;
    }

    /**
     * compute keytag from rdata (rfc4034)
     * (invalid for algorithm 1, but it's not recommended)
     *
     * @return int
     * @access protected
     *
     */
    protected function getKeyTag() : int
    {
        $key = array_values(unpack("C*", $this->rdata));
        $keySize = $this->rdLength;

        $ac = 0;
        for( $i = 0; $i < $keySize; $i++ )
            $ac += ($i & 1) ? $key[$i] : $key[$i] << 8;

        $ac += ($ac >> 16) & 0xFFFF;
        return $ac & 0xFFFF;
    }
}