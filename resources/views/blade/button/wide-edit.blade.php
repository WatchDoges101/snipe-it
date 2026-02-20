@props([
    'item' => null,
    'route' => null,
    'tooltip' => true,
])

@can('update', $item)
<!-- start update button component -->
@if ($item->deleted_at=='')
<a href="{{ ($item->deleted_at == '') ? $route: '#' }}" class="btn btn-block btn-sm btn-warning btn-social hidden-print{{ ($item->deleted_at!='') ? ' disabled' : '' }}"{!! $tooltip ? ' data-tooltip="true" title="'.e(trans('general.update')).'"' : '' !!}>
    <x-icon type="edit" />
    {{ trans('general.update') }}
</a>
@endif
<!-- end update button component -->
@endcan