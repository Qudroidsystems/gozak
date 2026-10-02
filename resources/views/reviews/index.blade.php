@extends('layouts.master')
@section('title', 'Product Reviews')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Product Reviews" icon="ri-star-smile-line" subtitle="What customers say about your products. Reply publicly as GozakMart from the review details." />

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Total reviews" :value="number_format($stats['total'])" icon="ri-chat-3-line" accent="sky" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Average rating" :value="$stats['average'] . ' ★'" icon="ri-star-line" accent="amber" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Awaiting reply" :value="number_format($stats['unanswered'])" icon="ri-question-answer-line" accent="violet" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="1–2 star reviews" :value="number_format($stats['low'])" icon="ri-emotion-unhappy-line" accent="rose" /></div>
    </div>

    <x-cb.card title="All Reviews" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <select id="f-rating" class="form-select form-select-sm" data-dt-filter="#reviewsTable" style="width:auto;">
                <option value="">All ratings</option>
                @for($s = 5; $s >= 1; $s--)
                    <option value="{{ $s }}">{{ $s }} star{{ $s > 1 ? 's' : '' }} ({{ $breakdown[$s] ?? 0 }})</option>
                @endfor
            </select>
            <select id="f-reply" class="form-select form-select-sm" data-dt-filter="#reviewsTable" style="width:auto;">
                <option value="">Any reply status</option>
                <option value="unanswered">Awaiting reply</option>
                <option value="answered">Replied</option>
            </select>
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="reviewsTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Reply</th>
                        <th>Date</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

{{-- View / reply modal --}}
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div><span class="fw-bold" id="rv-product"></span><small class="d-block text-muted" id="rv-meta"></small></div>
                    <span class="text-warning fs-5" id="rv-stars"></span>
                </div>
                <div class="p-3 rounded mb-3" style="background:var(--cb-surface-2);" id="rv-comment"></div>

                @can('Update review')
                <form id="replyForm">
                    <label class="form-label">Reply as GozakMart</label>
                    <textarea name="company_comment" id="rv-reply" class="form-control" rows="3" maxlength="1000" required placeholder="Thank the customer, or explain how you'll make it right…"></textarea>
                    <div class="text-end mt-2">
                        <button type="submit" class="btn btn-primary" id="replySubmit"><i class="ri-send-plane-line me-1"></i> Save reply</button>
                    </div>
                </form>
                @else
                <div id="rv-reply-readonly" class="text-muted"></div>
                @endcan
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var URLS = {
        data:    @json(route('web.reviews.data')),
        edit:    @json(route('web.reviews.edit', '__ID__')),
        reply:   @json(route('web.reviews.company-comment', '__ID__')),
        destroy: @json(route('web.reviews.destroy', '__ID__'))
    };
    var u = function (k, id) { return URLS[k].replace('__ID__', id); };

    var table = GZ.dt('#reviewsTable', {
        url: URLS.data,
        order: [[5, 'desc']],
        filters: function () { return { rating: $('#f-rating').val(), reply: $('#f-reply').val() }; },
        columns: [
            { data: 'product',    name: 'product' },
            { data: 'user_name',  name: 'product_reviews.user_name' },
            { data: 'rating',     name: 'product_reviews.rating', searchable: false },
            { data: 'comment',    name: 'product_reviews.comment' },
            { data: 'reply',      name: 'product_reviews.company_comment', searchable: false },
            { data: 'created_at', name: 'product_reviews.created_at', searchable: false },
            { data: 'action',     name: 'action', orderable: false, searchable: false }
        ]
    });

    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal'));
    var currentId = null;

    $('#reviewsTable').on('click', '.view-btn', function () {
        $.get(u('edit', $(this).data('id'))).done(function (r) {
            currentId = r.id;
            var n = Math.round(r.rating);
            $('#rv-product').text(r.product_title || '');
            $('#rv-meta').text((r.user_name || 'Anonymous') + ' · ' + r.created_at);
            $('#rv-stars').text('★'.repeat(n) + '☆'.repeat(Math.max(0, 5 - n)));
            $('#rv-comment').text(r.comment || '');
            $('#rv-reply').val(r.company_comment || '');
            $('#rv-reply-readonly').text(r.company_comment || 'No reply yet.');
            modal.show();
        }).fail(function (x) { GZ.toast(GZ.xhrError(x), 'error'); });
    });

    $('#replyForm').on('submit', function (e) {
        e.preventDefault();
        var btn = $('#replySubmit').prop('disabled', true);
        $.post(u('reply', currentId), { company_comment: $('#rv-reply').val() })
            .done(function (r) { modal.hide(); GZ.toast(r.message || 'Reply saved'); table.ajax.reload(null, false); })
            .fail(function (x) { Swal.fire({ icon: 'error', title: 'Could not save', text: GZ.xhrError(x) }); })
            .always(function () { btn.prop('disabled', false); });
    });

    $('#reviewsTable').on('click', '.delete-btn', function () {
        GZ.destroy(u('destroy', $(this).data('id')), 'this review', table);
    });
});
</script>
@endsection
