(function () {
    function setViewportUnit() {
        var inner = window.innerHeight;
        var screenHeight = window.screen && window.screen.height ? window.screen.height : 0;
        var docHeight = document.documentElement ? document.documentElement.clientHeight : 0;
        var vh = Math.max(inner, screenHeight, docHeight);
        document.documentElement.style.setProperty('--qr-vh', vh + 'px');
    }

    function initLogoSpin() {
        var logo = document.querySelector('.qr-digital-pricelist-logo');
        if (!logo) {
            return;
        }

        var rafId = null;
        var startTime = 0;
        var duration = 7000; // ms
        var turns = 5; // total rotations over the duration
        var clickCount = 0;

        function easeInOutCustom(t) {
            // Smooth start/end with faster mid-spin
            return 0.5 * (1 - Math.cos(Math.PI * t));
        }

        function step(timestamp) {
            if (!startTime) {
                startTime = timestamp;
            }

            var elapsed = timestamp - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var eased = easeInOutCustom(progress);
            var angle = 360 * turns * eased;

            logo.style.transform = 'perspective(1200px) rotateY(' + angle + 'deg)';
            if (!logo.classList.contains('is-spinning')) {
                logo.classList.add('is-spinning');
            }

            if (progress < 1) {
                rafId = requestAnimationFrame(step);
            } else {
                logo.style.transform = 'perspective(1200px) rotateY(0deg)';
                logo.classList.remove('is-spinning');
                rafId = null;
                startTime = 0;
            }
        }

        function spawnLogoBurst(extraCount) {
            var src = logo.getAttribute('src');
            if (!src) {
                return;
            }

            var label = document.querySelector('.qr-digital-pricelist-label');
            if (label) {
                setTimeout(function () {
                    label.classList.add('is-trembling');
                    setTimeout(function () {
                        label.classList.remove('is-trembling');
                    }, 700);
                }, 800); // start tremble later, when bursts reach the label
            }

            var rect = logo.getBoundingClientRect();
            var originX = rect.left + rect.width / 2;
            var originY = rect.top + rect.height / 2;
            var vw = window.innerWidth || document.documentElement.clientWidth || 1200;
            var count = (4 + Math.floor(Math.random() * 2)) * 3; // 12-15

            for (var i = 0; i < count; i++) {
                var particle = document.createElement('img');
                particle.src = src;
                particle.className = 'qr-logo-burst';
                particle.style.left = originX + 'px';
                particle.style.top = originY + 'px';

                // Physics-ish flight: fast launch, ease near apex, then accelerating fall
                var dx = (Math.random() - 0.5) * vw * 0.9;
                var vy0 = -0.55 - Math.random() * 0.35; // lower initial lift
                var vx = dx / 2200; // spread over ~2.2s flight
                var g = 0.0008; // gravity px/ms^2
                var duration = 3400;
                var start = performance.now();

                document.body.appendChild(particle);

                (function animate(el, vxVal, vyStart, grav, tStart, maxT) {
                    function step(now) {
                        var t = now - tStart;
                        if (t > maxT) t = maxT;

                        var vy = vyStart + grav * t;
                        var x = vxVal * t;
                        var y = vyStart * t + 0.5 * grav * t * t;

                        // Ease-out near apex by damping upward velocity mid-flight
                        if (t < maxT * 0.5) {
                            var ease = 1 - Math.pow(1 - t / (maxT * 0.5), 2);
                            y *= ease;
                        }

                        // Fade near end of fall
                        var opacity = 1 - Math.max(0, (t - maxT * 0.7) / (maxT * 0.3));
                        el.style.opacity = Math.max(0, opacity).toString();

                        el.style.transform = 'translate(' + x.toFixed(2) + 'px, ' + y.toFixed(2) + 'px) scale(0.65)';

                        if (t < maxT) {
                            requestAnimationFrame(step);
                        } else {
                            if (el && el.parentNode) {
                                el.parentNode.removeChild(el);
                            }
                        }
                    }
                    requestAnimationFrame(step);
                })(particle, vx, vy0, g, start, duration);
            }
        }

        function triggerSpin() {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            startTime = 0;
            rafId = requestAnimationFrame(step);
            clickCount += 1;
            var extra = 0;
            if (clickCount === 2) {
                extra = 10;
            } else if (clickCount === 3) {
                extra = 20;
            }

            setTimeout(function () {
                spawnLogoBurst(extra);
            }, 2000);

            // Trigger floater explosion slightly after the burst
            setTimeout(function () {
                if (window.qrExplodeFloaters) {
                    var rect = logo.getBoundingClientRect();
                    var origin = {
                        x: rect.left + rect.width / 2,
                        y: rect.top + rect.height / 2
                    };
                    window.qrExplodeFloaters(origin);
                }
            }, 2600);
        }

        logo.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            triggerSpin();
        });

        logo.addEventListener('click', function (event) {
            event.preventDefault();
            triggerSpin();
        });

        logo.addEventListener('dragstart', function (event) {
            event.preventDefault();
        });
    }

    function initAccordions() {
        var sections = document.querySelectorAll('.qr-digital-pricelist-category');
        if (!sections.length) {
            return;
        }

        sections.forEach(function (section) {
            var toggle = section.querySelector('.qr-digital-pricelist-category-toggle');
            var content = section.querySelector('.qr-digital-pricelist-items');
            var arrow = toggle ? toggle.querySelector('.qr-digital-pricelist-arrow') : null;
            var indicator = toggle ? toggle.querySelector('.qr-digital-pricelist-category-indicator-icon') : null;
            if (!toggle || !content) {
                return;
            }

            // Ensure collapsed state is fully hidden and ready to animate
            if (section.classList.contains('is-collapsed')) {
                content.classList.add('collapsed');
                content.style.maxHeight = '0px';
                content.style.display = 'none';
            }

            function spawnCategoryFall(fromEl) {
                var catIcon = section.querySelector('.qr-digital-pricelist-category-icon');
                if (!catIcon || !fromEl) {
                    return;
                }

                var rect = fromEl.getBoundingClientRect();
                var originY = rect.top + rect.height / 2;
                var src = catIcon.getAttribute('src');
                if (!src) {
                    return;
                }

                var count = 8;
                for (var i = 0; i < count; i++) {
                    var drop = document.createElement('img');
                    drop.src = src;
                    drop.className = 'qr-category-fall';
                    var startX = rect.left + Math.random() * rect.width;
                    drop.style.left = startX + 'px';
                    drop.style.top = originY + 'px';

                    var spreadX = (Math.random() * rect.width - rect.width / 2) * 0.7;
                    var fallY = window.innerHeight * 0.8 + Math.random() * window.innerHeight * 0.4;
                    drop.style.transform = 'translate(0px, -20px) scale(0.95)';

                    document.body.appendChild(drop);

                    setTimeout(function (el, dx, dy) {
                        return function () {
                            el.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(0.85)';
                            el.style.opacity = '0';
                        };
                    }(drop, spreadX, fallY), 180 + i * 90);

                    setTimeout(function (el) {
                        return function () {
                            if (el && el.parentNode) {
                                el.parentNode.removeChild(el);
                            }
                        };
                    }(drop), 7800 + i * 90);
                }
            }

            toggle.addEventListener('click', function () {
                var isCollapsed = section.classList.toggle('is-collapsed');
                var expanded = !isCollapsed;
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

                var icon = section.querySelector('.qr-digital-pricelist-category-icon');
                var indicatorIcon = section.querySelector('.qr-digital-pricelist-category-indicator-icon');
                var title = section.querySelector('.qr-digital-pricelist-category-title');

                if (expanded) {
                    if (icon) {
                        icon.classList.remove('is-collapse-clicked');
                        icon.classList.add('is-clicked');
                    }
                    if (indicatorIcon) {
                        indicatorIcon.classList.remove('is-collapse-clicked');
                        indicatorIcon.classList.add('is-clicked');
                    }
                    if (title) {
                        title.classList.add('is-clicked');
                    }
                    setTimeout(function () {
                        if (icon) icon.classList.remove('is-clicked');
                        if (indicatorIcon) indicatorIcon.classList.remove('is-clicked');
                        if (title) title.classList.remove('is-clicked');
                    }, 1000);

                    content.style.transitionDuration = '1.9s';
                    content.style.visibility = 'visible';
                    content.style.display = 'block';
                    content.removeAttribute('hidden');
                    content.classList.remove('collapsed');
                    content.style.maxHeight = '0px';
                    void content.offsetHeight;
                    content.style.maxHeight = content.scrollHeight + 'px';
                    spawnCategoryFall(toggle);
                    if (arrow && toggle.dataset.arrowUp) {
                        arrow.src = toggle.dataset.arrowUp;
                        arrow.alt = arrow.alt.replace('Expand', 'Collapse');
                    }
                    if (indicatorIcon) {
                        var upIcon = indicatorIcon.getAttribute('data-icon-up');
                        if (upIcon) {
                            indicatorIcon.setAttribute('src', upIcon);
                        }
                    }
                } else {
                    if (icon) {
                        icon.classList.remove('is-clicked');
                        icon.classList.add('is-collapse-clicked');
                    }
                    if (indicatorIcon) {
                        indicatorIcon.classList.remove('is-clicked');
                        indicatorIcon.classList.add('is-collapse-clicked');
                    }
                    if (title) title.classList.remove('is-clicked');

                    content.style.transitionDuration = '0.9s';
                    content.classList.add('collapsed');
                    content.style.maxHeight = content.scrollHeight + 'px';
                    void content.offsetHeight;
                    content.style.maxHeight = '0px';

                    function handleCollapseEnd(event) {
                        if (event.target !== content || event.propertyName !== 'max-height') return;
                        content.removeEventListener('transitionend', handleCollapseEnd);
                        content.setAttribute('hidden', 'hidden');
                        content.style.visibility = 'hidden';
                        content.style.display = 'none';
                        if (icon) icon.classList.remove('is-collapse-clicked');
                        if (indicatorIcon) indicatorIcon.classList.remove('is-collapse-clicked');
                    }

                    content.addEventListener('transitionend', handleCollapseEnd);
                    if (arrow && toggle.dataset.arrowDown) {
                        arrow.src = toggle.dataset.arrowDown;
                        arrow.alt = arrow.alt.replace('Collapse', 'Expand');
                    }
                    if (indicatorIcon) {
                        var downIcon = indicatorIcon.getAttribute('data-icon-down');
                        if (downIcon) {
                            indicatorIcon.setAttribute('src', downIcon);
                        }
                    }
                }
            });
        });

        // expose collapse-all helper for external triggers
        window.qrCollapseAllCategories = function () {
            sections.forEach(function (section) {
                var toggle = section.querySelector('.qr-digital-pricelist-category-toggle');
                var content = section.querySelector('.qr-digital-pricelist-items');
                var arrow = toggle ? toggle.querySelector('.qr-digital-pricelist-arrow') : null;
                var indicator = toggle ? toggle.querySelector('.qr-digital-pricelist-category-indicator-icon') : null;
                if (!toggle || !content) {
                    return;
                }

                if (!section.classList.contains('is-collapsed')) {
                    section.classList.add('is-collapsed');
                    toggle.setAttribute('aria-expanded', 'false');
                    content.classList.add('collapsed');
                    content.style.maxHeight = content.scrollHeight + 'px';
                    void content.offsetHeight; // force reflow
                    content.style.maxHeight = '0px';
                    function handleCollapseEnd(event) {
                        if (event.target !== content || event.propertyName !== 'max-height') return;
                        content.removeEventListener('transitionend', handleCollapseEnd);
                        content.setAttribute('hidden', 'hidden');
                        content.style.visibility = 'hidden';
                        content.style.display = 'none';
                    }

                    content.addEventListener('transitionend', handleCollapseEnd);
                    if (arrow && toggle.dataset.arrowDown) {
                        arrow.src = toggle.dataset.arrowDown;
                        arrow.alt = arrow.alt.replace('Collapse', 'Expand');
                    }
                    if (indicator) {
                        var downIcon = indicator.getAttribute('data-icon-down');
                        if (downIcon) {
                            indicator.setAttribute('src', downIcon);
                        }
                    }
                }
            });
        };
    }

    function initInfoModals() {
        var triggers = document.querySelectorAll('.qr-digital-pricelist-info-trigger');
        if (!triggers.length) {
            return;
        }

        function closeModal(modal) {
            if (!modal) {
                return;
            }
            var content = modal.querySelector('.qr-digital-pricelist-info-content');
            if (!content) {
                modal.setAttribute('hidden', 'hidden');
                return;
            }

            content.style.transitionDuration = '1.9s';
            content.style.maxHeight = content.scrollHeight + 'px';
            void content.offsetHeight;
            content.style.maxHeight = '0px';

            function handleInfoCloseEnd(event) {
                if (event.target !== content || event.propertyName !== 'max-height') return;
                content.removeEventListener('transitionend', handleInfoCloseEnd);
                modal.setAttribute('hidden', 'hidden');
                content.style.maxHeight = '0px';
                var controller = document.querySelector('[aria-controls="' + modal.id + '"]');
                if (controller) {
                    controller.setAttribute('aria-expanded', 'false');
                    controller.focus();
                }
            }

            content.addEventListener('transitionend', handleInfoCloseEnd);
        }

        function openModal(modal, trigger) {
            if (!modal) {
                return;
            }
            modal.removeAttribute('hidden');
            var content = modal.querySelector('.qr-digital-pricelist-info-content');
            if (content) {
                content.style.transitionDuration = '1.9s';
                var targetHeight = Math.min(
                    Math.max(content.scrollHeight, window.innerHeight * 0.85),
                    window.innerHeight * 0.9
                );
                content.style.maxHeight = '0px';
                void content.offsetHeight;
                content.style.maxHeight = targetHeight + 'px';
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'true');
            }

            var scrollArea = modal.querySelector('.qr-digital-pricelist-info-text');
            if (scrollArea) {
                scrollArea.scrollTop = 0;
            }

            var closeBtn = modal.querySelector('.qr-digital-pricelist-info-close');
            if (closeBtn) {
                closeBtn.focus();
            }

            modal.addEventListener('click', function handleBackdrop(event) {
                if (event.target.matches('.qr-digital-pricelist-info-backdrop') || event.target.matches('.qr-digital-pricelist-info-close')) {
                    modal.removeEventListener('click', handleBackdrop);
                    closeModal(modal);
                }
            });

            modal.addEventListener('keydown', function handleEsc(event) {
                if (event.key === 'Escape') {
                    modal.removeEventListener('keydown', handleEsc);
                    closeModal(modal);
                }
            });
        }

        triggers.forEach(function (trigger) {
            var targetId = trigger.getAttribute('aria-controls');
            var modal = document.getElementById(targetId);
            if (!modal) {
                return;
            }

            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                var isHidden = modal.hasAttribute('hidden');
                if (isHidden) {
                    openModal(modal, trigger);
                } else {
                    closeModal(modal);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setViewportUnit();
        window.addEventListener('resize', setViewportUnit);
        window.addEventListener('orientationchange', setViewportUnit);
        initLogoSpin();
        initAccordions();
        initInfoModals();
        initFloatingLogo();

        // Add scroll to top button
        var scrollBtn = document.createElement('button');
        scrollBtn.className = 'qr-digital-pricelist-scroll-to-top';
        scrollBtn.type = 'button';
        scrollBtn.innerHTML = '<img src="/wp-content/plugins/qr-digital-pricelist/assets/icons/up.svg" alt="Scroll to top" />';
        document.body.appendChild(scrollBtn);
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (window.qrCollapseAllCategories) {
                window.qrCollapseAllCategories();
            }
        });

        // Show only when scrolled past first category
        var firstCategory = document.querySelector('.qr-digital-pricelist-category');
        if (firstCategory) {
            var rect = firstCategory.getBoundingClientRect();
            var threshold = window.scrollY + rect.top + rect.height;
            scrollBtn.style.display = 'none';
            window.addEventListener('scroll', function() {
                if (window.scrollY > threshold) {
                    scrollBtn.style.display = 'flex';
                } else {
                    scrollBtn.style.display = 'none';
                }
            });
        }
    });
})();

