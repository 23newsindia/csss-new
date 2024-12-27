<?php
class MACP_HTML_Minifier {
    private $search = [
        '/\>[^\S ]+/s',     // Strip whitespaces after tags
        '/[^\S ]+\</s',     // Strip whitespaces before tags
        '/(\s)+/s',         // Shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    ];
    
    private $replace = [
        '>',
        '<',
        '\\1',
        ''
    ];

    private $options = [
        'remove_comments' => true,
        'remove_whitespace' => true,
        'remove_blank_lines' => true,
        'compress_js' => true,
        'compress_css' => true
    ];

    public function __construct($options = []) {
        $this->options = array_merge($this->options, $options);
    }

    public function minify($html) {
        if (empty($html)) {
            return $html;
        }

        // Save conditional comments
        $conditionals = [];
        if (preg_match_all('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $conditionals['%%CONDITIONAL' . $i . '%%'] = $match;
                $html = str_replace($match, '%%CONDITIONAL' . $i . '%%', $html);
            }
        }

        // Save textarea and pre content
        $pre_tags = [];
        if (preg_match_all('/<(pre|textarea)[^>]*>.*?<\/\\1>/is', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $pre_tags['%%PRE' . $i . '%%'] = $match;
                $html = str_replace($match, '%%PRE' . $i . '%%', $html);
            }
        }

        // Save script content
        $script_tags = [];
        if (preg_match_all('/<script[^>]*>.*?<\/script>/is', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $script_tags['%%SCRIPT' . $i . '%%'] = $match;
                $html = str_replace($match, '%%SCRIPT' . $i . '%%', $html);
            }
        }

        // Save style content
        $style_tags = [];
        if (preg_match_all('/<style[^>]*>.*?<\/style>/is', $html, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $style_tags['%%STYLE' . $i . '%%'] = $match;
                $html = str_replace($match, '%%STYLE' . $i . '%%', $html);
            }
        }

        // Process main HTML
        if ($this->options['remove_comments']) {
            $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
        }

        if ($this->options['remove_whitespace']) {
            $html = preg_replace($this->search, $this->replace, $html);
            $html = preg_replace('/\s+/', ' ', $html);
        }

        if ($this->options['remove_blank_lines']) {
            $html = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $html);
        }

        // Restore preserved content
        $html = strtr($html, $conditionals);
        $html = strtr($html, $pre_tags);

        // Process and restore scripts
        if ($this->options['compress_js']) {
            foreach ($script_tags as $key => $script) {
                $script = $this->minify_js($script);
                $script_tags[$key] = $script;
            }
        }
        $html = strtr($html, $script_tags);

        // Process and restore styles
        if ($this->options['compress_css']) {
            foreach ($style_tags as $key => $style) {
                $style = $this->minify_css($style);
                $style_tags[$key] = $style;
            }
        }
        $html = strtr($html, $style_tags);

        return trim($html);
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