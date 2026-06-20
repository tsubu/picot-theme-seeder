<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared Anime.js (https://animejs.com/) helpers for generated themes.
 */
class PTS_Animejs
{
    const VERSION = '4.4.1';

    const CDN_URL = 'https://cdn.jsdelivr.net/npm/animejs@4.4.1/dist/bundles/anime.umd.min.js';

    /**
     * wp_enqueue_script() line for the Anime.js UMD bundle (global: anime).
     *
     * @return string
     */
    public static function get_enqueue_script_line()
    {
        return "    wp_enqueue_script('animejs', '" . self::CDN_URL . "', array(), '" . self::VERSION . "', true);\n";
    }

    /**
     * wp_enqueue_script() line for the theme's animate-init.js helper.
     *
     * @param string $theme_slug    Theme slug.
     * @param string $version_expr  PHP expression for the script version (without quotes).
     * @return string
     */
    public static function get_enqueue_init_line($theme_slug, $version_expr = "wp_get_theme()->get('Version')")
    {
        return "    wp_enqueue_script('{$theme_slug}-animate-init', get_template_directory_uri() . '/assets/js/animate-init.js', array('animejs'), {$version_expr}, true);\n";
    }

    /**
     * CSS for Anime.js utility classes in generated theme stylesheets.
     *
     * @return string
     */
    public static function get_stylesheet_css()
    {
        return <<<'CSS'
.pts-animate,
.pts-animate-load,
.pts-animate-hero__item,
.pts-animate-stagger > * {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .pts-animate,
    .pts-animate-load,
    .pts-animate-hero__item,
    .pts-animate-stagger > * {
        opacity: 1;
        transform: none;
    }
}

.editor-styles-wrapper .pts-animate,
.editor-styles-wrapper .pts-animate-load,
.editor-styles-wrapper .pts-animate-hero__item,
.editor-styles-wrapper .pts-animate-stagger > *,
.block-editor-writing-flow .pts-animate,
.block-editor-writing-flow .pts-animate-load,
.block-editor-writing-flow .pts-animate-hero__item,
.block-editor-writing-flow .pts-animate-stagger > * {
    opacity: 1;
    transform: none;
}

CSS;
    }

    /**
     * Theme helper: load, scroll, and stagger animations for utility classes.
     *
     * @return string
     */
    public static function get_init_js()
    {
        return <<<'JS'
(function () {
    if (typeof anime === 'undefined' || typeof anime.animate !== 'function') {
        return;
    }

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var animatedSelector = '.pts-animate, .pts-animate-load, .pts-animate-hero__item, .pts-animate-stagger > *';

    function revealStatic() {
        document.querySelectorAll(animatedSelector).forEach(function (el) {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
    }

    function getVariant(el) {
        var dataVariant = el.getAttribute('data-pts-animate');
        if (dataVariant) {
            return dataVariant;
        }
        if (el.classList.contains('pts-animate-fade-up')) {
            return 'fade-up';
        }
        if (el.classList.contains('pts-animate-fade-down')) {
            return 'fade-down';
        }
        if (el.classList.contains('pts-animate-fade-left')) {
            return 'fade-left';
        }
        if (el.classList.contains('pts-animate-fade-right')) {
            return 'fade-right';
        }
        if (el.classList.contains('pts-animate-zoom-in')) {
            return 'zoom-in';
        }
        return 'fade-in';
    }

    function buildProps(el, variant) {
        var delay = parseInt(el.getAttribute('data-pts-animate-delay') || '0', 10);
        var duration = parseInt(el.getAttribute('data-pts-animate-duration') || '800', 10);
        var props = {
            opacity: [0, 1],
            duration: duration,
            ease: 'out(3)',
            delay: delay > 0 ? delay : 0,
        };

        if (variant === 'fade-up') {
            props.y = ['2rem', 0];
        } else if (variant === 'fade-down') {
            props.y = ['-2rem', 0];
        } else if (variant === 'fade-left') {
            props.x = ['-2rem', 0];
        } else if (variant === 'fade-right') {
            props.x = ['2rem', 0];
        } else if (variant === 'zoom-in') {
            props.scale = [0.92, 1];
        }

        return props;
    }

    function getScrollThreshold(el) {
        var threshold = parseFloat(el.getAttribute('data-pts-animate-threshold') || '0.12');
        return isNaN(threshold) ? 0.12 : threshold;
    }

    function playAnimation(el, props, scrollTarget) {
        if (scrollTarget && typeof anime.onScroll === 'function') {
            anime.animate(el, Object.assign({}, props, {
                autoplay: anime.onScroll({ target: scrollTarget, threshold: getScrollThreshold(scrollTarget) }),
            }));
            return;
        }

        anime.animate(el, props);
    }

    function observeThenAnimate(el, props) {
        if (typeof IntersectionObserver === 'undefined') {
            anime.animate(el, props);
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (! entry.isIntersecting) {
                        return;
                    }
                    anime.animate(entry.target, props);
                    observer.unobserve(entry.target);
                });
            },
            { threshold: getScrollThreshold(el), rootMargin: '0px 0px -5% 0px' }
        );

        observer.observe(el);
    }

    function initHeroTimelines() {
        document.querySelectorAll('.pts-animate-hero').forEach(function (hero) {
            var items = hero.querySelectorAll('.pts-animate-hero__item, .pts-animate-load');
            if (! items.length) {
                return;
            }

            if (typeof anime.createTimeline === 'function' && typeof anime.stagger === 'function') {
                anime.createTimeline({ defaults: { ease: 'out(3)', duration: 900 } })
                    .add(items, {
                        opacity: [0, 1],
                        y: ['2rem', 0],
                        delay: anime.stagger(120),
                    });
                return;
            }

            items.forEach(function (item, index) {
                var props = buildProps(item, getVariant(item));
                props.delay = index * 120;
                anime.animate(item, props);
            });
        });
    }

    function initLoadAnimations() {
        document.querySelectorAll('.pts-animate-load').forEach(function (el) {
            if (el.closest('.pts-animate-hero')) {
                return;
            }
            anime.animate(el, buildProps(el, getVariant(el)));
        });
    }

    function initScrollAnimations() {
        document.querySelectorAll('.pts-animate').forEach(function (el) {
            if (el.closest('.pts-animate-stagger') || el.closest('.pts-animate-hero')) {
                return;
            }

            var props = buildProps(el, getVariant(el));

            if (typeof anime.onScroll === 'function') {
                playAnimation(el, props, el);
                return;
            }

            observeThenAnimate(el, props);
        });
    }

    function initStaggerGroups() {
        document.querySelectorAll('.pts-animate-stagger').forEach(function (group) {
            var childSelector = group.getAttribute('data-pts-animate-children') || ':scope > *';
            var children = group.querySelectorAll(childSelector);
            if (! children.length) {
                return;
            }

            var staggerMs = parseInt(group.getAttribute('data-pts-animate-stagger') || '100', 10);
            var variant = group.getAttribute('data-pts-animate') || 'fade-up';
            var props = buildProps(group, variant);
            props.delay = typeof anime.stagger === 'function' ? anime.stagger(staggerMs) : 0;

            if (typeof anime.onScroll === 'function') {
                playAnimation(children, props, group);
                return;
            }

            if (typeof IntersectionObserver === 'undefined') {
                if (typeof anime.stagger !== 'function') {
                    Array.prototype.forEach.call(children, function (child, index) {
                        var childProps = buildProps(child, variant);
                        childProps.delay = index * staggerMs;
                        anime.animate(child, childProps);
                    });
                } else {
                    anime.animate(children, props);
                }
                return;
            }

            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (! entry.isIntersecting) {
                            return;
                        }

                        if (typeof anime.stagger !== 'function') {
                            Array.prototype.forEach.call(children, function (child, index) {
                                var childProps = buildProps(child, variant);
                                childProps.delay = index * staggerMs;
                                anime.animate(child, childProps);
                            });
                        } else {
                            anime.animate(children, props);
                        }

                        observer.unobserve(entry.target);
                    });
                },
                { threshold: getScrollThreshold(group), rootMargin: '0px 0px -5% 0px' }
            );

            observer.observe(group);
        });
    }

    if (reducedMotion) {
        revealStatic();
        return;
    }

    initHeroTimelines();
    initLoadAnimations();
    initScrollAnimations();
    initStaggerGroups();

    // Safety net: never leave utility-class content permanently hidden.
    window.setTimeout(function () {
        document.querySelectorAll(animatedSelector).forEach(function (el) {
            if (window.getComputedStyle(el).opacity === '0') {
                el.style.opacity = '1';
                el.style.transform = 'none';
            }
        });
    }, 2500);
})();
JS;
    }
}
