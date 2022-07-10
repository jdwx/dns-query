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
 * @since     File available since Release 1.2.5
 *
 */

/**
 * TLSA Resource Record - RFC 6698
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Cert. Usage  |   Selector    | Matching Type |               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+               /
 *  /                                                               /
 *  /                 Certificate Association Data                  /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class TLSA extends RR
{
    /*
     * The Certificate Usage Field
     */
    public int $certUsage;

    /*
     * The Selector Field
     */
    public int $selector;

    /*
     * The Matching Type Field
     */
    public int $matchingType;

    /*
     * The Certificate Association Data Field
     */
    public string $certificate;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string {
        return $this->certUsage . ' ' . $this->selector . ' ' .
            $this->matchingType . ' ' . base64_encode($this->certificate);
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
    protected function rrFromString(array $rdata) : bool {
        $this->certUsage       = (int) array_shift( $rdata );
        $this->selector        = (int) array_shift( $rdata );
        $this->matchingType    = (int) array_shift( $rdata );
        $this->certificate     = base64_decode(implode('', $rdata));

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
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {

            //
            // unpack the format, keytag and algorithm
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('Cusage/Cselector/Ctype', $this->rdata);

            $this->certUsage       = $x['usage'];
            $this->selector         = $x['selector'];
            $this->matchingType    = $x['type'];

            //
            // copy the certificate
            //
            $this->certificate  = substr($this->rdata, 3, $this->rdLength - 3);

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
        if (strlen($this->certificate) > 0) {

            $data = pack(
                    'CCC', $this->certUsage, $this->selector, $this->matchingType
                ) . $this->certificate;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
