<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TileProxyController extends Controller
{
    private function normalizeTileY(string $y): array
    {
        // Acepta "123.png" y "123@2x.png" (Leaflet puede pedir retina)
        if (!preg_match('/^(?<num>\d+)(?<retina>@2x)?\.png$/', $y, $m)) {
            return ['y' => '0.png', 'yNumber' => 0];
        }

        $num = (int) $m['num'];
        $retina = $m['retina'] ?? '';

        return [
            'y' => $num . ($retina ?: '') . '.png',
            'yNumber' => $num,
        ];
    }

    public function cartoDark(Request $request, int $z, int $x, string $y)
    {
        ['y' => $yClean, 'yNumber' => $yNumber] = $this->normalizeTileY($y);
        $subdomains = ['a', 'b', 'c', 'd'];
        $sub = $subdomains[($x + $yNumber + $z) % 4] ?? 'a';

        $url = "https://{$sub}.basemaps.cartocdn.com/dark_all/{$z}/{$x}/{$yClean}";

        return $this->proxyPng($url);
    }

    public function stadiaDark(Request $request, int $z, int $x, string $y)
    {
        $key = (string) config('services.stadiamaps.key', '');
        if ($key === '') {
            return response('Missing STADIA_KEY', 500);
        }

        ['y' => $yClean] = $this->normalizeTileY($y);
        // Stadia usa {y}{r}.png; acá aceptamos y con o sin @2x
        $url = "https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{$z}/{$x}/{$yClean}?api_key={$key}";

        return $this->proxyPng($url);
    }

    private function proxyPng(string $url): Response
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: image/avif,image/webp,image/apng,image/png,image/*,*/*;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            return response('', 204);
        }

        return response($body, 200, [
            'Content-Type' => $contentType !== '' ? $contentType : 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

