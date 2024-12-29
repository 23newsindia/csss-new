<?php
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/processors/class-macp-picture-processor.php';
require_once MACP_PLUGIN_DIR . 'includes/lazy-load/processors/class-macp-image-processor.php';

class MACP_Lazy_Load_Processor {
    private $picture_processor;
    private $image_processor;

    public function __construct() {
        $this->picture_processor = new MACP_Picture_Processor();
        $this->image_processor = new MACP_Image_Processor();
    }

    public function process_content($content) {
        if (empty($content)) {
            return $content;
        }

        // Process picture elements first
        $content = $this->picture_processor->process($content);
        
        // Process remaining images
        $content = $this->image_processor->process($content);

        return $content;
    }
}
