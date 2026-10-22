@extends('layouts.master')

@section('title', 'Photos, video & specs')

@section('content')
<style>
    .media-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:12px; }
    .media-tile { position:relative; border:1px solid var(--vz-border-color, #e9ebec); border-radius:8px; overflow:hidden; background:#f8f9fa; }
    .media-tile img { width:100%; aspect-ratio:1/1; object-fit:cover; display:block; }
    .media-tile .tile-bar { display:flex; justify-content:space-between; align-items:center; padding:4px 6px; background:#fff; font-size:12px; gap:4px; }
    .media-tile .drag { position:absolute; top:6px; left:6px; background:rgba(0,0,0,.55); color:#fff; border-radius:4px; padding:0 4px; cursor:grab; }
    .media-tile .badge-type { position:absolute; top:6px; right:6px; }
    .media-tile .play { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; }
    .media-tile .play i { font-size:42px; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,.5); }
    .media-tile .opt-tag { position:absolute; left:6px; bottom:36px; max-width:calc(100% - 12px); }
    .drop-zone { border:2px dashed #ced4da; border-radius:10px; padding:22px; text-align:center; cursor:pointer; transition:background .15s; }
    .drop-zone.drag-over { background:rgba(64,81,137,.06); border-color:#405189; }
    .sg-table input { min-width:70px; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $product->title }}</h4>
                            <small class="text-muted">SKU {{ $product->sku }} · Photos, video & specs</small>
                        </div>
                        <div class="page-title-right">
                            <a href="{{ route('web.products.index') }}" class="btn btn-sm btn-light"><i class="ri-arrow-left-line me-1"></i>Back to products</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                {{-- ── Left: media ─────────────────────────────────────────────── --}}
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0">Gallery</h5>
                            <small class="text-muted">Drag to reorder: the first item is what shoppers see first. Videos are placed first automatically.</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label class="drop-zone d-block mb-0" id="photoDrop">
                                        <i class="ri-image-add-line fs-2 text-primary d-block"></i>
                                        <strong>Add photos</strong>
                                        <div class="small text-muted">Drop files here or click. JPG, PNG or WebP, up to 10 MB each, max 20 at a time.<br>They're resized and converted to WebP automatically.</div>
                                        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" multiple hidden>
                                    </label>
                                </div>
                                <div class="col-md-5">
                                    <label class="drop-zone d-block mb-0" id="videoDrop">
                                        <i class="ri-video-add-line fs-2 text-danger d-block"></i>
                                        <strong>Add a short video</strong>
                                        <div class="small text-muted">MP4, MOV or WebM · up to {{ $limits['video_seconds'] }}s and {{ $limits['video_mb'] }} MB.<br>Portrait (9:16) or square looks best in the app.</div>
                                        <input type="file" id="videoInput" accept="video/mp4,video/quicktime,video/webm" hidden>
                                    </label>
                                </div>
                            </div>

                            @if(!empty($optionValues))
                            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                <label class="form-label mb-0 small">New uploads are for:</label>
                                <select id="uploadOption" class="form-select form-select-sm" style="max-width:260px">
                                    <option value="">All options (general photos)</option>
                                    @foreach($optionValues as $name => $vals)
                                        <optgroup label="{{ $name }}">
                                            @foreach($vals as $val)
                                                <option value="{{ $name }}|{{ $val }}">{{ $name }}: {{ $val }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <small class="text-muted">Tie photos to a colour so the app shows them when that colour is picked.</small>
                            </div>
                            @endif

                            <div id="uploadProgress" class="progress mb-3 d-none" style="height:6px">
                                <div class="progress-bar" style="width:0%"></div>
                            </div>

                            <div class="small text-muted mb-2">
                                Server upload limit: {{ $limits['server_upload'] }} per file, {{ $limits['server_post'] }} per request.
                                @if((int) $limits['server_upload'] > 0 && (int) $limits['server_upload'] < $limits['video_mb'])
                                    <span class="text-danger">This is lower than the {{ $limits['video_mb'] }} MB video limit. Raise <code>upload_max_filesize</code> and <code>post_max_size</code> in php.ini to upload larger videos.</span>
                                @endif
                            </div>

                            <div id="mediaGrid" class="media-grid">
                                @foreach($product->media as $m)
                                    @include('catalog.partials.media-tile', ['m' => $m, 'optionValues' => $optionValues])
                                @endforeach
                            </div>
                            <p id="emptyMedia" class="text-muted text-center my-4 {{ $product->media->isEmpty() ? '' : 'd-none' }}">
                                No photos yet. The product thumbnail is used until you add some.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Right: specs + size guide ─────────────────────────────── --}}
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Specifications</h5>
                            <button class="btn btn-sm btn-primary" id="saveSpecs">Save</button>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">Shown under “Specifications” on the product page, e.g. Material: 100% Cotton.</small>
                            <div id="specList" class="d-flex flex-column gap-2">
                                @foreach($product->specifications as $s)
                                    <div class="d-flex gap-1 spec-row">
                                        <span class="drag-handle text-muted pt-1" style="cursor:grab"><i class="ri-drag-move-2-line"></i></span>
                                        <input class="form-control form-control-sm spec-label" value="{{ $s->label }}" placeholder="Label" maxlength="80">
                                        <input class="form-control form-control-sm spec-value" value="{{ $s->value }}" placeholder="Value" maxlength="1000">
                                        <button class="btn btn-sm btn-link text-danger px-1" onclick="this.closest('.spec-row').remove()"><i class="ri-close-line"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <button class="btn btn-sm btn-outline-primary" onclick="addSpec()">+ Add row</button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Common rows</button>
                                    <ul class="dropdown-menu">
                                        @foreach(['Material', 'Brand', 'Model', 'Colour', 'Weight', 'Dimensions', 'Country of origin', 'Warranty', 'What’s in the box', 'Care instructions', 'Power', 'Capacity'] as $label)
                                            <li><a class="dropdown-item" href="#" onclick="addSpec(@json($label));return false;">{{ $label }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Size guide</h5>
                            <div class="d-flex gap-1">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Template</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" data-template="tops">Tops / shirts (cm)</a></li>
                                        <li><a class="dropdown-item" href="#" data-template="trousers">Trousers (inches)</a></li>
                                        <li><a class="dropdown-item" href="#" data-template="shoes">Shoes (EU / UK / US)</a></li>
                                    </ul>
                                </div>
                                <button class="btn btn-sm btn-primary" id="saveGuide">Save</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">Optional. Shoppers see a “Size guide” link next to the size picker.</small>
                            <div class="row g-2 mb-2">
                                <div class="col-4"><input id="sgUnit" class="form-control form-control-sm" placeholder="Unit (cm)" maxlength="12"></div>
                                <div class="col-8 text-end">
                                    <button class="btn btn-sm btn-light" onclick="sgAddCol()">+ Column</button>
                                    <button class="btn btn-sm btn-light" onclick="sgAddRow()">+ Row</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered sg-table mb-2" id="sgTable"></table>
                            </div>
                            <textarea id="sgNote" class="form-control form-control-sm" rows="2" maxlength="300" placeholder="Note (optional), e.g. how to measure"></textarea>
                            <button class="btn btn-sm btn-link text-danger px-0 mt-1" id="removeGuide">Remove size guide</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<template id="tileTemplate">@include('catalog.partials.media-tile', ['m' => null, 'optionValues' => $optionValues])</template>

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.7.7/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');

    const base = @json(url('web/catalog/products/' . $product->id . '/media'));
    const LIMITS = @json($limits);
    const grid = document.getElementById('mediaGrid');
    const progress = document.getElementById('uploadProgress');
    const bar = progress.querySelector('.progress-bar');
    const toast = (icon, title) => Swal.fire({ toast: true, position: 'top-end', icon, title, timer: 2200, showConfirmButton: false });
    const errMsg = err => err.response?.data?.message || 'Something went wrong';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // ── Reorder ─────────────────────────────────────────────────────────────
    Sortable.create(grid, {
        handle: '.drag', animation: 150,
        onEnd: () => {
            const ids = [...grid.querySelectorAll('.media-tile')].map(t => t.dataset.id);
            axios.post(`${base}/reorder`, { ids }).then(() => toast('success', 'Order saved'))
                .catch(err => toast('error', errMsg(err)));
        },
    });

    function uploadOption() {
        const v = document.getElementById('uploadOption')?.value || '';
        if (!v) return {};
        const [name, ...rest] = v.split('|');
        return { attribute_name: name, attribute_value: rest.join('|') };
    }

    function addTile(item) {
        const html = document.getElementById('tileTemplate').innerHTML;
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const tile = wrap.firstElementChild;
        tile.dataset.id = item.id;
        tile.querySelector('img').src = item.thumb_url || item.poster_url || item.url || '';
        tile.querySelector('.play').classList.toggle('d-none', item.type !== 'video');
        const typeBadge = tile.querySelector('.badge-type');
        typeBadge.textContent = item.type === 'video' ? (item.duration_seconds ? item.duration_seconds + 's' : 'Video') : '';
        typeBadge.classList.toggle('d-none', item.type !== 'video');
        const sel = tile.querySelector('.opt-select');
        if (sel) sel.value = item.attribute_name ? `${item.attribute_name}|${item.attribute_value}` : '';
        syncOptTag(tile);
        tile.querySelector('.open-link').href = item.url;
        if (item.type === 'video') grid.prepend(tile); else grid.append(tile);
        document.getElementById('emptyMedia').classList.add('d-none');
    }

    function syncOptTag(tile) {
        const sel = tile.querySelector('.opt-select');
        const tag = tile.querySelector('.opt-tag');
        if (!tag) return;
        const v = sel?.value || '';
        tag.textContent = v ? v.split('|').slice(1).join('|') : '';
        tag.classList.toggle('d-none', !v);
    }

    // ── Photos ──────────────────────────────────────────────────────────────
    async function uploadPhotos(files) {
        files = [...files].filter(f => f.type.startsWith('image/'));
        if (!files.length) return;
        for (let i = 0; i < files.length; i += 5) {      // 5 per request keeps under post_max_size
            const fd = new FormData();
            files.slice(i, i + 5).forEach(f => fd.append('photos[]', f));
            Object.entries(uploadOption()).forEach(([k, v]) => fd.append(k, v));
            progress.classList.remove('d-none');
            try {
                const res = await axios.post(`${base}/photos`, fd, {
                    onUploadProgress: e => { bar.style.width = Math.round(((i + (e.loaded / (e.total || 1)) * Math.min(5, files.length - i)) / files.length) * 100) + '%'; },
                });
                res.data.data.forEach(addTile);
            } catch (err) {
                Swal.fire('Upload failed', errMsg(err), 'error');
                break;
            }
        }
        progress.classList.add('d-none'); bar.style.width = '0%';
        toast('success', 'Photos added');
    }

    // ── Video: grab a poster frame + duration in the browser, then upload ──
    function inspectVideo(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const v = document.createElement('video');
            v.preload = 'metadata'; v.muted = true; v.playsInline = true; v.src = url;
            v.onloadedmetadata = () => { v.currentTime = Math.min(1, (v.duration || 1) / 3); };
            v.onseeked = () => {
                const canvas = document.createElement('canvas');
                const scale = Math.min(1, 1080 / Math.max(v.videoWidth, v.videoHeight));
                canvas.width = Math.round(v.videoWidth * scale);
                canvas.height = Math.round(v.videoHeight * scale);
                canvas.getContext('2d').drawImage(v, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => { URL.revokeObjectURL(url); resolve({ duration: v.duration, poster: blob }); }, 'image/jpeg', 0.85);
            };
            v.onerror = () => { URL.revokeObjectURL(url); reject(new Error('This video format can’t be read by the browser. Try exporting it as MP4 (H.264).')); };
        });
    }

    async function uploadVideo(file) {
        if (!file) return;
        if (file.size > LIMITS.video_mb * 1024 * 1024) {
            return Swal.fire('Video too large', `Keep it under ${LIMITS.video_mb} MB. Most phones can trim and export a smaller copy.`, 'warning');
        }
        let meta;
        try { meta = await inspectVideo(file); }
        catch (e) { return Swal.fire('Can’t read video', e.message, 'error'); }
        if (meta.duration > LIMITS.video_seconds + 0.5) {
            return Swal.fire('Video too long', `Keep it to ${LIMITS.video_seconds} seconds or less (this one is ${Math.round(meta.duration)}s).`, 'warning');
        }
        const fd = new FormData();
        fd.append('video', file);
        if (meta.poster) fd.append('poster', meta.poster, 'poster.jpg');
        fd.append('duration', Math.round(meta.duration));
        Object.entries(uploadOption()).forEach(([k, v]) => fd.append(k, v));
        progress.classList.remove('d-none');
        try {
            const res = await axios.post(`${base}/video`, fd, {
                onUploadProgress: e => { bar.style.width = Math.round((e.loaded / (e.total || 1)) * 100) + '%'; },
            });
            addTile(res.data.data);
            toast('success', 'Video added');
        } catch (err) {
            const tooBig = err.response?.status === 413;
            Swal.fire('Upload failed', tooBig ? 'The server rejected the file size. Raise upload_max_filesize / post_max_size in php.ini.' : errMsg(err), 'error');
        } finally {
            progress.classList.add('d-none'); bar.style.width = '0%';
        }
    }

    // ── Drop zones ──────────────────────────────────────────────────────────
    function wireDrop(zoneId, inputId, handler) {
        const zone = document.getElementById(zoneId), input = document.getElementById(inputId);
        input.addEventListener('change', () => { handler(input.files); input.value = ''; });
        ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag-over'); }));
        ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('drag-over'); }));
        zone.addEventListener('drop', e => handler(e.dataTransfer.files));
    }
    wireDrop('photoDrop', 'photoInput', uploadPhotos);
    wireDrop('videoDrop', 'videoInput', files => uploadVideo(files[0]));

    // ── Tile actions ────────────────────────────────────────────────────────
    grid.addEventListener('change', e => {
        if (!e.target.classList.contains('opt-select')) return;
        const tile = e.target.closest('.media-tile');
        const [name, ...rest] = (e.target.value || '').split('|');
        axios.patch(`${base}/${tile.dataset.id}`, { attribute_name: e.target.value ? name : null, attribute_value: e.target.value ? rest.join('|') : null })
            .then(() => { syncOptTag(tile); toast('success', 'Saved'); })
            .catch(err => toast('error', errMsg(err)));
    });

    grid.addEventListener('click', e => {
        const del = e.target.closest('.del-media');
        if (!del) return;
        const tile = del.closest('.media-tile');
        Swal.fire({ icon: 'warning', title: 'Remove this item?', showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#d33' })
            .then(r => {
                if (!r.isConfirmed) return;
                axios.delete(`${base}/${tile.dataset.id}`).then(() => {
                    tile.remove();
                    if (!grid.children.length) document.getElementById('emptyMedia').classList.remove('d-none');
                }).catch(err => toast('error', errMsg(err)));
            });
    });

    // ── Specifications ──────────────────────────────────────────────────────
    const specList = document.getElementById('specList');
    Sortable.create(specList, { handle: '.drag-handle', animation: 150 });
    window.addSpec = function (label = '') {
        specList.insertAdjacentHTML('beforeend', `
            <div class="d-flex gap-1 spec-row">
                <span class="drag-handle text-muted pt-1" style="cursor:grab"><i class="ri-drag-move-2-line"></i></span>
                <input class="form-control form-control-sm spec-label" value="${esc(label)}" placeholder="Label" maxlength="80">
                <input class="form-control form-control-sm spec-value" placeholder="Value" maxlength="1000">
                <button class="btn btn-sm btn-link text-danger px-1" onclick="this.closest('.spec-row').remove()"><i class="ri-close-line"></i></button>
            </div>`);
        specList.lastElementChild.querySelector(label ? '.spec-value' : '.spec-label').focus();
    };
    if (!specList.children.length) addSpec();

    document.getElementById('saveSpecs').addEventListener('click', () => {
        const specifications = [...specList.querySelectorAll('.spec-row')].map(r => ({
            label: r.querySelector('.spec-label').value.trim(),
            value: r.querySelector('.spec-value').value.trim(),
        })).filter(s => s.label || s.value);
        axios.put(`${base}/specifications`, { specifications })
            .then(() => toast('success', 'Specifications saved'))
            .catch(err => Swal.fire('Not saved', errMsg(err), 'error'));
    });

    // ── Size guide ──────────────────────────────────────────────────────────
    const TEMPLATES = {
        tops:     { unit: 'cm', columns: ['Size', 'Chest', 'Waist', 'Length'], rows: [['S','86-91','71-76','68'],['M','96-101','81-86','70'],['L','106-111','91-96','72'],['XL','116-121','101-106','74'],['XXL','126-131','111-116','76']], note: 'Measure around the fullest part of your chest.' },
        trousers: { unit: 'inches', columns: ['Size', 'Waist', 'Hip', 'Inside leg'], rows: [['30','30','38','31'],['32','32','40','32'],['34','34','42','32'],['36','36','44','32'],['38','38','46','33']], note: '' },
        shoes:    { unit: '', columns: ['EU', 'UK', 'US', 'Foot length (cm)'], rows: [['39','5.5','6.5','24.5'],['40','6.5','7.5','25'],['41','7','8','25.5'],['42','8','9','26.5'],['43','9','10','27'],['44','9.5','10.5','28'],['45','10.5','11.5','28.5']], note: 'If you are between sizes, pick the larger size.' },
    };
    let guide = @json($product->size_guide) || { unit: '', columns: ['Size', ''], rows: [['', '']], note: '' };
    const sgTable = document.getElementById('sgTable');

    function readGuide() {
        const columns = [...sgTable.querySelectorAll('thead input')].map(i => i.value.trim());
        const rows = [...sgTable.querySelectorAll('tbody tr')].map(tr => [...tr.querySelectorAll('input')].map(i => i.value.trim()));
        return { unit: document.getElementById('sgUnit').value.trim(), columns, rows, note: document.getElementById('sgNote').value.trim() };
    }
    function renderGuide() {
        document.getElementById('sgUnit').value = guide.unit || '';
        document.getElementById('sgNote').value = guide.note || '';
        const head = guide.columns.map((c, ci) => `<th><div class="d-flex gap-1"><input class="form-control form-control-sm fw-semibold" value="${esc(c)}" maxlength="30">${ci > 0 ? `<button class="btn btn-sm btn-link text-danger p-0" onclick="sgDelCol(${ci})">×</button>` : ''}</div></th>`).join('');
        const body = guide.rows.map((r, ri) => `<tr>${guide.columns.map((_, ci) => `<td><input class="form-control form-control-sm" value="${esc(r[ci] ?? '')}" maxlength="30"></td>`).join('')}<td class="p-0 align-middle"><button class="btn btn-sm btn-link text-danger" onclick="sgDelRow(${ri})">×</button></td></tr>`).join('');
        sgTable.innerHTML = `<thead class="table-light"><tr>${head}<th></th></tr></thead><tbody>${body}</tbody>`;
    }
    window.sgAddCol = () => { guide = readGuide(); guide.columns.push(''); guide.rows.forEach(r => r.push('')); renderGuide(); };
    window.sgAddRow = () => { guide = readGuide(); guide.rows.push(guide.columns.map(() => '')); renderGuide(); };
    window.sgDelCol = ci => { guide = readGuide(); guide.columns.splice(ci, 1); guide.rows.forEach(r => r.splice(ci, 1)); renderGuide(); };
    window.sgDelRow = ri => { guide = readGuide(); guide.rows.splice(ri, 1); if (!guide.rows.length) guide.rows.push(guide.columns.map(() => '')); renderGuide(); };
    document.querySelectorAll('[data-template]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault(); guide = JSON.parse(JSON.stringify(TEMPLATES[a.dataset.template])); renderGuide();
    }));
    document.getElementById('saveGuide').addEventListener('click', () => {
        const g = readGuide();
        axios.put(`${base}/size-guide`, { size_guide: g })
            .then(() => toast('success', 'Size guide saved'))
            .catch(err => Swal.fire('Not saved', errMsg(err), 'error'));
    });
    document.getElementById('removeGuide').addEventListener('click', () => {
        axios.put(`${base}/size-guide`, { size_guide: null }).then(() => {
            guide = { unit: '', columns: ['Size', ''], rows: [['', '']], note: '' }; renderGuide(); toast('success', 'Size guide removed');
        });
    });
    renderGuide();
})();
</script>
@endsection