function initFloatingLogo() {
    var wrappers = document.querySelectorAll('.qr-digital-pricelist-floating-logo');
    if (!wrappers.length) {
        return;
    }

    var activeFloaters = [];

    // Limit keef_logo floaters to max 3
    var keefWrappers = [];
    wrappers.forEach(function (wrapper) {
        var img = wrapper.querySelector('img');
        if (img && img.src && img.src.indexOf('keef_logo.png') !== -1) {
            keefWrappers.push(wrapper);
        }
    });
    if (keefWrappers.length > 3) {
        keefWrappers.slice(3).forEach(function (extra) {
            if (extra && extra.parentNode) {
                extra.parentNode.removeChild(extra);
            }
        });
    }

    function startFloater(wrapper, index) {
        var img = wrapper.querySelector('img');
        if (!img) {
            return;
        }

        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var timeoutId = null;
        var clickCount = 0;
        var currentPos = { x: 0, y: 0 };

        function randomPosition() {
            var extra = 120;
            var x = Math.random() * (vw + extra * 2) - extra;
            var y = Math.random() * (vh + extra * 2) - extra;
            return { x: x, y: y };
        }

        function randomOffscreenPosition() {
            var cx = vw / 2;
            var cy = vh / 2;
            var radius = Math.max(vw, vh) / 2 + 240 + Math.random() * 160;
            var angle = Math.random() * Math.PI * 2;
            return {
                x: cx + Math.cos(angle) * radius,
                y: cy + Math.sin(angle) * radius
            };
        }

        function randomDuration() {
            // Slower, subtle drift with per-instance variance
            var base = 14000 + Math.random() * 8000;
            return base * (0.9 + 0.2 * Math.random());
        }

        function spawnBurst() {
            var rect = img.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            var count = 16;
            var baseSize = Math.max(rect.width * 0.4, 6);
            for (var i = 0; i < count; i++) {
                var clone = document.createElement('img');
                clone.src = img.src;
                clone.alt = '';
                clone.style.position = 'fixed';
                clone.style.pointerEvents = 'none';
                clone.style.width = baseSize + 'px';
                clone.style.height = 'auto';
                clone.style.left = rect.left + rect.width / 2 - baseSize / 2 + 'px';
                clone.style.top = rect.top + rect.height / 2 - baseSize / 2 + 'px';
                clone.style.opacity = '1';
                clone.style.transition = 'transform 900ms ease-out, opacity 900ms ease-out';
                clone.style.transform = 'translate(0px, 0px) scale(1)';
                var angle = Math.random() * Math.PI * 2;
                var distance = 180 + Math.random() * 180;
                var dx = Math.cos(angle) * distance;
                var dy = Math.sin(angle) * distance;
                document.body.appendChild(clone);
                requestAnimationFrame(function (el, tx, ty) {
                    return function () {
                        el.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(0.9) rotate(' + (Math.random() * 60 - 30) + 'deg)';
                        el.style.opacity = '0';
                    };
                }(clone, dx, dy));
                setTimeout(function (el) {
                    return function () {
                        if (el && el.parentNode) {
                            el.parentNode.removeChild(el);
                        }
                    };
                }(clone), 1000);
            }
        }

        function move() {
            vw = window.innerWidth;
            vh = window.innerHeight;
            var pos = randomPosition();
            var duration = randomDuration();
            img.style.transition = 'transform ' + duration + 'ms ease-in-out';
            img.style.transform = 'translate(' + pos.x + 'px, ' + pos.y + 'px) rotate(' + (Math.random() * 14 - 7) + 'deg)';
            currentPos = pos;
            timeoutId = setTimeout(move, duration);
        }

        vw = window.innerWidth;
        vh = window.innerHeight;
        var startPos = randomPosition();
        var initialDelay = Math.random() * 1000;
        var initialAngle = Math.random() * 14 - 7;

        // Place instantly at a random on-screen spot (no travel from 0,0)
        img.style.transition = 'none';
        img.style.transform = 'translate(' + startPos.x + 'px, ' + startPos.y + 'px) rotate(' + initialAngle + 'deg)';
        currentPos = startPos;

        timeoutId = setTimeout(function () {
            move();
        }, initialDelay);

        window.addEventListener('resize', function () {
            vw = window.innerWidth;
            vh = window.innerHeight;
        });

        function explodeFrom(origin) {
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            var originPoint = origin || { x: vw / 2, y: vh / 2 };
            // Direction strictly radial from logo center to current floater position
            var dx = currentPos.x - originPoint.x;
            var dy = currentPos.y - originPoint.y;
            var angle = Math.atan2(dy, dx);
            if (!isFinite(angle)) {
                angle = Math.random() * Math.PI * 2;
            }
            var dirLen = Math.sqrt(dx * dx + dy * dy) || 1;
            var nx = dx / dirLen;
            var ny = dy / dirLen;
            // Keep most floaters near screen edges; few go slightly outside
            var longHop = Math.random() < 0.25;
            var distance = longHop ? (180 + Math.random() * 180) : (90 + Math.random() * 120);
            var target = {
                x: originPoint.x + nx * (dirLen + distance),
                y: originPoint.y + ny * (dirLen + distance)
            };

            // Keep within viewport bounds (near edges) to avoid disappearing
            var padding = 30;
            var minX = -vw * 0.1;
            var maxX = vw * 1.1;
            var minY = -vh * 0.1;
            var maxY = vh * 1.1;
            target.x = Math.max(minX, Math.min(maxX, target.x));
            target.y = Math.max(minY, Math.min(maxY, target.y));

            var explodeDuration = 1100;
            img.style.transition = 'transform ' + explodeDuration + 'ms cubic-bezier(0.05, 0.8, 0.2, 1)';
            img.style.transform = 'translate(' + target.x + 'px, ' + target.y + 'px) rotate(' + (Math.random() * 18 - 9) + 'deg)';
            currentPos = target;

            // Resume drift just before the explode animation ends to avoid stops
            timeoutId = setTimeout(function () {
                move();
            }, Math.max(100, explodeDuration - 120));
        }

        activeFloaters.push({ explode: explodeFrom });

        img.addEventListener('click', function (event) {
            event.preventDefault();
            clickCount += 1;

            if (clickCount >= 3) {
                clickCount = 0;
                spawnBurst();
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                // fade out, jump to new spot, then resume drift
                img.style.transition = 'opacity 180ms ease';
                img.style.opacity = '0';
                setTimeout(function () {
                    var newPos = randomPosition();
                    var angle = Math.random() * 14 - 7;
                    img.style.transition = 'none';
                    img.style.transform = 'translate(' + newPos.x + 'px, ' + newPos.y + 'px) rotate(' + angle + 'deg)';
                    void img.offsetHeight; // reflow
                    img.style.transition = 'transform ' + randomDuration() + 'ms ease-in-out, opacity 220ms ease';
                    img.style.opacity = '1';
                    move();
                }, 200);
                return;
            }

            spawnBurst();
        });
    }

    wrappers.forEach(function (wrapper, idx) {
        startFloater(wrapper, idx);
    });

    window.qrExplodeFloaters = function (origin) {
        activeFloaters.forEach(function (floater) {
            if (floater && typeof floater.explode === 'function') {
                floater.explode(origin);
            }
        });
    };
}
