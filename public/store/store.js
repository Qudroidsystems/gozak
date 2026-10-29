/* GozakMart shopping website: shared behaviour (no framework). */
(function () {
    'use strict';
    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

    // ── Toast ───────────────────────────────────────────────────────────────
    let toastTimer;
    window.storeToast = function (html, ms = 3200) {
        const t = $('[data-toast]');
        if (!t) return;
        t.innerHTML = html;
        t.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { t.hidden = true; }, ms);
    };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    window.storeEsc = esc;

    // ── Account dropdown ────────────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-dd]');
        $$('.dd.open').forEach(d => { if (!btn || d !== btn.parentElement) d.classList.remove('open'); });
        if (btn) btn.parentElement.classList.toggle('open');
    });

    // ── Mobile category drawer ──────────────────────────────────────────────
    const drawer = $('[data-menu]');
    document.addEventListener('click', e => {
        if (e.target.closest('[data-open-menu]')) { drawer.hidden = false; document.body.style.overflow = 'hidden'; }
        if (e.target.closest('[data-close-menu]') || e.target === drawer) { drawer.hidden = true; document.body.style.overflow = ''; }
    });

    // ── Hero slider ─────────────────────────────────────────────────────────
    $$('[data-slider]').forEach(slider => {
        const track = $('.slides', slider);
        const n = track ? track.children.length : 0;
        if (n < 2) return;
        const dots = $('.dots', slider);
        let i = 0, timer;
        dots.innerHTML = Array.from({ length: n }, (_, k) => `<button type="button" aria-label="Slide ${k + 1}"></button>`).join('');
        const go = k => {
            i = (k + n) % n;
            track.style.transform = `translateX(-${i * 100}%)`;
            $$('button', dots).forEach((b, j) => b.classList.toggle('on', j === i));
        };
        const auto = () => { clearInterval(timer); timer = setInterval(() => go(i + 1), 5000); };
        dots.addEventListener('click', e => { const b = e.target.closest('button'); if (b) { go($$('button', dots).indexOf(b)); auto(); } });
        let x0 = null;
        slider.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
        slider.addEventListener('touchend', e => {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 40) { go(i + (dx < 0 ? 1 : -1)); auto(); }
            x0 = null;
        });
        go(0); auto();
    });

    // ── Countdowns (deal end times are ISO strings with offset) ─────────────
    const cds = $$('[data-countdown]');
    if (cds.length) {
        const tick = () => cds.forEach(el => {
            const ms = new Date(el.dataset.countdown).getTime() - Date.now();
            if (isNaN(ms)) return;
            if (ms <= 0) { el.textContent = 'Ended'; return; }
            const h = Math.floor(ms / 3600000), m = Math.floor(ms % 3600000 / 60000), s = Math.floor(ms % 60000 / 1000);
            el.textContent = (h > 47 ? Math.floor(h / 24) + 'd ' : String(h).padStart(2, '0') + 'h : ') + String(m).padStart(2, '0') + 'm : ' + String(s).padStart(2, '0') + 's';
        });
        tick(); setInterval(tick, 1000);
    }

    // ── Add to cart (cards and product page) ────────────────────────────────
    window.storeAddToCart = async function (productId, variationId = null, qty = 1, btn = null) {
        if (btn) { btn.disabled = true; btn.dataset.label = btn.dataset.label || btn.textContent; btn.textContent = 'Adding…'; }
        try {
            const res = await fetch(window.STORE.cartAdd, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.STORE.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ product_id: productId, variation_id: variationId, quantity: qty }),
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'Could not add to cart');
            $$('[data-cart-count]').forEach(b => { b.textContent = data.count; b.hidden = !data.count; });
            window.storeToast(`✓ ${esc(data.message || 'Added to cart')} <a href="${esc(data.cart_url)}">View cart</a>`);
            return true;
        } catch (err) {
            window.storeToast(esc(err.message));
            return false;
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label; }
        }
    };
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-add-to-cart]');
        if (!b) return;
        e.preventDefault();
        window.storeAddToCart(parseInt(b.dataset.addToCart, 10), null, 1, b);
    });

    // ── Quantity steppers ───────────────────────────────────────────────────
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-qty-step]');
        if (!b) return;
        const input = b.closest('.qty')?.querySelector('input');
        if (!input) return;
        const v = Math.max(parseInt(input.min || '1', 10), Math.min(parseInt(input.max || '999', 10), (parseInt(input.value, 10) || 1) + parseInt(b.dataset.qtyStep, 10)));
        input.value = v;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // ── Auto-submit selects (sort) ──────────────────────────────────────────
    document.addEventListener('change', e => {
        if (e.target.matches('[data-autosubmit]')) e.target.form?.submit();
    });

    // ── Filters toggle on mobile ────────────────────────────────────────────
    document.addEventListener('click', e => {
        const t = e.target.closest('[data-toggle-filters]');
        if (t) t.closest('.filters')?.classList.toggle('open');
    });
})();
