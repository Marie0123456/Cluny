<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class FfeCompetService
{
    const BASE_URL = 'https://ffecompet.ffe.com';

    private Client $client;
    private CookieJar $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies'         => $this->cookieJar,
            'allow_redirects' => ['track_redirects' => true, 'max' => 10],
            'timeout'         => 30,
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9',
            ],
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
            // GET espace_perso — FFE Compet redirige vers la page de login CAS
            $response  = $this->client->get(self::BASE_URL . '/espace_perso');
            $html      = (string) $response->getBody();
            $finalUrl  = $this->getFinalUrl($response, self::BASE_URL . '/espace_perso');

            // Extraire l'action du formulaire et la résoudre en URL absolue
            $formAction = $this->extractFormAction($html);
            $loginUrl   = $formAction
                ? $this->resolveUrl($finalUrl, $formAction)
                : $this->resolveUrl($finalUrl, 'login');

            // Tokens CAS cachés
            $formData = [
                'username'  => $login,
                'password'  => $password,
                '_eventId'  => 'submit',
            ];

            $execution = $this->extractHiddenField($html, 'execution');
            if ($execution !== null) {
                $formData['execution'] = $execution;
            }

            $lt = $this->extractHiddenField($html, 'lt');
            if ($lt !== null) {
                $formData['lt'] = $lt;
            }

            $response = $this->client->post($loginUrl, [
                'form_params' => $formData,
                'headers'     => ['Referer' => $finalUrl],
            ]);

            $body = (string) $response->getBody();

            if (str_contains($body, 'id="fm1"') || str_contains($body, 'id="username"')) {
                throw new RuntimeException('Identifiants FFE Compet invalides. Vérifiez votre login et mot de passe.');
            }

        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de se connecter à FFE Compet : ' . $e->getMessage());
        }
    }

    private function fetchExcel(string $numeroConcours): string
    {
        try {
            $url = self::BASE_URL . '/concours/' . $numeroConcours . '/all/xls?club=all&discipline=all&typeEng=';

            $response = $this->client->get($url);
            $content  = (string) $response->getBody();

            if (empty(trim($content))) {
                throw new RuntimeException('Le fichier téléchargé depuis FFE Compet est vide. Vérifiez le numéro de concours et vos droits d\'accès.');
            }

            if (str_contains($content, 'id="fm1"') || str_contains($content, 'id="username"')) {
                throw new RuntimeException('Session FFE Compet expirée ou accès refusé. Réessayez.');
            }

            return $content;
        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de télécharger le fichier FFE Compet : ' . $e->getMessage());
        }
    }

    private function extractFormAction(string $html): ?string
    {
        if (preg_match('/<form[^>]+id=["\']fm1["\'][^>]*action=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<form[^>]+action=["\']([^"\']+)["\'][^>]*id=["\']fm1["\']/', $html, $m)) {
            return $m[1];
        }
        // Fallback : premier form avec method=post
        if (preg_match('/<form[^>]+method=["\']post["\'][^>]*action=["\']([^"\']+)["\']/', $html, $m)) {
            return $m[1];
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
        // Déjà absolu
        if (preg_match('#^https?://#', $relative)) {
            return $relative;
        }
        $parts = parse_url($base);
        $origin = $parts['scheme'] . '://' . $parts['host'];

        // Relatif à la racine
        if (str_starts_with($relative, '/')) {
            return $origin . $relative;
        }
        // Relatif au répertoire courant
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
