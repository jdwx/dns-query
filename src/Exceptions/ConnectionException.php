<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


/**
 * A connection exception is thrown when something
 * happens that is specific to a particular connection.
 * An example might be a short read on a TCP connection.
 *
 * In such cases, the connection typically needs to be
 * discarded (with the possible exception of unbound
 * UDP sockets). But this error doesn't necessarily
 * mean no further connections can be made to the same
 * host:port combination.
 */
class ConnectionException extends TransportException {


}
