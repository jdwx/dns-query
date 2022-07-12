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
 * This is only used for generating an empty ANY RR.
 *
 */
class ANY extends RR
{

    /** {@inheritdoc} */
    protected function rrToString() : string
    {
        return '';
    }


    /** {@inheritdoc} */
    protected function rrFromString(array $rdata) : bool
    {
        return true;
    }


    /** {@inheritdoc} */
    protected function rrSet( Packet $packet ) : bool
    {
        return true;
    }


    /** {@inheritdoc} */
    protected function rrGet( Packet $packet ) : ?string
    {
        return '';
    }


}
