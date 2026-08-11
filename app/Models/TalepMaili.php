<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalepMaili extends Model
{
    protected $table = 'talep_mailleri';

    protected $fillable = [
        'talep_id',
        'account',
        'folder',
        'uid',
        'message_id',
        'message_hash',
        'thread_key',
        'mail_baslik',
        'subject_normalized',
        'from_email',
        'from_name',
        'to_emails_json',
        'cc_emails_json',
        'in_reply_to',
        'references_header',
        'received_at',
        'direction',
        'attachment_names_json',
        'linked_by',
        'sync_status',
        'mail_icerik',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'to_emails_json' => 'array',
        'cc_emails_json' => 'array',
        'attachment_names_json' => 'array',
    ];

    public function talep()
    {
        return $this->belongsTo(Talep::class);
    }

    public function mailAttachments()
    {
        return $this->hasMany(TalepMailAttachment::class, 'talep_maili_id');
    }

    public function getMailIcerikHtmlAttribute(): string
    {
        return self::formatMailBody($this->mail_icerik);
    }

    public function getMailIcerikTextAttribute(): string
    {
        return self::mailBodyToPlainText($this->mail_icerik);
    }

    public static function mailBodyForStorage(?string $htmlBody, ?string $textBody): string
    {
        $htmlBody = trim((string) $htmlBody);
        if ($htmlBody !== '') {
            return self::sanitizeMailHtml($htmlBody);
        }

        return self::plainTextToHtml((string) $textBody);
    }

    public static function formatMailBody(?string $body): string
    {
        $body = trim((string) $body);
        if ($body === '') {
            return '';
        }

        $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (self::containsHtmlTags($body)) {
            return self::sanitizeMailHtml($body);
        }

        if ($decoded !== $body && self::containsHtmlTags($decoded)) {
            return self::sanitizeMailHtml($decoded);
        }

        return self::plainTextToHtml($body);
    }

    public static function mailBodyToPlainText(?string $body): string
    {
        $body = trim((string) $body);
        if ($body === '') {
            return '';
        }

        $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $source = self::containsHtmlTags($body) || self::containsHtmlTags($decoded) ? $decoded : $body;
        $source = preg_replace('/<\s*br\s*\/?>/i', "\n", $source) ?: $source;
        $source = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $source) ?: $source;
        $text = html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?: $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?: $text;

        return trim($text);
    }

    private static function plainTextToHtml(string $body): string
    {
        $html = e($body);
        $html = preg_replace(
            '~(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $html
        ) ?: $html;

        return nl2br($html, false);
    }

    private static function sanitizeMailHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|form|textarea|select)[^>]*>.*?</\1>#is', '', $html) ?: $html;
        $html = preg_replace('#<(script|style|iframe|object|embed|meta|link|base|input|button)[^>]*\/?>#is', '', $html) ?: $html;

        $oldErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="mail-body-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($oldErrors);

        $root = $dom->getElementById('mail-body-root');
        if (! $root) {
            return self::plainTextToHtml(self::mailBodyToPlainText($html));
        }

        self::sanitizeNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $node->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::allowedMailTags(), true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                self::sanitizeNode($node);
                return;
            }

            self::sanitizeElementAttributes($child);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeElementAttributes(\DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';
        $style = self::safeMailStyle($element->getAttribute('style'));
        $tableSpans = [];
        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $tableSpans[$attribute] = (int) $element->getAttribute($attribute);
            }
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($tag === 'a' && preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $value = $tableSpans[$attribute] ?? 0;
                if ($value > 1 && $value <= 20) {
                    $element->setAttribute($attribute, (string) $value);
                }
            }
        }

        if ($style !== '') {
            $element->setAttribute('style', $style);
        }
    }

    private static function safeMailStyle(string $style): string
    {
        $rules = [];
        if (preg_match('/font-weight\s*:\s*(bold|[6-9]00)/i', $style)) {
            $rules[] = 'font-weight:700';
        }
        if (preg_match('/font-style\s*:\s*italic/i', $style)) {
            $rules[] = 'font-style:italic';
        }
        if (preg_match('/text-decoration[^;]*underline/i', $style)) {
            $rules[] = 'text-decoration:underline';
        }

        return implode(';', $rules);
    }

    private static function containsHtmlTags(string $value): bool
    {
        return preg_match('/<\/?(p|div|br|span|strong|b|em|i|u|ul|ol|li|blockquote|table|thead|tbody|tr|td|th|a|font|html|body|h[1-6])\b[^>]*>/i', $value) === 1;
    }

    private static function allowedMailTags(): array
    {
        return [
            'a',
            'b',
            'blockquote',
            'br',
            'div',
            'em',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'i',
            'li',
            'ol',
            'p',
            'small',
            'span',
            'strong',
            'table',
            'tbody',
            'td',
            'th',
            'thead',
            'tr',
            'u',
            'ul',
        ];
    }
}
