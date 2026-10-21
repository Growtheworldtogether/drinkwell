<?php

namespace App\Support;

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PagesController;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Search across the public pages. Each page is rendered as visitors see it, split into blocks at every heading
 * and question, and the blocks are matched against the words typed. Nothing is hand-copied, so the results
 * always match what the pages say.
 */
class SiteSearch
{
    /** Words that carry no meaning on their own. */
    private const STOP = ['a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'can', 'do', 'does', 'for', 'from', 'get', 'how', 'i', 'if', 'in', 'is', 'it', 'me', 'my', 'of', 'on', 'or', 'the', 'to', 'what', 'when', 'where', 'who', 'why', 'will', 'with', 'you', 'your'];

    /** Route name => page label, in the order results are preferred when scores tie. */
    private const PAGES = [
        'questions' => 'Questions',
        'program' => 'How it works',
        'earnings' => 'Your earnings',
        'locations' => 'Locations',
        'home' => 'Home',
        'contact' => 'Contact us',
        'terms' => 'Terms',
        'privacy' => 'Privacy',
    ];

    /** @return list<array{page: string, url: string, heading: string, snippet: string}> */
    public function search(string $query, int $limit = 10): array
    {
        $terms = $this->terms($query);
        if ($terms === []) {
            return [];
        }

        $scored = [];
        foreach ($this->index() as $order => $block) {
            $heading = mb_strtolower($block['heading']);
            $context = mb_strtolower($block['section'].' '.$block['url']);
            $body = mb_strtolower($block['body']);
            $hit = 0;
            $score = 0;
            foreach ($terms as $term) {
                $inHead = $this->has($heading, $term);
                $inContext = $this->has($context, $term);
                $inBody = $this->has($body, $term);
                if ($inHead || $inContext || $inBody) {
                    $hit++;
                    $score += ($inHead ? 6 : 0) + ($inContext ? 4 : 0) + min(substr_count($body, $this->stem($term)), 4);
                }
            }
            // The legal pages mention almost everything; they only come first when nothing else matches.
            if (in_array($block['page'], ['Terms', 'Privacy'], true)) {
                $score -= 8;
            }
            if ($hit > 0) {
                $scored[] = ['all' => $hit === count($terms), 'hit' => $hit, 'score' => $score, 'order' => $order, 'block' => $block];
            }
        }

        // Blocks that contain every word come first; if none do, show the closest ones.
        $full = array_values(array_filter($scored, fn ($r) => $r['all']));
        $pool = $full !== [] ? $full : $scored;
        usort($pool, fn ($a, $b) => [$b['hit'], $b['score'], $a['order']] <=> [$a['hit'], $a['score'], $b['order']]);

        $out = [];
        $seen = [];
        foreach ($pool as $r) {
            $b = $r['block'];
            $key = $b['url'].'|'.$b['heading'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['page' => $b['page'].($b['section'] !== '' ? ' · '.$b['section'] : ''), 'url' => $b['url'], 'heading' => $b['heading'], 'snippet' => $this->snippet($b['body'], $terms)];
            if (count($out) === $limit) {
                break;
            }
        }

        return $out;
    }

    /** @return list<string> */
    public function terms(string $query): array
    {
        $words = preg_split('/[^\pL\pN$]+/u', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, self::STOP, true));

        return array_values(array_unique(array_slice($words, 0, 8)));
    }

    /** @return list<array{page: string, section: string, url: string, heading: string, body: string}> */
    public function index(): array
    {
        return Cache::remember('site-search:v5:'.(auth()->check() ? 'in' : 'out'), now()->addMinutes(10), function () {
            $blocks = [];
            foreach (self::PAGES as $route => $label) {
                foreach ($this->blocks($this->render($route), route($route), $label) as $block) {
                    $blocks[] = $block;
                }
            }

            return $blocks;
        });
    }

    private function render(string $route): string
    {
        $view = $route === 'contact' ? app(ContactController::class)->show() : app(PagesController::class)->{$route}();

        return $view->render();
    }

    /** @return list<array{page: string, url: string, heading: string, body: string}> */
    private function blocks(string $html, string $url, string $page): array
    {
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $main = (new DOMXPath($doc))->query('//main')->item(0);
        if (! $main instanceof DOMElement) {
            return [];
        }

        $blocks = [];
        $current = null;
        $section = '';
        $walk = function (DOMNode $node, string $anchor) use (&$walk, &$blocks, &$current, &$section, $url, $page): void {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $tag = strtolower($child->tagName);
                    if (in_array($tag, ['script', 'style', 'form', 'nav', 'button', 'template', 'svg', 'input', 'select', 'textarea'], true)
                        || $child->getAttribute('aria-hidden') === 'true'
                        || preg_match('/(^|\s)(cc-photo|cc-rows|cc-story-dots|af-btn|af-ctas|cc-btn-row|cc-calc[\w-]*)(\s|$)/', $child->getAttribute('class'))) {
                        continue;
                    }
                    $here = $child->getAttribute('id') !== '' ? $child->getAttribute('id') : $anchor;
                    if (in_array($tag, ['h1', 'h2', 'h3', 'summary'], true)) {
                        if ($current !== null && $current['body'] !== '') {
                            $blocks[] = $current;
                        }
                        $heading = $this->clean($this->text($child));
                        if ($tag === 'summary') {
                            $here = 'q-'.Str::slug($heading);
                        }
                        if ($tag === 'h1' || $tag === 'h2') {
                            $section = $heading;
                        }
                        $context = in_array($tag, ['h3', 'summary'], true) && $section !== $heading && $section !== $page ? $section : '';
                        $current = ['page' => $page, 'section' => $context, 'url' => $url.($here !== '' ? '#'.$here : ''), 'heading' => $heading, 'body' => ''];

                        continue;
                    }
                    $walk($child, $here);
                } elseif ($child->nodeType === XML_TEXT_NODE && $current !== null) {
                    $t = trim($child->textContent);
                    if ($t !== '') {
                        $current['body'] = $this->clean($current['body'].' '.$t);
                    }
                }
            }
        };
        $walk($main, '');
        if ($current !== null && $current['body'] !== '') {
            $blocks[] = $current;
        }

