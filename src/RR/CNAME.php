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
 * CNAME Resource Record - RFC1035 section 3.3.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                     CNAME                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CNAME extends RR
{
    /*
     * The canonical name 
     */
    public string $cname;


    /** {@inheritdoc} @noinspection PhpMissingParentCallCommonInspection */
    #[ArrayShape( [ 'target' => "string" ] )] public function getPHPRData() : array {
        return [
            'target' => $this->cname,
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return $this->cleanString($this->cname) . '.';
    }

    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        $this->cname = $this->cleanString(array_shift($rdata));
        return true;
    }


    /** {@inheritdoc}
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            $offset = $packet->offset;
            $this->cname = $packet->expandEx( $offset );

            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->cname) > 0) {

            return $packet->compress($this->cname, $packet->offset);
        }

        return null;
    }
}
