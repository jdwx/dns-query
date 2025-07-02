<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Cache\MessageCache;
use JDWX\DNSQuery\Cache\MessageCacheInterface;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use Psr\SimpleCache\CacheInterface;


class CachingClient extends AbstractClient {


    private MessageCacheInterface $cache;

    /** @var array<int, string> */
    private array $rIDMap = [];

    private string $stLastKey = '';


    public function __construct( CacheInterface|MessageCacheInterface $i_cache,
                                 private readonly ClientInterface     $clientBackend ) {
        if ( ! $i_cache instanceof MessageCacheInterface ) {
            $i_cache = new MessageCache( $i_cache );
        }
        $this->cache = $i_cache;
    }


    public function receiveResponse( ?int $i_id = null, ?int $i_nuTimeoutSeconds = null,
                                     ?int $i_nuTimeoutMicroSeconds = null ) : ?Message {
        $stKey = is_int( $i_id ) ? $this->rIDMap[ $i_id ] ?? '' : $this->stLastKey;
        $this->stLastKey = '';
        if ( ! empty( $stKey ) && $this->cache->has( $stKey ) ) {
            $msg = $this->cache->get( $stKey );
            unset( $this->rIDMap[ $msg->id() ] );
            return $msg;
        }
        $msg = $this->clientBackend->receiveResponse( $i_id, $i_nuTimeoutSeconds, $i_nuTimeoutMicroSeconds );
        if ( ! $msg instanceof Message ) {
            return null;
        }
        $this->cache->put( $stKey, $msg );
        return $msg;
    }


    public function sendRequest( MessageInterface $i_request ) : void {
        $this->rIDMap[ $i_request->id() ] = $this->stLastKey = $this->cache::hash( $i_request );
        $this->clientBackend->sendRequest( $i_request );
        // TODO: Implement sendRequest() method.
    }


}
