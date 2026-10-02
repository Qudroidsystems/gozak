{{-- Row action menu for the promo banners DataTable (rendered by PromoBannerController@data) --}}
<div class="dropdown">
    <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
        <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item view-btn" href="javascript:void(0);"
               data-id="{{ $banner->id }}"
               data-badge="{{ $banner->badge_text }}"
               data-title="{{ $banner->title }}"
               data-subtitle="{{ $banner->subtitle }}"
               data-cta-text="{{ $banner->cta_text }}"
               data-cta-route="{{ $banner->cta_route }}"
               data-gradient-start="{{ $banner->gradient_start }}"
               data-gradient-end="{{ $banner->gradient_end }}"
               data-accent="{{ $banner->accent_color }}"
               data-screen="{{ $banner->target_screen }}"
               data-style="{{ $banner->display_style }}"
               data-amount="{{ $banner->amount_text }}"
               data-masked-user="{{ $banner->masked_user }}"
               data-from-label="{{ $banner->from_label }}"
               data-type-label="{{ $banner->type_label }}"
               data-date-label="{{ $banner->date_label }}"
               data-conditions="{{ $banner->conditions_text }}"
               data-announcement="{{ $banner->announcement_text }}"
               data-image="{{ $banner->full_image_url }}">
                <i class="bi bi-eye me-1"></i> View
            </a>
        </li>
        @can('Update promo_banner')
            <li>
                <a class="dropdown-item edit-btn" href="javascript:void(0);"
                   data-id="{{ $banner->id }}"
                   data-badge="{{ $banner->badge_text }}"
                   data-title="{{ $banner->title }}"
                   data-subtitle="{{ $banner->subtitle }}"
                   data-cta-text="{{ $banner->cta_text }}"
                   data-cta-route="{{ $banner->cta_route }}"
                   data-gradient-start="{{ $banner->gradient_start }}"
                   data-gradient-end="{{ $banner->gradient_end }}"
                   data-accent="{{ $banner->accent_color }}"
                   data-screen="{{ $banner->target_screen }}"
                   data-active="{{ $banner->active ? '1' : '0' }}"
                   data-starts="{{ $banner->starts_at?->format('Y-m-d\TH:i') }}"
                   data-ends="{{ $banner->ends_at?->format('Y-m-d\TH:i') }}"
                   data-image="{{ $banner->full_image_url }}"
                   data-lottie="{{ $banner->lottie_asset }}"
                   data-show-once="{{ $banner->show_once_daily ? '1' : '0' }}"
                   data-sort="{{ $banner->sort_order }}"
                   data-style="{{ $banner->display_style }}"
                   data-amount="{{ $banner->amount_text }}"
                   data-masked-user="{{ $banner->masked_user }}"
                   data-from-label="{{ $banner->from_label }}"
                   data-type-label="{{ $banner->type_label }}"
                   data-date-label="{{ $banner->date_label }}"
                   data-conditions="{{ $banner->conditions_text }}"
                   data-announcement="{{ $banner->announcement_text }}">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            </li>
        @endcan
        <li>
            <a class="dropdown-item toggle-status-btn" href="javascript:void(0);"
               data-id="{{ $banner->id }}"
               data-status="{{ $banner->active ? 'active' : 'inactive' }}">
                <i class="bi bi-arrow-repeat me-1"></i>
                {{ $banner->active ? 'Deactivate' : 'Activate' }}
            </a>
        </li>
        @can('Delete promo_banner')
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger remove-item-btn" href="javascript:void(0);"
                   data-id="{{ $banner->id }}"
                   data-title="{{ $banner->title }}">
                    <i class="bi bi-trash me-1"></i> Delete
                </a>
            </li>
        @endcan
    </ul>
</div>
