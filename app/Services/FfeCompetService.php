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
            'base_uri' => self::BASE_URL,
            'cookies' => $this->cookieJar,
            'allow_redirects' => true,
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9',
            ],
        ]);
    }

    /**
     * Authenticate and download the engagements Excel file for a given contest.
     *
     * @throws RuntimeException
     */
    public function downloadEngagements(string $login, string $password, string $numeroConcours): string
    {
        $this->authenticate($login, $password);
        return $this->fetchExcel($numeroConcours);
    }

    private function authenticate(string $login, string $password): void
    {
        try {
            // GET login page to retrieve any hidden CAS token (execution field)
            $response = $this->client->get('/');
            $html = (string) $response->getBody();

            // If already redirected to login page, extract execution token
            $execution = $this->extractHiddenField($html, 'execution');

            $formData = [
                'username' => $login,
                'password' => $password,
                '_eventId' => 'submit',
            ];

            if ($execution !== null) {
                $formData['execution'] = $execution;
            }

            // POST credentials to the login endpoint
            $response = $this->client->post('/login', [
                'form_params' => $formData,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Referer' => self::BASE_URL . '/',
                ],
            ]);

            $body = (string) $response->getBody();

            // Detect login failure by checking for the login form still being present
            if (str_contains($body, 'id="fm1"') || str_contains($body, 'loginForm')) {
                throw new RuntimeException('Identifiants FFE Compet invalides. Vérifiez votre login et mot de passe.');
            }
        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de se connecter à FFE Compet : ' . $e->getMessage());
        }
    }

    private function fetchExcel(string $numeroConcours): string
    {
        try {
            $url = '/concours/' . $numeroConcours . '/all/xls?club=all&discipline=all&typeEng=';

            $response = $this->client->get($url);
            $content = (string) $response->getBody();

            if (empty(trim($content))) {
                throw new RuntimeException('Le fichier téléchargé depuis FFE Compet est vide. Vérifiez le numéro de concours et vos droits d\'accès.');
            }

            // Detect if we were redirected to the login page (session expired)
            if (str_contains($content, 'id="fm1"') || str_contains($content, 'loginForm')) {
                throw new RuntimeException('Session FFE Compet expirée. Réessayez.');
            }

            return $content;
        } catch (GuzzleException $e) {
            throw new RuntimeException('Impossible de télécharger le fichier FFE Compet : ' . $e->getMessage());
        }
    }

    private function extractHiddenField(string $html, string $fieldName): ?string
    {
        if (preg_match('/<input[^>]+name=["\']' . preg_quote($fieldName, '/') . '["\'][^>]+value=["\']([^"\']*)["\']/', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<input[^>]+value=["\']([^"\']*)["\'][^>]+name=["\']' . preg_quote($fieldName, '/') . '["\']/', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
