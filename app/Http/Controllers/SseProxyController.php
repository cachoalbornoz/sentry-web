<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseProxyController extends Controller
{
    public function dashboard(Request $request): StreamedResponse
    {
        $token = (string) $request->session()->get('api_token', '');
        $baseUrl = rtrim((string) config('services.sentry_api.base_url'), '/');

        $url = $baseUrl.'/dashboard/sse';

        return response()->stream(function () use ($url, $token) {
            // Proxy SSE usando cURL para streaming continuo.
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/event-stream',
                    'Cache-Control: no-cache',
                    'Authorization: Bearer '.$token,
                ],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;
                    @ob_flush();
                    flush();
                    return strlen($data);
                },
                CURLOPT_TIMEOUT => 0,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

