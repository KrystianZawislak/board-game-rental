<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SecurityHeadersSubscriberTest extends TestCase
{
    public function testBaselineHeadersAreAlwaysSet(): void
    {
        $headers = $this->respondWith(debug: true);

        self::assertSame('DENY', $headers->get('X-Frame-Options'));
        self::assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        self::assertSame('same-origin', $headers->get('Referrer-Policy'));
    }

    public function testCspOnlyInProd(): void
    {
        // dev/test (debug): CSP wyłączone, bo psułoby inline skrypty profilera
        self::assertNull($this->respondWith(debug: true)->get('Content-Security-Policy'));

        // prod (bez debug): CSP obecne, z twardym frame-ancestors
        $csp = $this->respondWith(debug: false)->get('Content-Security-Policy');
        self::assertNotNull($csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    private function respondWith(bool $debug): \Symfony\Component\HttpFoundation\ResponseHeaderBag
    {
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        (new SecurityHeadersSubscriber($debug))->onResponse($event);

        return $event->getResponse()->headers;
    }
}
