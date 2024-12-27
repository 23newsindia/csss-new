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
        
        window.addEventListener("pageshow", (e) => {
            this.persisted = e.persisted;
        });

        window.addEventListener("DOMContentLoaded", () => {
            this._preconnect3rdParties();
        });

        this._addUserInteractionListener(this);
    }

    _addUserInteractionListener(instance) {
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
        this._delayEventListeners();
        this._delayJQueryReady(this);
        this._handleDocumentWrite();
        this._registerAllDelayedScripts();
        await this._loadScriptsFromList(this.delayedScripts.normal);
        await this._loadScriptsFromList(this.delayedScripts.defer);
        await this._loadScriptsFromList(this.delayedScripts.async);
        try {
            await this._triggerDOMContentLoaded();
            await this._triggerWindowLoad();
        } catch(err) {
            console.error(err);
        }
    }

    _registerAllDelayedScripts() {
        document.querySelectorAll("script[type=rocketlazyloadscript]").forEach(elem => {
            if (elem.hasAttribute("data-rocket-src")) {
                if (elem.hasAttribute("async") && elem.async !== false) {
                    this.delayedScripts.async.push(elem);
                } else if (elem.hasAttribute("defer") && elem.defer !== false || elem.getAttribute("data-rocket-type") === "module") {
                    this.delayedScripts.defer.push(elem);
                } else {
                    this.delayedScripts.normal.push(elem);
                }
            }
        });
    }

    async _transformScript(script) {
        return new Promise((resolve, reject) => {
            const newScript = document.createElement("script");
            [...script.attributes].forEach(attr => {
                let name = attr.nodeName;
                if (name !== "type") {
                    if (name === "data-rocket-type") name = "type";
                    if (name === "data-rocket-src") name = "src";
                    newScript.setAttribute(name, attr.nodeValue);
                }
            });
            
            if (script.hasAttribute("src")) {
                newScript.addEventListener("load", resolve);
                newScript.addEventListener("error", reject);
            } else {
                newScript.text = script.text;
                resolve();
            }

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

    _delayEventListeners() {
        const eventListeners = {};
        
        ["DOMContentLoaded", "load", "scroll", "click"].forEach(event => {
            document.addEventListener = function(type, fn) {
                if (type === event) {
                    eventListeners[event] = eventListeners[event] || [];
                    eventListeners[event].push(fn);
                    return;
                }
                Event.prototype.addEventListener.apply(this, arguments);
            };
        });

        window.addEventListener = function(type, fn) {
            if (eventListeners[type]) {
                eventListeners[type].push(fn);
                return;
            }
            Event.prototype.addEventListener.apply(this, arguments);
        };
    }

    async _triggerDOMContentLoaded() {
        document.dispatchEvent(new Event("rocket-DOMContentLoaded"));
        await this._littleBreath();
        document.dispatchEvent(new Event("rocket-readystatechange"));
    }

    async _triggerWindowLoad() {
        window.dispatchEvent(new Event("rocket-load"));
        await this._littleBreath();
        window.dispatchEvent(new Event("rocket-pageshow"));
    }

    async _littleBreath() {
        return new Promise(resolve => {
            setTimeout(resolve, 30);
        });
    }

    _delayJQueryReady(rocket) {
        let jQueryInstance = window.jQuery;
        Object.defineProperty(window, "jQuery", {
            get: () => jQueryInstance,
            set: newJQuery => {
                if (newJQuery && newJQuery.fn && !rocket.allJQueries.includes(newJQuery)) {
                    newJQuery.fn.ready = newJQuery.fn.init.prototype.ready = function(fn) {
                        rocket.domReadyFired ? fn.bind(document)(newJQuery) : document.addEventListener("rocket-DOMContentLoaded", () => fn.bind(document)(newJQuery));
                        return this;
                    };
                    rocket.allJQueries.push(newJQuery);
                }
                jQueryInstance = newJQuery;
            }
        });
    }

    static run() {
        const rocket = new RocketLazyLoadScripts();
        rocket._addUserInteractionListener(rocket);
    }
}

RocketLazyLoadScripts.run();
</script>
EOT;
    }

    public function process_scripts($html) {
        if (!get_option('macp_enable_js_delay', 0)) {
            return $html;
        }

        // Add the loader script right after <head>
        $html = preg_replace('/<head(.*)>/i', '$0' . $this->get_loader_script(), $html, 1)