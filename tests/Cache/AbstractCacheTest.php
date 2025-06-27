<?php


declare( strict_types = 1 );


namespace Cache;


use JDWX\ArrayCache\ArrayCache;
use JDWX\DNSQuery\Cache\AbstractCache;
use JDWX\DNSQuery\Cache\MessageCache;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\Question;
use JDWX\DNSQuery\RR\A;
use JDWX\DNSQuery\RR\MX;
use JDWX\DNSQuery\RR\NS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( AbstractCache::class )]
final class AbstractCacheTest extends TestCase {


    public function testCalculateTTL() : void {
        $msg = Message::request( 'example.com', 'A', 'IN' );
        self::assertSame( 12345, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAnswer = new MX();
        $rrAnswer->ttl = 3600;
        $msg->answer[] = $rrAnswer;
        self::assertSame( 3600, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAuthority = new NS();
        $rrAuthority->ttl = 3500;
        $msg->authority[] = $rrAuthority;
        self::assertSame( 3500, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAdditional = new A();
        $rrAdditional->ttl = 3400;
        $msg->additional[] = $rrAdditional;
        self::assertSame( 3400, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAnswer->ttl = 3300;
        self::assertSame( 3300, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAuthority->ttl = 3200;
        self::assertSame( 3200, AbstractCache::calculateTTL( $msg, 12345 ) );

        $rrAdditional->ttl = 0;
        self::assertSame( 0, AbstractCache::calculateTTL( $msg, 12345 ) );

    }


    public function testGetEx() : void {
        $backend = new ArrayCache();
        $cache = new MessageCache( $backend );
        $msg = Message::request( 'example.com', 'A', 'IN' );
        $backend->set( 'foo', $msg, 1 );

        self::assertSame( $msg, $cache->getEx( 'foo' ) );
        usleep( 25000 );

        self::expectException( Exception::class );
        $cache->getEx( 'invalid key!' );
    }


    public function testHash() : void {
        $q1 = new Question( 'example.com', 'A', 'IN' );
        $q2 = Message::request( 'example.com', 'AAAA', 'IN' );
        $q3 = Message::request( 'example.com', 'A', 'CH' );
        self::assertNotEquals( AbstractCache::hash( $q1 ), AbstractCache::hash( $q2 ) );
        self::assertNotEquals( AbstractCache::hash( $q1 ), AbstractCache::hash( $q3 ) );
        self::assertNotEquals( AbstractCache::hash( $q2 ), AbstractCache::hash( $q3 ) );

        $msg = Message::request( $q1 );
        self::assertSame( AbstractCache::hash( $q1 ), AbstractCache::hash( $msg ) );

        $msg->question[] = $q2;
        self::assertNotEquals( AbstractCache::hash( $q1 ), AbstractCache::hash( $msg ) );
        self::assertNotEquals( AbstractCache::hash( $q2 ), AbstractCache::hash( $msg ) );
    }


    public function testIsTypeCacheable() : void {
        self::assertTrue( AbstractCache::isTypeCacheable( 'A' ) );
        self::assertTrue( AbstractCache::isTypeCacheable( RecordType::A->value ) );
        self::assertTrue( AbstractCache::isTypeCacheable( RecordType::A ) );
        self::assertFalse( AbstractCache::isTypeCacheable( 'OPT' ) );
        self::assertFalse( AbstractCache::isTypeCacheable( RecordType::OPT->value ) );
        self::assertFalse( AbstractCache::isTypeCacheable( RecordType::OPT ) );
    }


    public function testPut() : void {
        $backend = new ArrayCache();
        $cache = new MessageCache( $backend );
        $msg = Message::request( 'example.com', 'A', 'IN' );
        $cache->put( 'foo', $msg );
        self::assertSame( $msg, $backend->get( 'foo' ) );
    }


}
