@extends('layouts/edit-form', [
    'createText' => trans('admin/consumables/form.replenish'),
    'updateText' => trans('admin/consumables/form.replenish'),
    'httpMethod' => 'POST',
    'topSubmit' => true,
    'helpText' => trans('help.consumables'),
    'helpPosition' => 'right',
    'formAction' => route('consumables.replenish.store', $item->id),
    'index_route' => 'consumables.index'
])

@section('inputFields')

    <div class="form-group required">
        <label class="col-md-3 control-label">{{ trans('admin/consumables/form.qty_to_add') }}</label>
        <div class="col-md-7 col-sm-12">
            <input type="number" name="qty" class="form-control" min="1" value="1" required />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('general.note') }}</label>
        <div class="col-md-7 col-sm-12">
            <textarea name="note" class="form-control" rows="4"></textarea>
        </div>
    </div>

@endsection
