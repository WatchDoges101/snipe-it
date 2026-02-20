@props([
    'item' => null,
    'route' => null,
    'tooltip' => true,
])

@can('create', $item)
    <!-- start clone button component -->
    <a href="{{ $route }}" class="btn btn-block btn-sm btn-info btn-social hidden-print"{!! $tooltip ? ' data-tooltip="true" title="'.e(trans('general.clone')).'"' : '' !!}>
    <x-icon type="clone" />
    {{ trans('general.clone') }}
    <!-- end clone button component -->
</a>
@endcan