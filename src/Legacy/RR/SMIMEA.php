<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.4.2
 *
 */


/**
 * The SMIMEA RR is implemented exactly like the TLSA record, so
 * for now we just extend the TLSA RR and use it.
 *
 */
class SMIMEA extends TLSA {


}
