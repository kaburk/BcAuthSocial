(function () {
    'use strict';

    function setPending(link, pending) {
        if (!link) {
            return;
        }

        const body = document.body;
        const title = link.querySelector('.bca-login-alt-methods__title');

        if (!link.dataset.originalText) {
            link.dataset.originalText = title ? title.textContent : link.textContent;
        }

        link.dataset.loading = pending ? 'true' : 'false';
        link.setAttribute('aria-busy', pending ? 'true' : 'false');
        link.setAttribute('aria-disabled', pending ? 'true' : 'false');
        link.style.pointerEvents = pending ? 'none' : '';
        link.style.cursor = pending ? 'progress' : '';

        if (body) {
            body.style.cursor = pending ? 'progress' : '';
        }

        if (title) {
            title.textContent = pending ? '接続中...' : link.dataset.originalText;
            return;
        }

        link.textContent = pending ? '接続中...' : link.dataset.originalText;
    }

    function handleClick(event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('[data-bc-social-login-button="true"]');
        if (!link) {
            return;
        }

        // 補助操作（新規タブ等）は標準挙動を優先する
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        if (!link || link.dataset.loading === 'true') {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        setPending(link, true);

        // 遷移前に1フレーム待って描画を反映させる
        window.requestAnimationFrame(function () {
            window.setTimeout(function () {
                window.location.assign(link.href);
            }, 40);
        });
    }

    document.addEventListener('click', handleClick, false);
})();
