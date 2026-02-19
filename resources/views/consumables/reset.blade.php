@extends('layouts/edit-form', [
    'createText' => 'Replenish',
    'updateText' => 'Replenish',
    'httpMethod' => 'POST',
    'topSubmit' => true,
    'helpText' => trans('help.consumables'),
    'helpPosition' => 'right',
    'formAction' => route('consumables.reset.store', $item->id),
    'index_route' => 'consumables.index'
])

@section('inputFields')
    <div class="form-group">
        <div class="col-md-7 col-sm-12 col-md-offset-3">
            <p class="help-block">Press save to replenish remaining to total quantity.</p>
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-3 control-label">General Note</label>
        <div class="col-md-7 col-sm-12">
            <textarea name="note" class="form-control" rows="4"></textarea>
        </div>
    </div>

@endsection
