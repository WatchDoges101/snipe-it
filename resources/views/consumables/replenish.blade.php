@extends('layouts/edit-form', [
    'createText' => trans('admin/consumables/form.replenish'),
    'updateText' => trans('admin/consumables/form.replenish'),
    'httpMethod' => 'POST',
    'helpText' => trans('help.consumables'),
    'helpPosition' => 'right',
    'formAction' => route('consumables.replenish.store', $item->id),
    'index_route' => 'consumables.index',
    'options' => [
                'back' => trans('admin/hardware/form.redirect_to_type',['type' => trans('general.previous_page')]),
                'index' => trans('admin/hardware/form.redirect_to_all', ['type' => 'consumables']),
                'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.consumable')]),
               ]
])

@section('inputFields')

    <div class="form-group required">
        <label class="col-md-3 control-label">QTY to Replenish</label>
        <div class="col-md-7 col-sm-12">
            <input type="number" name="qty" class="form-control" min="1" value="1" required />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('general.order_number') }}</label>
        <div class="col-md-7 col-sm-12">
            <input type="text" name="order_number" class="form-control" value="{{ old('order_number') }}" />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('general.note') }}</label>
        <div class="col-md-7 col-sm-12">
            <textarea name="note" class="form-control" rows="4"></textarea>
        </div>
    </div>

@endsection
