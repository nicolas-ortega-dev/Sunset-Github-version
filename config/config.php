<?php
declare(strict_types=1);

// Configuración de Sunset. Valores por defecto de XAMPP (usuario root, sin contraseña).
return [
    'app' => [
        'name'      => 'Sunset',
        'debug'     => false,   // false al presentar, para no mostrar errores técnicos
        'demo_mode' => true,   // activa las librerías y ofertas de demostración
    ],
    'openlibrary' => [
        // Open Library pide identificar a quien consulta su API.
        'user_agent' => 'Sunset/1.0 (proyecto academico AIEP; nicolas.ortega.8a@gmail.com)',
        'delay_ms'   => 400,    // pausa minima entre peticiones
        'ssl_verify' => true,   // poner false SOLO si aparece el error de certificado SSL (60)
    ],
    'links' => [
        // {q} se reemplaza por el titulo y autor del libro.
        'buscalibre_search' => 'https://www.buscalibre.cl/libros/search?q={q}',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'sunset',
        'user' => 'root',
        'pass' => '',
    ],
];
