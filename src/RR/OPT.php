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
 * @since     File available since Release 1.0.0
 *
 */

/**
 * OPT Resource Record - RFC2929 section 3.1
 *
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                          OPTION-CODE                          |
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                         OPTION-LENGTH                         |
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *    |                                                               |
 *    /                          OPTION-DATA                          /
 *    /                                                               /
 *    +---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+---+
 *
 */
class OPT extends RR
{
    /*
     * option code - assigned by IANA
     */
    public int $option_code;

    /*
     * the length of the option data
     */
    public int $option_length;

    /*
     * the option data
     */
    public string $option_data;

    /*
     * the extended response code stored in the TTL
     */
    public int $extended_response_code;

    /*
     * the implementation level
     */
    public int $version;

    /*
     * the "DO" bit used for DNSSEC - RFC3225
     */
    public int $do;

    /*
     * the extended flags
     */
    public int $z;

    /**
     * Constructor - builds a new Net_DNS2_RR_OPT object; normally you wouldn't call
     * this directly, but OPT RRs are a little different
     *
     * @param ?Packet   &$packet a Packet or null to create an empty object
     * @param ?array    $rr      an array with RR parse values or null to
     *                           create an empty object
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct(Packet $packet = null, array $rr = null)
    {
        //
        // this is for when we're manually building an OPT RR object; we aren't
        // passing in binary data to parse, we just want a clean/empty object.
        //
        $this->name             = '';
        $this->type             = 'OPT';
        $this->rdLength         = 0;

        $this->option_code      = 0;
        $this->option_length    = 0;
        $this->extended_response_code   = 0;
        $this->version          = 0;
        $this->do               = 0;
        $this->z                = 0;

        //
        // everything else gets passed through to the parent.
        //
        if ( (!is_null($packet)) && (!is_null($rr)) ) {

            parent::__construct($packet, $rr);
        }
    }

    /**
     * method to return the rdata portion of the packet as a string. There is no
     * definition for returning an OPT RR by string. This is just here to validate
     * the binary parsing / building routines.
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->option_code . ' ' . $this->option_data;
    }

    /**
     * Parses the rdata portion from a standard DNS config line. There is no
     * definition for parsing an OPT RR by string. This is just here to validate
     * the binary parsing / building routines.
     *
     * @param string[] $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        $this->option_code      = (int) array_shift($rdata);
        $this->option_data      = array_shift($rdata);
        $this->option_length    = strlen($this->option_data);

        /** @noinspection SpellCheckingInspection */
        $x = unpack('Cextended/Cversion/Cdo/Cz', pack('N', $this->ttl));

        $this->extended_response_code   = $x['extended'];
        $this->version          = $x['version'];
        $this->do               = ($x['do'] >> 7);
        $this->z                = $x['z'];

        return true;
    }

    /**
     * parses the rdata of the Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet(Packet $packet) : bool
    {
        //
        // parse out the TTL value
        //
        /** @noinspection SpellCheckingInspection */
        $x = unpack('Cextended/Cversion/Cdo/Cz', pack('N', $this->ttl));

        $this->extended_response_code   = $x['extended'];
        $this->version          = $x['version'];
        $this->do               = ($x['do'] >> 7);
        $this->z                = $x['z'];

        //
        // parse the data, if there is any
        //
        if ($this->rdLength > 0) {

            //
            // unpack the code and length
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('noption_code/noption_length', $this->rdata);

            $this->option_code      = $x['option_code'];
            $this->option_length    = $x['option_length'];

            //
            // copy out the data based on the length
            //
            $this->option_data      = substr($this->rdata, 4);
        }

        return true;
    }

    /**
     * pre-builds the TTL value for this record; we needed to separate this out
     * from the rrGet() function, as the logic in the Net_DNS2_RR packs the TTL
     * value before it builds the rdata value.
     *
     * @return void
     * @access protected
     *
     */
    protected function preBuild() : void
    {
        //
        // build the TTL value based on the local values
        //
        /** @noinspection SpellCheckingInspection */
        $ttl = unpack(
            'N', 
            pack('CCCC', $this->extended_response_code, $this->version, ($this->do << 7), 0)
        );

        $this->ttl = $ttl[1];

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
    protected function rrGet(Packet $packet) : ?string
    {
        //
        // if there is an option code, then pack that data too
        //
        if ($this->option_code) {

            $data = pack('nn', $this->option_code, $this->option_length) .
                $this->option_data;

            $packet->offset += strlen($data);

            return $data;
        }

        return '';
    }
}
