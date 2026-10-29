<?php
$viewerDocuments = array_values(array_filter(
    $documents ?? [],
    static fn (array $document): bool => ! empty($document['url']) && ! empty($document['label'])
));
?>
<?php if ($viewerDocuments !== []): ?>
<div id="secure-document-viewer" class="fixed inset-0 z-[300] hidden items-center justify-center bg-slate-950/90 p-4" role="dialog" aria-modal="true" aria-labelledby="secure-document-title">
    <div class="flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wider text-rescue-600">Secure document viewer</p><h2 id="secure-document-title" class="truncate text-lg font-bold text-slate-900">Application document</h2></div>
            <div class="flex shrink-0 items-center gap-2"><a id="secure-document-new-tab" href="#" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Open in new tab</a><button id="secure-document-close" type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Close</button></div>
        </header>
        <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100">
            <div id="secure-document-loading" class="absolute inset-0 grid place-items-center"><div class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow">Loading document…</div></div>
            <div id="secure-document-error" class="absolute inset-0 hidden place-items-center p-6 text-center"><div class="max-w-md rounded-2xl bg-white p-6 shadow"><h3 class="font-bold text-slate-900">Document preview unavailable</h3><p class="mt-2 text-sm text-slate-600">Try opening the document in a new tab. If it still does not load, sign in again and retry.</p></div></div>
            <img id="secure-document-image" alt="" class="hidden h-full w-full object-contain p-3">
            <iframe id="secure-document-frame" title="Application document preview" class="hidden h-full w-full border-0 bg-white"></iframe>
        </div>
    </div>
</div>
<script>
(() => {
    const documents = <?= json_encode($viewerDocuments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
    const modal = document.getElementById('secure-document-viewer');
    const title = document.getElementById('secure-document-title');
    const newTab = document.getElementById('secure-document-new-tab');
    const closeButton = document.getElementById('secure-document-close');
    const loading = document.getElementById('secure-document-loading');
    const error = document.getElementById('secure-document-error');
    const image = document.getElementById('secure-document-image');
    const frame = document.getElementById('secure-document-frame');
    let objectUrl = null;

    function resetPreview() {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        image.removeAttribute('src');
        frame.src = 'about:blank';
        image.classList.add('hidden');
        frame.classList.add('hidden');
        error.classList.add('hidden');
        error.classList.remove('grid');
        loading.classList.remove('hidden');
    }

    function closeViewer() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');
        resetPreview();
    }

    async function openViewer(documentInfo) {
        resetPreview();
        title.textContent = documentInfo.label;
        newTab.href = documentInfo.url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');

        try {
            const response = await fetch(documentInfo.url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'image/*,application/pdf' },
            });
            if (!response.ok) throw new Error(`Document request failed (${response.status})`);
            const documentBlob = await response.blob();
            objectUrl = URL.createObjectURL(documentBlob);
            loading.classList.add('hidden');

            if (documentBlob.type.startsWith('image/')) {
                image.alt = documentInfo.label;
                image.src = objectUrl;
                image.classList.remove('hidden');
                return;
            }
            if (documentBlob.type === 'application/pdf') {
                frame.src = objectUrl;
                frame.classList.remove('hidden');
                return;
            }
            throw new Error(`Unsupported document type: ${documentBlob.type || 'unknown'}`);
        } catch (exception) {
            console.warn('Document preview failed.', exception);
            loading.classList.add('hidden');
            error.classList.remove('hidden');
            error.classList.add('grid');
        }
    }

    function bindDocumentButtons() {
        const buttons = [...document.querySelectorAll('button[type="button"]')];
        documents.forEach((documentInfo) => {
            const button = buttons.find((candidate) =>
                candidate.dataset.secureDocumentBound !== 'true'
                && candidate.textContent.replace(/\s+/g, ' ').trim().includes(documentInfo.label)
            );
            if (!button) return;
            button.dataset.secureDocumentBound = 'true';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();
                void openViewer(documentInfo);
            }, true);
        });
    }

    closeButton.addEventListener('click', closeViewer);
    modal.addEventListener('click', (event) => { if (event.target === modal) closeViewer(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeViewer(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindDocumentButtons);
    else bindDocumentButtons();
})();
</script>
<?php endif; ?>
