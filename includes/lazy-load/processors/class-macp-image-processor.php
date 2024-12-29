<?php
class MACP_Image_Processor {
    private $excluded_classes = ['no-lazy', 'skip-lazy'];

    public function process($html) {
        return preg_replace_callback(
            '/<img[^>]*>/i',
            [$this, 'process_image'],
            $html
        );
    }

    private function process_image($matches) {
        $img = $matches[0];

        // Skip if already processed or excluded
        if ($this->should_skip($img)) {
            return $img;
        }

        // Replace src with data-src
        $img = preg_replace(
            '/\ssrc=(["\'])(.*?)\1/i',
            ' src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E" data-src=$1$2$1',
            $img
        );

        // Handle srcset
        if (strpos($img, 'srcset') !== false) {
            $img = preg_replace('/srcset=(["\'])(.*?)\1/i', 'data-srcset=$1$2$1', $img);
        }

        // Add lazy loading class
        if (strpos($img, 'class=') !== false) {
            $img = preg_replace('/class=(["\'])(.*?)\1/i', 'class=$1$2 macp-lazy$1', $img);
        } else {
            $img = str_replace('<img', '<img class="macp-lazy"', $img);
        }

        return $img;
    }

    private function should_skip($img) {
        foreach ($this->excluded_classes as $class) {
            if (preg_match('/class=["\'][^"\']*\b' . $class . '\b[^"\']*["\']/', $img)) {
                return true;
            }
        }
        return strpos($img, 'data-src') !== false || 
               strpos($img, 'macp-lazy') !== false;
    }
}
