<?php
/**
 * @author Romila Raluca
 */

declare(strict_types=1);

namespace App\Config;

class Constants
{
    public const APP_NAME = 'Baby Info';
    public const APP_VERSION = '1.0.0';
    public const TIMEZONE = 'Europe/Bucharest';
    public const UPLOAD_MAX_SIZE = 50 * 1024 * 1024;
    public const UPLOAD_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'audio/mpeg', 'text/plain'];
    public const SESSION_LIFETIME = 7200;
    public const CSRF_TOKEN_LENGTH = 32;
    public const INVITE_TOKEN_LENGTH = 48;
    public const SHARE_TOKEN_LENGTH = 16;
    public const INVITE_EXPIRY_HOURS = 72;
    public const RESET_EXPIRY_MINUTES = 60;
    public const RSS_ITEMS_LIMIT = 50;
}