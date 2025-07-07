<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Cache\MessageCacheInterface;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;


class CacheOnlyClient extends AbstractClient {


    public function __construct( private readonly MessageCacheInterface $cache, ?string $classMessage = null ) {
        parent::__construct( $classMessage );
    }


    public function queryMessage( MessageInterface $i_msg ) : ?MessageInterface {
        if ( ! $this->cache::isTypeCacheable( $i_msg ) ) {
            return null;
        }
        $match = $this->cache->get( $i_msg );
        if ( ! $match instanceof MessageInterface ) {
            return null;
        }
        $response = static::response( $i_msg );
        assert( $response instanceof Message );
        foreach ( $match->getAnswer() as $rr ) {
            $response->addAnswer( $rr );
        }
        foreach ( $match->getAuthority() as $rr ) {
            $response->addAuthority( $rr );
        }
        foreach ( $match->getAdditional() as $rr ) {
            if ( $rr->isType( 'OPT' ) ) {
                continue;
            }
            $response->addAdditional( $rr );
        }
        return $response;
    }


}
