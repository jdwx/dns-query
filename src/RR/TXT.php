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
 * TXT Resource Record - RFC1035 section 3.3.14
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   TXT-DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class TXT extends RR
{


    /** @var string[] an array of the text strings */
    public array $text = [];


    /** {@inheritdoc} @noinspection PhpMissingParentCallCommonInspection */
    #[ArrayShape( [ 'txt' => "string" ] )] public function getPHPRData() : array {
        return [
            'txt' => $this->rrToString(),
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        if (count($this->text) == 0) {
            return '""';
        }

        $data = '';

        foreach ($this->text as $t) {

            $data .= $this->formatString( $t ) . ' ';
        }

        return trim($data);
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool {
        $data = $this->buildString($rdata);
        if (count($data) > 0) {

            $this->text = $data;
        }

        return true;
    }


    /** {@inheritdoc}
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {
            
            $length = $packet->offset + $this->rdLength;
            $offset = $packet->offset;

            while ($length > $offset) {

                $this->text[] = $packet->labelEx( $offset );
            }

            return true;
        }

        return false;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string {
        $data = '';

        foreach ($this->text as $t) {
            $data .= chr(strlen($t)) . $t;
        }

        $packet->offset += strlen($data);

        return $data;
    }


}
