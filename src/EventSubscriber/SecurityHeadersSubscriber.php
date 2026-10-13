<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds security headers to every response (OWASP A02).
 * CSP is prod-only, since it would break the web profiler's inline scripts in dev.
 */
final readonly class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-Frame-Options', 'DENY');            // anti-clickjacking (legacy browsers)
        $headers->set('X-Content-Type-Options', 'nosniff');  // no MIME sniffing
        $headers->set('Referrer-Policy', 'same-origin');

        if (!$this->debug) {
            // 'unsafe-inline' is needed for AssetMapper's inline importmap; a nonce would be stricter
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "img-src 'self' data:",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src https://fonts.gstatic.com",
                "script-src 'self' 'unsafe-inline'",
            ]));
        }
    }
}
