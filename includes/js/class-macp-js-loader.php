<?php
class MACP_JS_Loader {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_loader_script() {
        return <<<'EOT'
<script>
class RocketLazyLoadScripts {
    constructor() {
        this.triggerEvents = ["keydown", "mousedown", "mousemove", "touchmove", "touchstart", "touchend", "wheel", "scroll"];
        this.userEventHandler = this._triggerListener.bind(this);
        this.touchStartHandler = this._onTouchStart.bind(this);
        this.touchMoveHandler = this._onTouchMove.bind(this);
        this.touchEndHandler = this._onTouchEnd.bind(this);
        this.clickHandler = this._onClick.bind(this);
        this.interceptedClicks = [];
        this.delayedScripts = { normal: [], async: [], defer: [] };
        this.trash = [];
        this.allJQueries = [];
        
        // Initialize immediately if page is already scrolled
        if (window.scrollY > 0) {
            this._loadEverythingNow();
            return;
        }

        // Add event listeners for all trigger events
        this._addUserInteractionListener(this);
        
        // Handle page show events
        window.addEventListener("pageshow", (e) => {
            this.persisted = e.persisted;
        });

        // Initialize after DOM is ready
        window.addEventListener("DOMContentLoaded", () => {
            this._preconnect3rdParties();
        });
    }

    _addUserInteractionListener(instance) {
        // Add listeners for all trigger events
        this.triggerEvents.forEach(eventName => {
            window.addEventListener(eventName, instance.userEventHandler, {
                passive: true,
                capture: true
            });
        });
    }

    _removeUserInteractionListener() {
        this.triggerEvents.forEach(eventName => {
            window.removeEventListener(eventName, this.userEventHandler, {
                passive: true,
                capture: true
            });
        });
    }

    _triggerListener() {
        this._removeUserInteractionListener();
        this._loadEverythingNow();
    }

    async _loadEverythingNow() {
        // Register all scripts first
        this._registerAllDelayedScripts();
        
        // Load scripts in order
        await this._loadScriptsFromList(this.delayedScripts.normal);
        await this._loadScriptsFromList(this.delayedScripts.defer);
        await this._loadScriptsFromList(this.delayedScripts.async);
        
        // Trigger events
        try {
            await this._triggerDOMContentLoaded();
            await this._triggerWindowLoad();
        } catch(err) {
            console.error(err);
        }
    }

    _registerAllDelayedScripts() {
        document.querySelectorAll("script[type=rocketlazyloadscript]").forEach(script => {
            if (script.hasAttribute("data-rocket-src")) {
                if (script.hasAttribute("async") && script.async !== false) {
                    this.delayedScripts.async.push(script);
                } else if (script.hasAttribute("defer") && script.defer !== false) {
                    this.delayedScripts.defer.push(script);
                } else {
                    this.delayedScripts.normal.push(script);
                }
            }
        });
    }

    async _transformScript(script) {
        return new Promise((resolve, reject) => {
            const newScript = document.createElement("script");
            
            // Copy all attributes except type
            [...script.attributes].forEach(attr => {
                let name = attr.nodeName;
                if (name !== "type") {
                    if (name === "data-rocket-src") name = "src";
                    newScript.setAttribute(name, attr.nodeValue);
                }
            });
            
            // Handle load events
            if (script.hasAttribute("src")) {
                newScript.addEventListener("load", resolve);
                newScript.addEventListener("error", reject);
            } else {
                newScript.text = script.text;
                resolve();
            }

            // Replace old script with new one
            script.parentNode.replaceChild(newScript, script);
        });
    }

    async _loadScriptsFromList(scripts) {
        const promises = [];
        scripts.forEach((script) => {
            if (script && script.isConnected) {
                promises.push(this._transformScript(script));
            }
        });
        await Promise.all(promises);
    }

    async _triggerDOMContentLoaded() {
        document.dispatchEvent(new Event("DOMContentLoaded", {
            bubbles: true,
            cancelable: true
        }));
    }

    async _triggerWindowLoad() {
        window.dispatchEvent(new Event("load", {
            bubbles: true,
            cancelable: true
        }));
    }

    static run() {
        const rocket = new RocketLazyLoadScripts();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    RocketLazyLoadScripts.run();
});
</script>
EOT;
    }
}