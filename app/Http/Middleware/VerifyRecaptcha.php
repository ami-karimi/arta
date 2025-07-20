<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token'); // reCAPTCHA token
        $secret = '6LfWPIUrAAAAACZDGt_AZDSQkLRGiwnF9wdCXSek';

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'توکن کپتچا ارسال نشده است.'
            ], 400);
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token,
        ]);

        $result = $response->json();

        if (!$result['success'] || ($result['score'] ?? 1) < 0.5) {
            return response()->json([
                'success' => false,
                'message' => 'اعتبارسنجی کپتچا ناموفق بود.'
            ], 403);
        }

        return $next($request);
    }
}
