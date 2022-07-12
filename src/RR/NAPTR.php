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
 * NAPTR Resource Record - RFC2915
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                     ORDER                     |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                   PREFERENCE                  |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                     FLAGS                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                   SERVICES                    /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                    REGEXP                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                  REPLACEMENT                  /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class NAPTR extends RR
{


    /*
     * the order in which the NAPTR records MUST be processed
     */
    public int $order;

    /*
     * specifies the order in which NAPTR records with equal "order"
     * values SHOULD be processed
     */
    public int $preference;

    /*
     * rewrite flags
     */
    public string $flags;

    /* 
     * Specifies the service(s) available down this rewrite path
     */
    public string $services;

    /*
     * regular expression
     */
    public string $regexp;

    /* 
     * The next NAME to query for NAPTR, SRV, or address records
     * depending on the value of the flags field
     */
    public string $replacement;


    /** {@inheritdoc} */
    protected function rrToString() : string {
        return $this->order . ' ' . $this->preference . ' ' . 
            $this->formatString($this->flags) . ' ' . 
            $this->formatString($this->services) . ' ' . 
            $this->formatString($this->regexp) . ' ' . 
            $this->cleanString($this->replacement) . '.';
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool {
        $this->order        = (int) array_shift($rdata);
        $this->preference   = (int) array_shift($rdata);

        $data = $this->buildString($rdata);
        if (count($data) == 4) {

            $this->flags        = $data[0];
            $this->services     = $data[1];
            $this->regexp       = $data[2];
            $this->replacement  = $this->cleanString($data[3]);
        
            return true;
        }

        return false;
    }


    /** {@inheritdoc}
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {
            
            //
            // unpack the order and preference
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('norder/npreference', $this->rdata);

            $this->order        = $x['order'];
            $this->preference   = $x['preference'];

            $offset             = $packet->offset + 4;

            $this->flags        = $packet->labelEx( $offset );
            $this->services     = $packet->labelEx( $offset );
            $this->regexp       = $packet->labelEx( $offset );

            $this->replacement  = $packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string {
        if ( (isset($this->order)) && (strlen($this->services) > 0) ) {
            
            $data = pack('nn', $this->order, $this->preference);

            $data .= chr(strlen($this->flags)) . $this->flags;
            $data .= chr(strlen($this->services)) . $this->services;
            $data .= chr(strlen($this->regexp)) . $this->regexp;

            $packet->offset += strlen($data);

            $data .= $packet->compress($this->replacement, $packet->offset);

            return $data;
        }

        return null;
    }
}

