<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dokłada nagłówki bezpieczeństwa do każdej odpowiedzi (OWASP A02).
 * CSP tylko na produkcji — w dev psułaby inline'owe skrypty paska web profilera.
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
        $headers->set('X-Frame-Options', 'DENY');            // anti-clickjacking (starsze przeglądarki)
        $headers->set('X-Content-Type-Options', 'nosniff');  // bez zgadywania typu MIME
        $headers->set('Referrer-Policy', 'same-origin');

        if (!$this->debug) {
            // script/style 'unsafe-inline': AssetMapper renderuje inline importmap; docelowo (prod++) nonce
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
