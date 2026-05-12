<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class FfeCompetService
{
    const BASE_URL = 'https://ffecompet.ffe.com';

    // Headers identiques à Chrome 120 sur Windows
    const BROWSER_HEADERS = [
        'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language'           => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding'           => 'gzip, deflate, br',
        'Connection'                => 'keep-alive',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Fetch-Dest'            => 'document',
        'Sec-Fetch-Mode'            => 'navigate',
        'Sec-Fetch-Site'            => 'none',
        'Sec-Fetch-User'            => '?1',
        'Sec-Ch-Ua'                 => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'Sec-Ch-Ua-Mobile'          => '?0',
        'Sec-Ch-Ua-Platform'        => '"Windows"',
    ];

    private Client $client;
    private CookieJar $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies'         => $this->cookieJar,
            'allow_redirects' => ['track_redirects' => true, 'max' => 10],
            'timeout'         => 30,
            'decode_content'  => true,
            'headers'         => self::BROWSER_HEADERS,
        ]);
    }

    public function downloadEngagements(string $login, string $password, string $numeroConcours): string
    {
        $this->authenticate($login, $password);
        return $this->fetchExcel($numeroConcours);
    }

    private function authenticate(string $login, string $password): void
    {
        try {
            // 1. GET page d'accueil pour récupérer les cookies Cloudflare
            $this->client->get(self::BASE_URL . '/');

            // 2. GET espace_perso — redirige vers la page de login CAS
            $response = $this->client->get(self::BASE_URL . '/espace_perso');
            $html     = (string) $response->getBody();
            $finalUrl = $this->getFinalUrl($response, self::BASE_URL . '/espace_perso');

            // 3. Résoudre l'URL de login depuis l'action du formulaire
            $formAction = $this->extractFormAction($html);
            $loginUrl   = $formAction
                ? $this->resolveUrl($finalUrl, $formAction)
                : $this->resolveUrl($finalUrl, 'login');

            // 4. Tokens CAS
            $formData = [
                'username' => $login,
                'password' => $password,
                '_eventId' => 'submit',
            ];

            $execution = $this->extractHiddenField($html, 'execution');
            if ($execution !== null) {
                $formData['execution'] = $execution;
            }
            $lt = $this->extractHiddenField($html, 'lt');
            if ($lt !== null) {
                $formData['lt'] = $lt;
            }

            // 5. POST login
            $response = $this->client->post($loginUrl, [
                'form_params' => $formData,
                'headers'     => [
                    'Referer'         => $finalUrl,
                    'Origin'          => parse_url($finalUrl, PHP_URL_SCHEME) . '://' . parse_url($finalUrl, PHP_URL_HOST),
                    'Sec-Fetch-Site'  => 'same-origin',
                    'Content-Type'    => 'application/x-www-form-urlencoded',
                ],
            ]);

            $body = (string) $response->getBody();

            if (str_contains($body, 'id="fm1"') || str_contains($body, 'id="username"')) {
                throw new RuntimeException('Identifiants FFE Compet invalides. Vérifiez votre login et mot de passe.');
            }

            // 6. Naviguer vers espace_perso pour finaliser la session et obtenir les cookies
            $this->client->get(self::BASE_URL . '/espace_perso');

        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de se connecter à FFE Compet : ' . $e->getMessage());
        }
    }

    private function fetchExcel(string $numeroConcours): string
    {
        try {
            $concoursUrl = self::BASE_URL . '/concours/' . $numeroConcours . '/all/engagements';
            $downloadUrl = self::BASE_URL . '/concours/' . $numeroConcours . '/all/xls?club=all&discipline=all&typeEng=';

            // Visiter d'abord la page du concours pour avoir un Referer valide
            $this->client->get($concoursUrl, [
                'headers' => ['Referer' => self::BASE_URL . '/espace_perso'],
            ]);

            // Télécharger le fichier Excel
            $response = $this->client->get($downloadUrl, [
                'headers' => [
                    'Referer'        => $concoursUrl,
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Site' => 'same-origin',
                ],
            ]);

            $content = (string) $response->getBody();

            if (empty(trim($content))) {
                throw new RuntimeException('Le fichier téléchargé depuis FFE Compet est vide. Vérifiez le numéro de concours et vos droits d\'accès.');
            }

            if (str_contains($content, 'id="fm1"') || str_contains($content, 'id="username"')) {
                throw new RuntimeException('Session FFE Compet expirée ou accès refusé. Réessayez.');
            }

            if (str_contains($content, 'Just a moment') || str_contains($content, 'cf-browser-verification')) {
                throw new RuntimeException('FFE Compet bloque la requête (protection Cloudflare). Utilisez l\'import manuel par fichier.');
            }

            return $content;
        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de télécharger le fichier FFE Compet : ' . $e->getMessage());
        }
    }

    private function extractFormAction(string $html): ?string
    {
        if (preg_match('/<form[^>]+id=["\']fm1["\'][^>]*action=["\']([^"\']+)["\']/', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        if (preg_match('/<form[^>]+action=["\']([^"\']+)["\'][^>]*id=["\']fm1["\']/', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        if (preg_match('/<form[^>]+method=["\']post["\'][^>]*action=["\']([^"\']+)["\']/', $html, $m)) {
            return html_entity_decode($m[1]);
        }
        return null;
    }

    private function getFinalUrl($response, string $fallback): string
    {
        $redirects = $response->getHeader('X-Guzzle-Redirect-History');
        if (! empty($redirects)) {
            return end($redirects);
        }
        return $fallback;
    }

    private function resolveUrl(string $base, string $relative): string
    {
        if (preg_match('#^https?://#', $relative)) {
            return $relative;
        }
        $parts  = parse_url($base);
        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (str_starts_with($relative, '/')) {
            return $origin . $relative;
        }
        $dir = isset($parts['path']) ? dirname($parts['path']) : '/';
        return $origin . rtrim($dir, '/') . '/' . $relative;
    }

    private function extractHiddenField(string $html, string $fieldName): ?string
    {
        if (preg_match('/<input[^>]+name=["\']' . preg_quote($fieldName, '/') . '["\'][^>]+value=["\']([^"\']*)["\']/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<input[^>]+value=["\']([^"\']*)["\'][^>]+name=["\']' . preg_quote($fieldName, '/') . '["\']/', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
