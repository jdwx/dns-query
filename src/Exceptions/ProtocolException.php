<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


/**
 * A protocol exception is thrown when the other end of
 * a connection is reachable but rejects the connection
 * in a way that suggests it should not be retried.
 *
 * As an example, after getting a "connection refused"
 * error while attempting to use DNS over HTTPS, we
 * can assume that the server is not configured
 * to handle DNS over HTTPS requests and therefore
 * that we should not try that again. (At least,
 * not for quite some time.)
 */
class ProtocolException extends TransportException {


}
