<?php


declare( strict_types = 1 );


namespace Cache;


use JDWX\DNSQuery\Cache\MessageCache;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;


/** Test the Cache class. */
#[CoversClass( MessageCache::class )]
final class MessageCacheTest extends TestCase {


    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCacheBasics() : void {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );

        $cache = new MessageCache();
        self::assertFalse( $cache->has( 'foo' ) );

        $req = Message::request( 'example.com', 'MX', 'IN' );
        $rsp = Message::response( $req );
        $mx = new MX();
        $mx->name = 'example.com';
        $mx->exchange = 'smtp.example.com';
        $mx->preference = 10;
        $rsp->answer[] = $mx;

        $cache->put( 'foo', $ );



        self::assertTrue( $cache->has( 'foo' ) );
        $xx = $cache->get( 'foo' );
        $ans = $xx->answer[ 0 ];
        assert( $ans instanceof MX );
        self::assertEquals( 'smtp.google.com', $ans->exchange );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCacheExpire() : void {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );
        $rsp->answer[ 0 ]->ttl = 1;

        $cache = new MessageCache();
        self::assertFalse( $cache->has( 'foo' ) );
        $cache->put( 'foo', $rsp );
        usleep( 1010000 );
        self::assertFalse( $cache->has( 'foo' ) );
    }


    /** Coverage test for Cache::getEx() throwing an exception. */
    public function testCacheGetExException() : void {
        $cache = new MessageCache();
        self::expectException( Exception::class );
        $cache->getEx( 'foo' );
    }


    public function testCacheGetExPass() : void {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );
        $cache = new MessageCache();
        $cache->put( 'foo', $rsp );
        $xx = $cache->getEx( 'foo' );
        self::assertSame( $rsp, $xx );
    }


    /** Coverage test for Cache::hashRequest(). */
    public function testCacheHashRequest() : void {
        $req = new RequestPacket( 'foo', 'A' );
        $xx = MessageCache::hashRequest( $req );
        self::assertSame( '392642df2bfd95cb29616e386cd83a171998105c', $xx );
    }


    /** Coverage test for Cache::isTypeCacheable(). */
    public function testCacheIsTypeCacheable() : void {
        self::assertTrue( MessageCache::isTypeCacheable( 'A' ) );
        self::assertTrue( MessageCache::isTypeCacheable( 'MX' ) );
        self::assertFalse( MessageCache::isTypeCacheable( 'AXFR' ) );
        self::assertFalse( MessageCache::isTypeCacheable( 'OPT' ) );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCachePutAdditionalTTL() : void {
        $aRoot = gethostbyname( 'a.root-servers.net' );
        $dns = new Resolver( $aRoot );
        $rsp = $dns->query( 'org', 'SOA' );

        # Whack the authority section to force the TTL to be set by the additional section.
        $rsp->authority = [];

        $cache = new MessageCache();
        $cache->put( 'foo', $rsp );
        self::assertTrue( $cache->has( 'foo' ) );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCachePutAuthorityTTL() : void {
        $aRoot = gethostbyname( 'a.root-servers.net' );
        $dns = new Resolver( $aRoot );
        $rsp = $dns->query( 'org', 'SOA' );

        $cache = new MessageCache();
        $cache->put( 'foo', $rsp );
        self::assertTrue( $cache->has( 'foo' ) );
    }


}

