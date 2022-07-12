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
 * HINFO Resource Record - RFC1035 section 3.3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      CPU                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                       OS                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class HINFO extends RR
{


    /** @var string Computer Information */
    public string $cpu;

    /** @var string Operating System */
    public string $os;


    /** {@inheritdoc} @noinspection PhpMissingParentCallCommonInspection */
    #[ArrayShape( [ 'cpu' => "string", 'os' => "string" ] )] public function getPHPRData() : array {
        return [
            'cpu' => $this->cpu,
            'os' => $this->os,
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return $this->formatString( $this->cpu ) . ' ' . $this->formatString($this->os);
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        $data = $this->buildString($rdata);
        if (count($data) == 2) {

            $this->cpu  = trim($data[0], '"');
            $this->os   = trim($data[1], '"');

            return true;
        }

        return false;
    }


    /** {@inheritdoc}
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            $offset = $packet->offset;
    
            $this->cpu  = $packet->labelEx( $offset );
            $this->os   = $packet->labelEx( $offset );

            return true;
        }

        return false;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->cpu) > 0) {

            $data = pack('Ca*Ca*', strlen($this->cpu), $this->cpu, strlen($this->os), $this->os);

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }


}
