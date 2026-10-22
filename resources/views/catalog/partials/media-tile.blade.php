{{-- One gallery tile. Rendered server-side for existing media, and as a <template> ($m = null) for new uploads. --}}
@php
    $isVideo  = $m ? $m->isVideo() : false;
    $selected = $m && $m->attribute_name ? $m->attribute_name . '|' . $m->attribute_value : '';
@endphp
<div class="media-tile" data-id="{{ $m?->id }}">
    <img src="{{ $m ? ($m->thumb_url ?: $m->url) : '' }}" alt="{{ $m?->alt_text }}" loading="lazy">
    <span class="drag" title="Drag to reorder"><i class="ri-drag-move-2-line"></i></span>
    <span class="badge bg-dark badge-type {{ $isVideo ? '' : 'd-none' }}">{{ $isVideo ? ($m->duration_seconds ? $m->duration_seconds . 's' : 'Video') : '' }}</span>
    <div class="play {{ $isVideo ? '' : 'd-none' }}"><i class="ri-play-circle-fill"></i></div>
    <span class="badge bg-primary opt-tag text-truncate {{ $selected ? '' : 'd-none' }}">{{ $m?->attribute_value }}</span>
    <div class="tile-bar">
        @if(!empty($optionValues))
            <select class="form-select form-select-sm opt-select py-0" style="font-size:11px" title="Show for option">
                <option value="">All options</option>
                @foreach($optionValues as $name => $vals)
                    <optgroup label="{{ $name }}">
                        @foreach($vals as $val)
                            <option value="{{ $name }}|{{ $val }}" @selected($selected === $name . '|' . $val)>{{ $val }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        @else
            <span></span>
        @endif
        <a class="open-link text-muted" href="{{ $m?->url }}" target="_blank" title="Open"><i class="ri-external-link-line"></i></a>
        <button type="button" class="btn btn-sm btn-link text-danger p-0 del-media" title="Remove"><i class="ri-delete-bin-line"></i></button>
    </div>
</div>
