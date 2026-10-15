<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Dokumen REST API E-Book Marketplace',
    description: 'Dokumentasi REST API Interaktif sesuai API Contract'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000/api',
    description: 'Local Development Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan Token Sanctum'
)]
class SwaggerController extends Controller
{
    // 
}
