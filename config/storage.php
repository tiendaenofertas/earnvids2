<?php
// Contabo Object Storage (predeterminado)
define('CONTABO_ACCESS_KEY', '28db939aa009fbe8e9672751b17ae94d');
define('CONTABO_SECRET_KEY', '13c68306f2a5d2e0b58ef09d22f4988f');
define('CONTABO_BUCKET', 'japojes');
define('CONTABO_REGION', 'UnitedStates');
define('CONTABO_ENDPOINT', 'https://usc1.contabostorage.com');

// Rutas locales
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('VIDEO_PATH', UPLOAD_PATH . 'videos/');
define('THUMBNAIL_PATH', UPLOAD_PATH . 'thumbnails/');