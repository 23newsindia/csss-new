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

        // Get current classes
        $classes = $this->get_classes($img);
        
        // Add our lazy load class
        $classes[] = 'macp-lazy';

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

        // Update class attribute
        $img = $this->update_class_attribute($img, $classes);

        // Add loading attribute
        if (strpos($img, 'loading=') === false) {
            $img = str_replace('<img', '<img loading="lazy"', $img);
        }

        return $img;
    }

    private function should_skip($img) {
        // Check for excluded classes
        foreach ($this->excluded_classes as $class) {
            if (preg_match('/class=["\'][^"\']*\b' . $class . '\b[^"\']*["\']/', $img)) {
                return true;
            }
        }

        // Skip if already processed
        if (strpos($img, 'data-src') !== false || 
            strpos($img, 'macp-lazy') !== false) {
            return true;
        }

        // Skip if it's a king-lazy that's already loaded
        if (strpos($img, 'king-lazy loaded') !== false) {
            return true;
        }

        return false;
    }

    private function get_classes($img) {
        $classes = [];
        if (preg_match('/class=["\'](.*?)["\']/i', $img, $matches)) {
            $classes = array_filter(explode(' ', $matches[1]));
        }
        return $classes;
    }

    private function update_class_attribute($img, $classes) {
        $class_string = implode(' ', array_unique($classes));
        if (strpos($img, 'class=') !== false) {
            $img = preg_replace('/class=(["\'])(.*?)\1/i', 'class="' . $class_string . '"', $img);
        } else {
            $img = str_replace('<img', '<img class="' . $class_string . '"', $img);
        }
        return $img;
    }
}