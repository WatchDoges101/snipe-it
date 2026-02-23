@extends('layouts/edit-form', [
    'createText' => trans('admin/consumables/general.replenish'),
    'updateText' => trans('admin/consumables/general.replenish'),
    'httpMethod' => 'POST',
    'helpText' => trans('help.consumables'),
    'helpPosition' => 'right',
    'formAction' => route('consumables.reset.store', $item->id),
    'index_route' => 'consumables.index',
    'options' => [
                'back' => trans('admin/hardware/form.redirect_to_type',['type' => trans('general.previous_page')]),
                'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.consumables')]),
                'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.consumable')]),
               ]
])

@section('inputFields')
    <div class="form-group required">
        <label class="col-md-3 control-label">{{ trans('admin/consumables/general.qty_to_replenish') }}</label>
        <div class="col-md-7 col-sm-12">
            <input
                type="number"
                name="qty"
                class="form-control"
                min="1"
                max="{{ $max_replenish }}"
                value="{{ old('qty', $max_replenish) }}"
                required
            />
            <p class="help-block">{{ trans('general.remaining') }}: {{ $max_replenish }}</p>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-7 col-sm-12 col-md-offset-3">
            <p class="help-block">{{ trans('admin/consumables/general.replenish_selected_qty_help') }}</p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('general.order_number') }}</label>
        <div class="col-md-7 col-sm-12">
            <input type="text" name="order_number" class="form-control" value="{{ old('order_number') }}" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('admin/consumables/general.general_note') }}</label>
        <div class="col-md-7 col-sm-12">
            <textarea name="note" class="form-control" rows="4"></textarea>
        </div>
    </div>

@endsection
