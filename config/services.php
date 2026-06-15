<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visueco Self-Hosted Services
    |--------------------------------------------------------------------------
    |
    | Visueco TIDAK menggunakan layanan pihak ketiga (AWS/SES/Postmark/Resend/
    | Slack). Seluruh Machine Learning ditangani mandiri oleh container
    | visueco-ml. Kredensial di bawah hanya menunjuk ke service internal.
    |
    */

    'ai_ecosort' => [
        'endpoint' => env('AI_ECOSORT_ENDPOINT', 'http://visueco-ml:8001/predict'),
        'timeout' => (int) env('AI_ECOSORT_TIMEOUT', 30),
        'train_timeout' => (int) env('AI_ECOSORT_TRAIN_TIMEOUT', 600),
    ],

];
