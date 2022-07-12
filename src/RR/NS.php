<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
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
 * NS Resource Record - RFC1035 section 3.3.11
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   NSD NAME                    /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class NS extends RR
{
    /*
     * the hostname of the DNS server
     */
    public string $nsdName;


    /** {@inheritdoc} @noinspection PhpMissingParentCallCommonInspection */
    #[ArrayShape( [ 'target' => "string" ] )] public function getPHPRData() : array {
        return [
            'target' => $this->nsdName,
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string {
        return $this->cleanString($this->nsdName) . '.';
    }

    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool {
        $this->nsdName = $this->cleanString(array_shift($rdata));
        return true;
    }


    /** {@inheritdoc}
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {

            $offset = $packet->offset;
            $this->nsdName = $packet->expandEx( $offset );

            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string {
        if (strlen($this->nsdName) > 0) {

            return $packet->compress($this->nsdName, $packet->offset);
        }
        
        return null;
    }
}
