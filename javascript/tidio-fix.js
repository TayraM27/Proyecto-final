(function() {
    'use strict';
    function protegerTidio() {
        var iframe = document.getElementById('tidio-chat-code');
        if (!iframe) {
            iframe = document.querySelector('iframe[src*=\"tidio\"]');
        }
        if (iframe) {
            iframe.style.setProperty('z-index', '999999', 'important');
            iframe.style.setProperty('pointer-events', 'auto', 'important');
            if (window.getComputedStyle(iframe).position === 'static') {
                iframe.style.setProperty('position', 'fixed', 'important');
            }
        }
    }
    function isTidioElement(target) {
        if (!target) return false;
        if (target.closest('#tidio-chat-code')) return true;
        if (target.tagName === 'IFRAME' && target.src && target.src.indexOf('tidio') !== -1) return true;
        return false;
    }
    document.addEventListener('click', function(e) {
        if (isTidioElement(e.target)) {
            e.stopPropagation();
            return false;
        }
    }, true);
    if (window.tidioChatApi) {
        window.tidioChatApi.on('open', function() {
            setTimeout(protegerTidio, 100);
        });
        window.tidioChatApi.on('ready', function() {
            protegerTidio();
        });
        window.tidioChatApi.on('close', function() {
            console.log('Tidio cerrado - reabriendo si es error');
            setTimeout(function() {
                if (window.tidioChatApi) window.tidioChatApi.open();
            }, 2000);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', protegerTidio);
    } else {
        protegerTidio();
    }
    setInterval(protegerTidio, 2000);
    var observer = new MutationObserver(function() {
        protegerTidio();
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();
