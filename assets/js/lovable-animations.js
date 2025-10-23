/**
 * Lovable Animations JavaScript
 * 
 * This file handles all animation triggers, intersection observers,
 * and dynamic content loading for Lovable exports in WordPress
 * 
 * @package Lovable_Exporter
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Main Lovable class
     */
    class LovableAnimations {
        constructor() {
            this.elements = [];
            this.observer = null;
            this.settings = window.lovableSettings || {};
            this.init();
        }

        /**
         * Initialize the animation system
         */
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }

        /**
         * Setup animations
         */
        setup() {
            // Check if animations are enabled
            if (this.settings.animationEnabled === false) {
                this.disableAnimations();
                return;
            }

            // Find all elements with animation attributes
            this.elements = document.querySelectorAll('[data-lovable-anim]');
            
            if (this.elements.length === 0) {
                return;
            }

            // Check if IntersectionObserver is supported
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                // Fallback for older browsers
                this.fallbackAnimation();
            }

            // Setup lazy loading
            this.setupLazyLoad();

            // Setup event listeners
            this.setupEventListeners();
        }

        /**
         * Setup Intersection Observer for scroll-triggered animations
         */
        setupIntersectionObserver() {
            const options = {
                root: null,
                rootMargin: '0px 0px -100px 0px', // Trigger 100px before element enters viewport
                threshold: 0.1
            };

            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateElement(entry.target);
                        
                        // Unobserve after animation (animate only once)
                        const animateOnce = entry.target.getAttribute('data-lovable-once') !== 'false';
                        if (animateOnce) {
                            this.observer.unobserve(entry.target);
                        }
                    } else {
                        // If animate-once is false, reset animation when out of view
                        const animateOnce = entry.target.getAttribute('data-lovable-once') !== 'false';
                        if (!animateOnce) {
                            this.resetAnimation(entry.target);
                        }
                    }
                });
            }, options);

            // Observe all animated elements
            this.elements.forEach(element => {
                this.observer.observe(element);
            });
        }

        /**
         * Animate an element
         * @param {HTMLElement} element - Element to animate
         */
        animateElement(element) {
            const animationType = element.getAttribute('data-lovable-anim');
            const delay = element.getAttribute('data-lovable-delay') || '0';
            const duration = element.getAttribute('data-lovable-duration') || 'normal';

            // Add duration class
            if (duration) {
                element.classList.add(`lovable-duration-${duration}`);
            }

            // Apply delay
            if (delay && parseInt(delay) > 0) {
                element.style.animationDelay = `${delay}ms`;
                element.classList.add(`lovable-delay-${delay}`);
            }

            // Trigger animation
            setTimeout(() => {
                element.classList.add('lovable-animated');
                element.classList.add(`lovable-anim-${animationType}`);

                // Fire custom event
                this.fireEvent(element, 'lovable:animated', {
                    type: animationType,
                    delay: delay,
                    duration: duration
                });

                // Remove will-change after animation completes
                this.cleanupAnimation(element, duration);
            }, parseInt(delay) || 0);
        }

        /**
         * Reset animation
         * @param {HTMLElement} element - Element to reset
         */
        resetAnimation(element) {
            const animationType = element.getAttribute('data-lovable-anim');
            element.classList.remove('lovable-animated');
            element.classList.remove(`lovable-anim-${animationType}`);
        }

        /**
         * Cleanup animation properties after completion
         * @param {HTMLElement} element - Element to cleanup
         * @param {string} duration - Animation duration
         */
        cleanupAnimation(element, duration) {
            const durationMap = {
                'fast': 400,
                'normal': 800,
                'slow': 1200
            };

            const cleanupDelay = durationMap[duration] || durationMap['normal'];

            setTimeout(() => {
                element.classList.add('lovable-animation-complete');
                element.style.willChange = 'auto';
            }, cleanupDelay);
        }

        /**
         * Fallback animation for browsers without IntersectionObserver
         */
        fallbackAnimation() {
            this.elements.forEach(element => {
                this.animateElement(element);
            });
        }

        /**
         * Disable all animations (for accessibility or performance)
         */
        disableAnimations() {
            const elements = document.querySelectorAll('[data-lovable-anim]');
            elements.forEach(element => {
                element.style.opacity = '1';
                element.removeAttribute('data-lovable-anim');
            });
        }

        /**
         * Setup lazy loading for images
         */
        setupLazyLoad() {
            if (!this.settings.lazyloadEnabled) {
                return;
            }

            const lazyImages = document.querySelectorAll('img[data-src], .lovable-image[data-src]');

            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            const src = img.getAttribute('data-src');
                            
                            if (src) {
                                img.src = src;
                                img.classList.add('lazyloaded');
                                img.removeAttribute('data-src');
                            }

                            imageObserver.unobserve(img);
                        }
                    });
                });

                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback: load all images immediately
                lazyImages.forEach(img => {
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.classList.add('lazyloaded');
                    }
                });
            }
        }

        /**
         * Setup event listeners for interactive animations
         */
        setupEventListeners() {
            // Hover effects
            document.querySelectorAll('[data-lovable-hover]').forEach(element => {
                const hoverEffect = element.getAttribute('data-lovable-hover');
                
                element.addEventListener('mouseenter', () => {
                    element.classList.add(`lovable-effect-${hoverEffect}`);
                });

                element.addEventListener('mouseleave', () => {
                    element.classList.remove(`lovable-effect-${hoverEffect}`);
                });
            });

            // Click effects
            document.querySelectorAll('[data-lovable-click]').forEach(element => {
                const clickEffect = element.getAttribute('data-lovable-click');
                
                element.addEventListener('click', () => {
                    element.classList.add(`lovable-effect-${clickEffect}`);
                    
                    setTimeout(() => {
                        element.classList.remove(`lovable-effect-${clickEffect}`);
                    }, 500);
                });
            });

            // Scroll effects (parallax, etc.)
            this.setupScrollEffects();
        }

        /**
         * Setup scroll effects
         */
        setupScrollEffects() {
            const parallaxElements = document.querySelectorAll('[data-lovable-parallax]');
            
            if (parallaxElements.length === 0) {
                return;
            }

            let ticking = false;

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.handleParallax(parallaxElements);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }

        /**
         * Handle parallax effect
         * @param {NodeList} elements - Elements with parallax effect
         */
        handleParallax(elements) {
            const scrolled = window.pageYOffset;

            elements.forEach(element => {
                const speed = parseFloat(element.getAttribute('data-lovable-parallax')) || 0.5;
                const yPos = -(scrolled * speed);
                element.style.transform = `translateY(${yPos}px)`;
            });
        }

        /**
         * Fire custom event
         * @param {HTMLElement} element - Element to fire event on
         * @param {string} eventName - Event name
         * @param {Object} detail - Event detail
         */
        fireEvent(element, eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                detail: detail,
                bubbles: true,
                cancelable: true
            });
            element.dispatchEvent(event);
        }

        /**
         * Public method to manually trigger animation
         * @param {HTMLElement} element - Element to animate
         */
        trigger(element) {
            if (element && element.hasAttribute('data-lovable-anim')) {
                this.animateElement(element);
            }
        }

        /**
         * Public method to reset animation
         * @param {HTMLElement} element - Element to reset
         */
        reset(element) {
            if (element) {
                this.resetAnimation(element);
            }
        }

        /**
         * Destroy observer and cleanup
         */
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
        }
    }

    /**
     * Dynamic Content Loader
     */
    class LovableDynamicContent {
        constructor() {
            this.setupDynamicContent();
        }

        /**
         * Setup dynamic content loading
         */
        setupDynamicContent() {
            // Handle AJAX loading for dynamic content
            this.setupAjaxLoaders();
            
            // Handle infinite scroll if enabled
            this.setupInfiniteScroll();
        }

        /**
         * Setup AJAX loaders
         */
        setupAjaxLoaders() {
            document.querySelectorAll('[data-lovable-load]').forEach(element => {
                const loadUrl = element.getAttribute('data-lovable-load');
                const trigger = element.getAttribute('data-lovable-trigger') || 'visible';

                if (trigger === 'visible') {
                    this.loadOnVisible(element, loadUrl);
                } else if (trigger === 'click') {
                    element.addEventListener('click', () => {
                        this.loadContent(element, loadUrl);
                    });
                }
            });
        }

        /**
         * Load content when element becomes visible
         * @param {HTMLElement} element - Target element
         * @param {string} url - URL to load content from
         */
        loadOnVisible(element, url) {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadContent(element, url);
                            observer.unobserve(element);
                        }
                    });
                });

                observer.observe(element);
            } else {
                this.loadContent(element, url);
            }
        }

        /**
         * Load content via AJAX
         * @param {HTMLElement} element - Target element
         * @param {string} url - URL to load content from
         */
        loadContent(element, url) {
            element.classList.add('lovable-loading');

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                element.innerHTML = html;
                element.classList.remove('lovable-loading');
                element.removeAttribute('data-lovable-load');

                // Re-initialize animations for new content
                window.lovableAnimations.setup();
            })
            .catch(error => {
                console.error('Lovable: Error loading content', error);
                element.classList.remove('lovable-loading');
            });
        }

        /**
         * Setup infinite scroll
         */
        setupInfiniteScroll() {
            const container = document.querySelector('[data-lovable-infinite-scroll]');
            
            if (!container) {
                return;
            }

            const nextPageUrl = container.getAttribute('data-lovable-next-page');
            
            if (!nextPageUrl) {
                return;
            }

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadNextPage(container);
                        }
                    });
                }, { rootMargin: '200px' });

                observer.observe(container.lastElementChild);
            }
        }

        /**
         * Load next page for infinite scroll
         * @param {HTMLElement} container - Container element
         */
        loadNextPage(container) {
            const nextPageUrl = container.getAttribute('data-lovable-next-page');
            
            if (!nextPageUrl) {
                return;
            }

            container.classList.add('lovable-loading');

            fetch(nextPageUrl)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newItems = doc.querySelectorAll('[data-lovable-item]');
                const newNextPage = doc.querySelector('[data-lovable-infinite-scroll]')
                    ?.getAttribute('data-lovable-next-page');

                newItems.forEach(item => {
                    container.appendChild(item);
                });

                container.classList.remove('lovable-loading');

                if (newNextPage) {
                    container.setAttribute('data-lovable-next-page', newNextPage);
                } else {
                    container.removeAttribute('data-lovable-next-page');
                }

                // Re-initialize animations for new content
                window.lovableAnimations.setup();
            })
            .catch(error => {
                console.error('Lovable: Error loading next page', error);
                container.classList.remove('lovable-loading');
            });
        }
    }

    /**
     * Initialize when DOM is ready
     */
    window.lovableAnimations = new LovableAnimations();
    window.lovableDynamicContent = new LovableDynamicContent();

    /**
     * Expose public API
     */
    window.Lovable = {
        animate: (element) => window.lovableAnimations.trigger(element),
        reset: (element) => window.lovableAnimations.reset(element),
        destroy: () => window.lovableAnimations.destroy()
    };

    // Support for Elementor editor
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', () => {
            window.lovableAnimations.setup();
        });
    }

})();