        return array_values(array_filter($blocks, fn ($b) => $b['heading'] !== ''));
    }

    /** Text of an element with a space between its parts, so stacked heading lines do not run together. */
    private function text(DOMNode $node): string
    {
        $parts = [];
        foreach ($node->childNodes as $child) {
            $parts[] = $child->nodeType === XML_TEXT_NODE ? $child->textContent : $this->text($child);
        }

        return implode(' ', $parts);
    }

    private function clean(string $s): string
    {
        $s = preg_replace('/\s+/u', ' ', str_replace("\u{2011}", '-', $s)) ?? $s;

        return trim(preg_replace('/\s+([,.;:!?])/u', '$1', $s) ?? $s);
    }

    private function stem(string $term): string
    {
        return mb_strlen($term) > 3 && str_ends_with($term, 's') ? mb_substr($term, 0, -1) : $term;
    }

    private function has(string $hay, string $term): bool
    {
        return str_contains($hay, $this->stem($term));
    }

    /** A short piece of the block around the first match, with the matched words marked. Returns safe HTML. */
    private function snippet(string $body, array $terms): string
    {
        $lower = mb_strtolower($body);
        $at = null;
        foreach ($terms as $term) {
            $p = mb_strpos($lower, $this->stem($term));
            if ($p !== false && ($at === null || $p < $at)) {
                $at = $p;
            }
        }
        $start = max(0, ($at ?? 0) - 60);
        $text = mb_substr($body, $start, 220);
        if ($start > 0) {
            $text = '…'.ltrim(mb_substr($text, (int) mb_strpos($text.' ', ' ')));
        }
        if ($start + 220 < mb_strlen($body)) {
            $text = rtrim(mb_substr($text, 0, (int) mb_strrpos($text, ' '))).'…';
        }

        $safe = e($text);
        $pattern = implode('|', array_map(fn ($t) => preg_quote(e($this->stem($t)), '/'), $terms));

        return preg_replace('/('.$pattern.')/iu', '<mark>$1</mark>', $safe) ?? $safe;
    }
}
