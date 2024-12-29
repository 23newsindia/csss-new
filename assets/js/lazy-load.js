// Initialize vanilla-lazyload
window.lazyLoadInstance = new LazyLoad({
    elements_selector: ".macp-lazy",
    use_native: true,
    threshold: 300,
    callback_enter: (element) => {
        // Handle picture element sources
        if (element.parentNode.tagName === 'PICTURE') {
            element.parentNode.querySelectorAll('source').forEach(source => {
                if (source.dataset.srcset) {
                    source.srcset = source.dataset.srcset;
                }
            });
        }
    },
    callback_loaded: (element) => {
        element.classList.add('macp-lazy-loaded');
        if (element.classList.contains('king-lazy')) {
            element.classList.add('loaded');
        }
    },
    callback_error: (element) => {
        if (element.dataset.src) {
            element.src = element.dataset.src;
        }
    }
});

// Update lazy loading on dynamic content
document.addEventListener('macp_content_updated', () => {
    window.lazyLoadInstance.update();
});
