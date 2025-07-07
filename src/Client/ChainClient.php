<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\MessageInterface;


class ChainClient extends AbstractClient {


    /** @param list<ClientInterface> $rBackends */
    public function __construct( private readonly array $rBackends, ?string $classMessage ) {
        parent::__construct( $classMessage );
    }


    public function queryMessage( MessageInterface $i_msg ) : ?MessageInterface {
        foreach ( $this->rBackends as $backend ) {
            $response = $backend->queryMessage( $i_msg );
            if ( $response instanceof MessageInterface ) {
                return $response;
            }
        }
        return null;
    }


}
