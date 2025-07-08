<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


/**
 * This exception indicates that a network operation failed
 * due to a network-related issue, such as a timeout or
 * unreachable host. It typically represents something
 * that is expected to be transient.
 */
class NetworkException extends TransportException {


}
