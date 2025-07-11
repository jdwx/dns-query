<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


/**
 * Setup exceptions indicates that something happened
 * on the local system during the setup of the transport
 * layer. (E.g., a resource limitation, an attempt
 * to bind an address that is already in use, etc.)
 *
 */
class SetupException extends TransportException {


}
