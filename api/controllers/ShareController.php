<?php
/**
 * Pagina publica per moment partajat: /share/{token}.
 * Accesibila FARA cont. Server-rendered (HTML + meta Open Graph pentru preview social).
 * Arata momentul doar daca token-ul exista si is_shared = 1; altfel 404.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Config\Constants;
use App\Models\MomentModel;

class ShareController extends Controller
{
    private const TYPE_LABEL = [
        'food' => 'Food', 'medical' => 'Medical',
        'photo' => 'Photo', 'friends' => 'Friends', 'sleep' => 'Sleep',
        'voice' => 'Voice', 'other' => 'Other',
    ];

    public function show(array $params): void
    {
        $token = (string) ($params['token'] ?? '');

        $moment = (new MomentModel())->findByShareToken($token);
        if ($moment === null) {
            Response::html($this->notFoundPage(), 404);
        }

        Response::html($this->momentPage($moment));
    }

    private function momentPage(array $m): string
    {
        $childName = trim(($m['child_first'] ?? '') . ' ' . ($m['child_last'] ?? ''));
        $author    = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
        $title     = $this->esc($m['title'] ?? '');
        $body      = $this->esc($m['body'] ?? '');
        $typeLabel = $this->esc(self::TYPE_LABEL[$m['type'] ?? 'other'] ?? ucfirst((string) ($m['type'] ?? 'other')));
        $date      = $this->esc(date('j F Y', strtotime((string) ($m['happened_at'] ?? 'now'))));
        $mediaAbs  = !empty($m['media_url']) ? $this->baseUrl() . $m['media_url'] : null;

        // Meta Open Graph (preview pe social)
        $ogDesc  = $this->esc(mb_substr((string) ($m['body'] ?? $childName), 0, 200));
        $ogImage = $mediaAbs ? '<meta property="og:image" content="' . $this->esc($mediaAbs) . '"/>' : '';
        $pageUrl = $this->esc($this->baseUrl() . '/share/' . (string) ($m['share_token'] ?? ''));

        $mediaBlock = $mediaAbs
            ? '<div class="media"><img src="' . $this->esc($mediaAbs) . '" alt="' . $title . '"/></div>'
            : '';
        $bodyBlock = $body !== '' ? '<p class="body">' . nl2br($body) . '</p>' : '';

        $appName = $this->esc(Constants::APP_NAME);

        return <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{$title} · {$appName}</title>
    <meta name="robots" content="noindex"/>

    <meta property="og:type" content="article"/>
    <meta property="og:site_name" content="{$appName}"/>
    <meta property="og:title" content="{$title}"/>
    <meta property="og:description" content="{$ogDesc}"/>
    <meta property="og:url" content="{$pageUrl}"/>
    {$ogImage}
    <meta name="twitter:card" content="summary_large_image"/>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Courier+Prime&family=Caveat:wght@400;700&family=Special+Elite&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --bg-paper:#e6dfd3; --primary:#321716; --secondary:#9e422c;
            --text-body:#1e1b14; --text-muted:#504443; --outline:#d4c3c1; --tape:rgba(131,166,183,0.7);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background-color:var(--bg-paper);
            background-image:radial-gradient(var(--outline) 0.5px, transparent 0.5px),
                radial-gradient(var(--outline) 0.5px, var(--bg-paper) 0.5px);
            background-size:20px 20px; background-position:0 0,10px 10px;
            color:var(--text-body); font-family:'Courier Prime',monospace; line-height:1.6;
            min-height:100vh; display:flex; flex-direction:column; align-items:center;
            justify-content:center; padding:2rem 1rem;
        }
        .card {
            background:#fff; max-width:560px; width:100%; padding:1.5rem 1.5rem 2rem;
            box-shadow:2px 2px 2px rgba(0,0,0,0.05), 8px 8px 24px rgba(0,0,0,0.12);
            position:relative; transform:rotate(-0.5deg);
        }
        .card::before {
            content:''; position:absolute; top:-14px; left:50%;
            transform:translateX(-50%) rotate(-2deg);
            width:120px; height:28px; background:var(--tape); mix-blend-mode:multiply; opacity:0.85;
        }
        .child { font-family:'Caveat',cursive; font-size:1.4rem; color:var(--text-muted); }
        .type-badge {
            display:inline-block; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px;
            color:var(--primary); border:1px solid var(--outline); border-radius:10px; padding:1px 10px; margin-left:6px;
        }
        h1 { font-family:'Libre Caslon Text',serif; font-size:1.8rem; color:var(--primary); margin:0.6rem 0 0.2rem; }
        .date { font-size:0.85rem; color:var(--text-muted); }
        .media { margin:1rem 0; border:1px solid var(--outline); }
        .media img { width:100%; max-height:420px; object-fit:cover; display:block; }
        .body { margin-top:0.8rem; white-space:pre-wrap; }
        .author { margin-top:1rem; font-family:'Caveat',cursive; font-size:1.1rem; color:var(--text-muted); }
        .footer {
            margin-top:1.6rem; text-align:center; font-family:'Special Elite',cursive;
            font-size:0.75rem; color:var(--text-muted);
        }
        .footer a { color:var(--secondary); text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <span class="child">{$this->esc($childName)}</span><span class="type-badge">{$typeLabel}</span>
        <h1>{$title}</h1>
        <div class="date">{$date}</div>
        {$mediaBlock}
        {$bodyBlock}
        <div class="author">— {$this->esc($author)}</div>
    </div>
    <div class="footer">Partajat prin <a href="/">{$appName}</a></div>
</body>
</html>
HTML;
    }

    private function notFoundPage(): string
    {
        $appName = $this->esc(Constants::APP_NAME);
        return <<<HTML
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Link indisponibil · {$appName}</title>
    <meta name="robots" content="noindex"/>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:wght@400;700&family=Courier+Prime&display=swap" rel="stylesheet"/>
    <style>
        body { background:#e6dfd3; color:#321716; font-family:'Courier Prime',monospace;
            min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:2rem; }
        h1 { font-family:'Libre Caslon Text',serif; font-size:2rem; margin-bottom:0.5rem; }
        a { color:#9e422c; }
    </style>
</head>
<body>
    <h1>Link indisponibil</h1>
    <p>Acest moment nu mai este partajat sau link-ul este invalid.</p>
    <p style="margin-top:1rem;"><a href="/">Inapoi la {$appName}</a></p>
</body>
</html>
HTML;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
