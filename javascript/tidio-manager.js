// tidio-manager.js
(function() {
    'use strict';

    function fixTidio() {
        var iframe = document.getElementById('tidio-chat-code') || document.querySelector('iframe[src*="tidio"]');
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
        if (target.tagName === 'IFRAME') {
            var src = target.src || '';
            if (src.indexOf('tidio') !== -1) return true;
        }
        return false;
    }

    document.addEventListener('click', function(e) {
        if (isTidioElement(e.target)) {
            e.stopPropagation();
        }
    }, true);

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.removedNodes.forEach(function(node) {
                    if (node.id === 'tidio-chat-code' ||
                        (node.tagName === 'IFRAME' && node.src && node.src.indexOf('tidio') !== -1)) {
                        console.warn('Tidio iframe removido - reinyectando');
                        setTimeout(function() {
                            if (!document.getElementById('tidio-chat-code') &&
                                !document.querySelector('iframe[src*="tidio"]')) {
                                var s = document.createElement('script');
                                s.src = 'https://code.tidio.co/l90vdgeb19wyo1hvbhpmzrgfojxhtmqq.js';
                                s.async = true;
                                document.body.appendChild(s);
                            }
                        }, 1000);
                    }
                });
            }
        });
        fixTidio();
    });

    observer.observe(document.body, { childList: true, subtree: true });

    if (window.tidioChatApi) {
        window.tidioChatApi.on('ready', function() {
            console.log('Tidio listo');
            fixTidio();
        });
        window.tidioChatApi.on('open', function() {
            console.log('Tidio abierto');
            setTimeout(fixTidio, 100);
        });
        window.tidioChatApi.on('close', function() {
            console.log('Tidio cerrado');
        });
    }

    setInterval(fixTidio, 2000);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixTidio);
    } else {
        fixTidio();
    }

    window.addEventListener('load', function() {
        setTimeout(fixTidio, 1000);
    });
})();
