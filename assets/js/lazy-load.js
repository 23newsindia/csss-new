// Initialize vanilla-lazyload
window.lazyLoadInstance = new LazyLoad({
    elements_selector: ".lazy",
    use_native: true, // Use native lazy loading if available
    threshold: 300,
    callback_error: (element) => {
        // Remove lazy class on error
        element.classList.remove('lazy');
        // Try loading the original source
        if (element.getAttribute('data-src')) {
            element.src = element.getAttribute('data-src');
        }
    },
    callback_loaded: (element) => {
        element.classList.add('lazy-loaded');
    }
});

// Update lazy loading on dynamic content
document.addEventListener('macp_content_updated', function() {
    window.lazyLoadInstance.update();
});