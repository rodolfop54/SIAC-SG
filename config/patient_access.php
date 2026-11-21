<?php

return [
    'enabled' => (bool) env('PATIENT_EMAIL_ACCESS', false),
    'brevo_key' => env('BREVO_API_KEY'),
    'from_address' => env('MAIL_FROM_ADDRESS'),
    'from_name' => env('MAIL_FROM_NAME', 'SIAC-SG'),
];
