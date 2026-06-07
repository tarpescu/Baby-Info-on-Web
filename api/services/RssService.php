<?php
/**
 * Genereaza un feed RSS valid din momentele partajate ale unui copil.
 * @author Tarpescu Sergiu
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Constants;

class RssService
{
    /**
     * Construieste XML-ul RSS pentru momentele partajate.
     *
     * @param array $child   Randul din tabela children (folosit pentru titlu/descriere)
     * @param array $moments Lista de momente cu is_shared = 1
     */
    public function build(array $child, array $moments): string
    {
        $childName = trim(($child['first_name'] ?? '') . ' ' . ($child['last_name'] ?? ''));
        $title = Constants::APP_NAME . ' - ' . $childName;
        $description = 'Momente partajate pentru ' . $childName;
        $selfUrl = $this->baseUrl() . '/api/rss/' . (int) $child['id'];
        $now = date(DATE_RSS);

        $items = '';
        foreach ($moments as $moment) {
            $items .= $this->renderItem($moment, (int) $child['id']);
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{$this->esc($title)}</title>
    <link>{$this->esc($this->baseUrl())}</link>
    <atom:link href="{$this->esc($selfUrl)}" rel="self" type="application/rss+xml" />
    <description>{$this->esc($description)}</description>
    <language>ro-ro</language>
    <lastBuildDate>{$now}</lastBuildDate>
{$items}  </channel>
</rss>
XML;
    }

    private function renderItem(array $moment, int $childId): string
    {
        $title = $this->esc($moment['title'] ?? '');
        $body = $this->esc($moment['body'] ?? '');
        $type = $this->esc($moment['type'] ?? 'other');
        $link = $this->esc($this->baseUrl() . '/dashboard#moment-' . (int) $moment['id']);
        $guid = $this->esc($this->baseUrl() . '/api/rss/' . $childId . '#moment-' . (int) $moment['id']);
        $pubDate = date(DATE_RSS, strtotime($moment['happened_at'] ?? 'now'));

        return <<<XML
    <item>
      <title>{$title}</title>
      <link>{$link}</link>
      <guid isPermaLink="false">{$guid}</guid>
      <description>{$body}</description>
      <category>{$type}</category>
      <pubDate>{$pubDate}</pubDate>
    </item>

XML;
    }

    /**
     * Escapeaza textul utilizatorului pentru a produce XML valid
     * (&, <, >, ", ' devin entitati).
     */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
