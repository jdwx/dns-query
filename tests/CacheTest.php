<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\tests;


use JDWX\DNSQuery\Cache\Cache;
use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;


/** Test the Cache class. */
class CacheTest extends TestCase {


    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCacheBasics() {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );

        $cache = new Cache();
        static::assertFalse( $cache->has( 'foo' ) );
        $cache->put( 'foo', $rsp );
        static::assertTrue( $cache->has( 'foo' ) );
        $xx = $cache->get( 'foo' );
        $ans = $xx->answer[ 0 ];
        assert( $ans instanceOf MX );
        static::assertEquals( 'smtp.google.com', $ans->exchange );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCacheExpire() {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );
        $rsp->answer[ 0 ]->ttl = 1;

        $cache = new Cache();
        static::assertFalse( $cache->has( 'foo' ) );
        $cache->put( 'foo', $rsp );
        usleep( 1010000 );
        static::assertFalse( $cache->has( 'foo' ) );
    }


    /** Coverage test for Cache::isTypeCacheable(). */
    public function testCacheIsTypeCacheable() {
        static::assertTrue( Cache::isTypeCacheable( 'A' ) );
        static::assertTrue( Cache::isTypeCacheable( 'MX' ) );
        static::assertFalse( Cache::isTypeCacheable( 'AXFR' ) );
        static::assertFalse( Cache::isTypeCacheable( 'OPT' ) );
    }


    /**
     * @throws InvalidArgumentException|Exception
     */
    public function testCacheGetExPass() {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );
        $cache = new Cache();
        $cache->put( 'foo', $rsp );
        $xx = $cache->getEx( 'foo' );
        static::assertInstanceOf( ResponsePacket::class, $xx );
    }


    /** Coverage test for Cache::getEx() throwing an exception. */
    public function testCacheGetExException() {
        $cache = new Cache();
        static::expectException( Exception::class );
        $cache->getEx( 'foo' );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCachePutAuthorityTTL() {
        $aRoot = gethostbyname( 'a.root-servers.net' );
        $dns = new Resolver( $aRoot );
        $rsp = $dns->query( 'org', 'SOA' );

        $cache = new Cache();
        $cache->put( 'foo',$rsp );
        static::assertTrue( $cache->has( 'foo' ) );
    }


    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testCachePutAdditionalTTL() {
        $aRoot = gethostbyname( 'a.root-servers.net' );
        $dns = new Resolver( $aRoot );
        $rsp = $dns->query( 'org', 'SOA' );

        # Whack the authority section to force the TTL to be set by the additional section.
        $rsp->authority = [];

        $cache = new Cache();
        $cache->put( 'foo',$rsp );
        static::assertTrue( $cache->has( 'foo' ) );
    }


    /** Coverage test for Cache::hashRequest(). */
    public function testCacheHashRequest() {
        $req = new RequestPacket( "foo", "A" );
        $xx = Cache::hashRequest( $req );
        static::assertSame( "392642df2bfd95cb29616e386cd83a171998105c", $xx );
    }


}

