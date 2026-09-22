<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PwaController extends AbstractController
{
    /**
     * Served from a route rather than a static file so the icon URLs carry the
     * AssetMapper digests and can be cached forever.
     */
    #[Route('/manifest.webmanifest', name: 'app_manifest', methods: ['GET'])]
    public function manifest(Packages $packages): JsonResponse
    {
        $response = new JsonResponse([
            'name' => 'skautIS mobil',
            'short_name' => 'skautIS',
            'description' => 'Členové, jednotky a kontakty ze skautISu v mobilu.',
            'lang' => 'cs',
            'dir' => 'ltr',
            'start_url' => $this->generateUrl('app_dashboard'),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#f4f6f4',
            'theme_color' => '#1b5e20',
            'icons' => [
                [
                    'src' => $packages->getUrl('icons/icon.svg'),
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                ],
                [
                    'src' => $packages->getUrl('icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => $packages->getUrl('icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
                [
                    'src' => $packages->getUrl('icons/icon-512-maskable.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Osoby',
                    'url' => $this->generateUrl('app_person_index'),
                ],
                [
                    'name' => 'Moje jednotka',
                    'url' => $this->generateUrl('app_unit_current'),
                ],
            ],
        ]);

        $response->headers->set('Content-Type', 'application/manifest+json');

        return $response;
    }

    /**
     * Shown by the service worker when a page is requested with no connection.
     */
    #[Route('/offline', name: 'app_offline', methods: ['GET'])]
    public function offline(): Response
    {
        return $this->render('pwa/offline.html.twig');
    }
}
