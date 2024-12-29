<?php
class MACP_HTML_Minifier {
    private $options = [
        'remove_comments' => true,
        'remove_whitespace' => true,
        'remove_blank_lines' => true,
        'compress_js' => true,
        'compress_css' => true,
        'preserve_conditional_comments' => true
    ];

    private $preserved_tags = [
        'pre',
        'textarea',
        'script',
        'style'
    ];

    public function __construct($options = []) {
        $this->options = array_merge($this->options, $options);
    }

    public function minify($html) {
        if (empty($html)) {
            return $html;
        }

        // Store preserved content
        $preservedTokens = [];
        
        // Preserve conditional comments
        if ($this->options['preserve_conditional_comments']) {
            $html = preg_replace_callback('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', function($matches) use (&$preservedTokens) {
                $token = '<!--PRESERVED' . count($preservedTokens) . '-->';
                $preservedTokens[$token] = $matches[0];
                return $token;
            }, $html);
        }

        // Preserve content in special tags
        foreach ($this->preserved_tags as $tag) {
            $html = preg_replace_callback('/<' . $tag . '([^>]*?)>(.*?)<\/' . $tag . '>/is', function($matches) use (&$preservedTokens) {
                $token = '<!--PRESERVED' . count($preservedTokens) . '-->';
                $preservedTokens[$token] = $matches[0];
                return $token;
            }, $html);
        }

        // Remove HTML comments (not containing IE conditional comments)
        if ($this->options['remove_comments']) {
            $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        }

        // Remove whitespace
        if ($this->options['remove_whitespace']) {
            // Remove whitespace between HTML tags
            $html = preg_replace('/>\s+</s', '><', $html);
            
            // Remove whitespace at the start of HTML tags
            $html = preg_replace('/\s+>/s', '>', $html);
            
            // Remove whitespace at the end of HTML tags
            $html = preg_replace('/<\s+/s', '<', $html);
            
            // Compress multiple spaces to a single space
            $html = preg_replace('/\s{2,}/s', ' ', $html);
            
            // Remove spaces around common HTML elements
            $html = preg_replace('/\s+(<\/?(?:img|input|br|hr|meta|link)([^>]*)>)\s+/is', '$1', $html);
            
            // Clean up whitespace around block elements
            $html = preg_replace('/\s+(<\/?(?:div|p|table|tr|td|th|ul|ol|li|h[1-6]|header|footer|section|article)(?:\s[^>]*)?>)\s+/is', '$1', $html);
        }

        // Remove blank lines
        if ($this->options['remove_blank_lines']) {
            $html = preg_replace("/^\s+/m", "", $html);
            $html = preg_replace("/\n\s+/m", "\n", $html);
            $html = preg_replace("/\n+/s", "\n", $html);
        }

        // Restore preserved content
        foreach ($preservedTokens as $token => $content) {
            $html = str_replace($token, $content, $html);
        }

        // Final cleanup
        $html = trim($html);

        return $html;
    }

    private function minify_js($script) {
        if (preg_match('/<script[^>]*>(.*?)<\/script>/is', $script, $matches)) {
            $js = $matches[1];
            // Basic JS minification
            $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
            $js = preg_replace('/\s+/', ' ', $js);
            $js = str_replace(['; ', ' {', '{ ', ' }', '} ', ', ', ' (', ') '], [';', '{', '{', '}', '}', ',', '(', ')'], $js);
            return str_replace($matches[1], $js, $script);
        }
        return $script;
    }

    private function minify_css($style) {
        if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $style, $matches)) {
            $css = $matches[1];
            // Basic CSS minification
            $css = preg_replace('/\/\*(?:.*?)*?\*\//', '', $css);
            $css = preg_replace('/\s+/', ' ', $css);
            $css = str_replace([': ', ' {', '{ ', ' }', '} ', ', ', ' ;'], [':', '{', '{', '}', '}', ',', ';'], $css);
            return str_replace($matches[1], $css, $style);
        }
        return $style;
    }
}