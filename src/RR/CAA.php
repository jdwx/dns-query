<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


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
 * @since     File available since Release 1.2.0
 *
 */

/**
 * CAA Resource Record - http://tools.ietf.org/html/draft-ietf-pkix-caa-03
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |          FLAGS        |      TAG LENGTH       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      TAG                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      DATA                     /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CAA extends RR
{
    /*
     * The critical flag
     */
    public int $flags;

    /*
     * The property identifier
     */
    public string $tag;

    /*
      * The property value
     */
    public string $value;


    /** {@inheritdoc} @noinspection PhpMissingParentCallCommonInspection */
    #[ArrayShape( [ 'flags' => "int", 'tag' => "string", 'value' => "string" ] )] public function getPHPRData() : array {
        return [
            'flags' => $this->flags,
            'tag'   => $this->tag,
            'value' => $this->value,
        ];
    }


    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return $this->flags . ' ' . $this->tag . ' "' . 
            trim($this->cleanString($this->value), '"') . '"';
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        $this->flags    = (int) array_shift($rdata);
        $this->tag      = array_shift($rdata);

        $this->value    = trim($this->cleanString(implode(' ', $rdata)), '"');
        
        return true;
    }


    /** {@inheritdoc} */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {
            
            //
            // unpack the flags and tag length
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('Cflags/Ctag_length', $this->rdata);

            $this->flags    = $x['flags'];
            $offset         = 2;

            $this->tag      = substr($this->rdata, $offset, $x['tag_length']);
            $offset         += $x['tag_length'];

            $this->value    = substr($this->rdata, $offset);

            return true;
        }

        return false;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->value) > 0) {

            $data  = chr($this->flags);
            $data .= chr(strlen($this->tag)) . $this->tag . $this->value;

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }


}
