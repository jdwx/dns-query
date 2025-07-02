<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Cache;


use JDWX\DNSQuery\Cache\MessageCache;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Test the Cache class. */
#[CoversClass( MessageCache::class )]
final class MessageCacheTest extends TestCase {


    public function testCacheBasics() : void {
        $cache = new MessageCache();
        self::assertFalse( $cache->has( 'foo' ) );

        $req = Message::request( 'example.com', 'MX', 'IN' );
        $rsp = Message::response( $req );
        $mx = ResourceRecord::fromString( 'example.com 3600 IN MX 10 smtp.example.com' );
        $rsp->addAnswer( $mx );

        $cache->put( 'foo', $rsp );

        self::assertTrue( $cache->has( 'foo' ) );
        $xx = $cache->get( 'foo' );
        $ans = $xx->getAnswer()[ 0 ];
        self::assertTrue( $ans->isType( 'MX' ) );
        self::assertEquals( [ 'smtp', 'example', 'com' ], $ans->tryGetRDataValue( 'exchange' ) );
    }


    /*
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
    */

}

