<?php

namespace Modules\Expense\Services;

class ExpenseEvidencePdfHtml
{
    public function render(?string $html): string
    {
        if (! $html) {
            return '';
        }
        if (strip_tags($html) === $html) {
            return nl2br(e($html));
        }
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);

            return $this->node($document->getElementsByTagName('body')->item(0));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function node(?\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return e($node->textContent);
        }
        if (! $node instanceof \DOMElement) {
            return '';
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'img', 'link'], true)) {
            return '';
        }
        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= $this->node($child);
        }
        if (! in_array($tag, ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th'], true)) {
            return $content;
        }
        $attributes = '';
        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $value = filter_var($node->getAttribute($attribute), FILTER_VALIDATE_INT);
                if ($value && $value > 0 && $value <= 100) {
                    $attributes .= ' '.$attribute.'="'.$value.'"';
                }
            }
        }

        return $tag === 'br' ? '<br>' : '<'.$tag.$attributes.'>'.$content.'</'.$tag.'>';
    }
}
