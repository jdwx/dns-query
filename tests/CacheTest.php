<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests;


use JDWX\DNSQuery\Cache\Cache;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Resolver;
use JDWX\DNSQuery\RR\MX;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;


/** Test the Cache class. */
#[CoversClass( Cache::class )]
final class CacheTest extends TestCase {


    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function testCacheBasics() : void {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );

        $cache = new Cache();
        self::assertFalse( $cache->has( 'foo' ) );
        $cache->put( 'foo', $rsp );
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

        $cache = new Cache();
        self::assertFalse( $cache->has( 'foo' ) );
        $cache->put( 'foo', $rsp );
        usleep( 1010000 );
        self::assertFalse( $cache->has( 'foo' ) );
    }


    /** Coverage test for Cache::getEx() throwing an exception. */
    public function testCacheGetExException() : void {
        $cache = new Cache();
        self::expectException( Exception::class );
        $cache->getEx( 'foo' );
    }


    public function testCacheGetExPass() : void {
        $dns = new Resolver( '8.8.8.8' );
        $rsp = $dns->query( 'google.com', 'MX' );
        $cache = new Cache();
        $cache->put( 'foo', $rsp );
        $xx = $cache->getEx( 'foo' );
        self::assertSame( $rsp, $xx );
    }


    /** Coverage test for Cache::hashRequest(). */
    public function testCacheHashRequest() : void {
        $req = new RequestPacket( 'foo', 'A' );
        $xx = Cache::hashRequest( $req );
        self::assertSame( '392642df2bfd95cb29616e386cd83a171998105c', $xx );
    }


    /** Coverage test for Cache::isTypeCacheable(). */
    public function testCacheIsTypeCacheable() : void {
        self::assertTrue( Cache::isTypeCacheable( 'A' ) );
        self::assertTrue( Cache::isTypeCacheable( 'MX' ) );
        self::assertFalse( Cache::isTypeCacheable( 'AXFR' ) );
        self::assertFalse( Cache::isTypeCacheable( 'OPT' ) );
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

        $cache = new Cache();
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

        $cache = new Cache();
        $cache->put( 'foo', $rsp );
        self::assertTrue( $cache->has( 'foo' ) );
    }


}

