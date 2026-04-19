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

        var angle = 0;
        var velocity = 0;
        var friction = 0.94;
        var rafId = null;

        function normalizeAngle(value) {
            if (!isFinite(value)) {
                return 0;
            }
            value = value % 360;
            if (value > 180) {
                value -= 360;
            } else if (value < -180) {
                value += 360;
            }
            return value;
        }

        function step() {
            angle += velocity;
            angle = normalizeAngle(angle);
            velocity *= friction;

            if (Math.abs(velocity) <= 0.05) {
                velocity = 0;
                angle *= 0.8; // ease back toward center
            }

            if (Math.abs(angle) <= 0.5 && velocity === 0) {
                angle = 0;
                logo.style.transform = 'perspective(1200px) rotateY(0deg)';
                logo.classList.remove('is-spinning');
                rafId = null;
                return;
            }

            logo.style.transform = 'perspective(1200px) rotateY(' + angle + 'deg)';

            if (!logo.classList.contains('is-spinning')) {
                logo.classList.add('is-spinning');
            }

            rafId = requestAnimationFrame(step);
        }

        function boost(amount) {
            velocity += amount;
            if (!rafId) {
                rafId = requestAnimationFrame(step);
            }
        }

        logo.addEventListener('pointerenter', function () {
            boost(5);
        });

        logo.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            boost(12);
        });

        logo.addEventListener('pointerup', function () {
            boost(6);
        });

        logo.addEventListener('pointerleave', function () {
            // Allow natural slowdown via friction
        });

        logo.addEventListener('click', function (event) {
            event.preventDefault();
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

            toggle.addEventListener('click', function () {
                var isCollapsed = section.classList.toggle('is-collapsed');
                var expanded = !isCollapsed;
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                if (expanded) {
                    content.removeAttribute('hidden');
                    if (arrow && toggle.dataset.arrowUp) {
                        arrow.src = toggle.dataset.arrowUp;
                        arrow.alt = arrow.alt.replace('Expand', 'Collapse');
                    }
                    if (indicator) {
                        var upIcon = indicator.getAttribute('data-icon-up');
                        if (upIcon) {
                            indicator.setAttribute('src', upIcon);
                        }
                    }
                } else {
                    content.setAttribute('hidden', 'hidden');
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
        });
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
            modal.setAttribute('hidden', 'hidden');
            var controller = document.querySelector('[aria-controls="' + modal.id + '"]');
            if (controller) {
                controller.setAttribute('aria-expanded', 'false');
                controller.focus();
            }
        }

        function openModal(modal, trigger) {
            if (!modal) {
                return;
            }
            modal.removeAttribute('hidden');
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
    });
})();
