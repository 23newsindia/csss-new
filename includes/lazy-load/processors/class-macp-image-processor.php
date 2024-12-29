<?php
class MACP_Image_Processor {
    private $excluded_classes = ['no-lazy', 'skip-lazy', 'king-lazy'];

    public function process($html) {
        if (empty($html)) {
            return $html;
        }

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

        // Add our lazy load class
        $img = $this->add_lazy_load_class($img);

        // Replace src with data-src
        $img = $this->replace_src_attributes($img);

        return $img;
    }

    private function should_skip($img) {
        // Skip if already processed
        if (strpos($img, 'data-src') !== false || 
            strpos($img, 'macp-lazy') !== false) {
            return true;
        }

        // Check for excluded classes
        foreach ($this->excluded_classes as $class) {
            if (preg_match('/class=["\'][^"\']*\b' . $class . '\b[^"\']*["\']/', $img)) {
                return true;
            }
        }

        return false;
    }

    private function add_lazy_load_class($img) {
        $classes = $this->get_classes($img);
        $classes[] = 'macp-lazy';
        return $this->update_class_attribute($img, $classes);
    }

    private function replace_src_attributes($img) {
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

        return $img;
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