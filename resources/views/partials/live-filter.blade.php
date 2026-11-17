<script>
(() => {
    document.querySelectorAll('form[data-live-filter]').forEach(form => {
        if (form.dataset.liveBound) return;
        form.dataset.liveBound = '1';
        const sel = form.dataset.liveFilter;
        let timer, ctrl;

        const run = async (url) => {
            ctrl?.abort();
            ctrl = new AbortController();
            try {
                const res = await fetch(url, { signal: ctrl.signal, credentials: 'same-origin', headers: { Accept: 'text/html' } });
                if (!res.ok) throw new Error(res.status);
                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const fresh = doc.querySelector(sel);
                if (!fresh) throw new Error('missing results');
                document.querySelector(sel).replaceWith(fresh);
                history.replaceState(null, '', url);
            } catch (e) {
                if (e.name !== 'AbortError') window.location.assign(url);
            }
        };

        const apply = () => {
            clearTimeout(timer);
            const url = new URL(form.action || location.href, location.href);
            url.search = '';
            for (const [k, v] of new FormData(form)) {
                if (String(v).trim()) url.searchParams.set(k, String(v).trim());
            }
            run(url.toString());
        };

        form.addEventListener('submit', e => { e.preventDefault(); apply(); });
        form.querySelectorAll('input[type=search],input[type=text]').forEach(i =>
            i.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(apply, 400); }));
        form.querySelectorAll('select,input[type=date]').forEach(i => {
            i.addEventListener('change', apply);
            window.jQuery?.(i).on('change', apply);
        });
        document.addEventListener('click', e => {
            const a = e.target.closest(`${sel} .pagination a`);
            if (a) { e.preventDefault(); run(a.href); }
        });
    });
})();
</script>