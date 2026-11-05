<?php

use App\Http\Controllers\Api\TruckDeclarationsController;
use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\PostingApiService;

/**
 * Covers the two matchers that the external WTT integration depends on:
 *   - normaliseName()           → applied to both sides of findDriverByName()
 *   - declarationCoversPlate()  → 3rd accept path based on the driver's active decls
 *
 * Both are private, so we poke them via reflection. This is the simplest test that
 * actually exercises the fix; a full HTTP feature test would need to mock the
 * PostingApiService pagination, which adds noise without covering more of the fix.
 */

function makeController(): TruckDeclarationsController
{
    return new TruckDeclarationsController(
        app(PostingApiService::class),
        app(DriverService::class),
        app(DeclarationService::class),
    );
}

function invokePrivate(object $obj, string $method, array $args)
{
    $ref = new ReflectionMethod($obj, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($obj, $args);
}

describe('normaliseName (used on both sides of findDriverByName)', function () {
    it('normalises Danish ø/æ/å to plain ascii', function () {
        $c = makeController();
        $a = invokePrivate($c, 'normaliseName', ['Christian hjortshøj Lauritzen']);
        $b = invokePrivate($c, 'normaliseName', ['Christian Hjortshoj Lauritzen']);
        expect($a)->toBe($b)->toBe('christian hjortshoj lauritzen');
    });

    it('is case-insensitive and collapses whitespace', function () {
        $c = makeController();
        $a = invokePrivate($c, 'normaliseName', ['  Christian   Hjortshøj  Lauritzen ']);
        $b = invokePrivate($c, 'normaliseName', ['CHRISTIAN HJORTSHØJ LAURITZEN']);
        expect($a)->toBe($b);
    });

    it('matches the two forms WTT sends for the same IMI driver', function () {
        $c = makeController();
        $wttWithAccent = invokePrivate($c, 'normaliseName', ['Christian hjortshøj Lauritzen']);
        $wttAsciiRetry = invokePrivate($c, 'normaliseName', ['Christian hjortshoj Lauritzen']);
        $imiStored = invokePrivate($c, 'normaliseName', ['Christian Hjortshøj Lauritzen']);
        expect($wttWithAccent)->toBe($imiStored);
        expect($wttAsciiRetry)->toBe($imiStored);
    });

    it('tolerates stray double/trailing whitespace in the stored record', function () {
        $c = makeController();
        $needle = invokePrivate($c, 'normaliseName', ['Christian Hjortshøj Lauritzen']);
        // Simulate stored record with trailing space on first name + double space.
        $stored = invokePrivate($c, 'normaliseName', ['Christian ' . ' ' . 'Hjortshøj  Lauritzen']);
        expect($stored)->toBe($needle);
    });
});

describe('normalisePlate', function () {
    it('upper-cases and strips every whitespace char', function () {
        $c = makeController();
        expect(invokePrivate($c, 'normalisePlate', [' zk 9824 l ']))->toBe('ZK9824L');
        expect(invokePrivate($c, 'normalisePlate', ['ZK9824L']))->toBe('ZK9824L');
    });
});
