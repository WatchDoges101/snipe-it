@props([
    'item' => null,
    'route',
    'tooltip' => true,
])

@can('checkout', $item)
    @if ((method_exists($item, 'numRemaining')) && ($item->numRemaining() > 0))
        <a href="{{ $route  }}" class="btn btn-sm bg-maroon btn-social btn-block hidden-print"{!! $tooltip ? ' data-tooltip="true" title="'.e(trans('general.checkout')).'"' : '' !!}>
            <x-icon type="checkout" />
            {{ trans('general.checkout') }}
        </a>
    @endif
@endcan
