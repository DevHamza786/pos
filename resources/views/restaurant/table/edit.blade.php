<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('Restaurant\TableController@update', [$table->id]),
            'method' => 'PUT',
            'id' => 'table_edit_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('restaurant.edit_table')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('restaurant.table_name') . ':*') !!}
                {!! Form::text('name', $table->name, [
                    'class' => 'form-control',
                    'required',
                    'placeholder' => __('brand.brand_name'),
                ]) !!}
            </div>
            <div class="form-group">
                {!! Form::label('charges', 'Room Charges' . ':*') !!}
                {!! Form::number('charges', $table->charges, [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0.01',
                    'required',
                    'placeholder' => 'Room Charges',
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('description', __('restaurant.short_description') . ':') !!}
                {!! Form::text('description', $table->description, [
                    'class' => 'form-control',
                    'placeholder' => __('brand.short_description'),
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('pictures', 'Room Pictures') !!}
                {!! Form::file('pictures[]', [
                    'class' => 'form-control',
                    'multiple' => 'multiple',
                    'placeholder' => 'Room Pictures',
                ]) !!}
                @if ($table->tablePicture->count() > 0)
                    <div class="slider">
                        @foreach ($table->tablePicture as $picture)
                            <div>
                                <img src="{{ asset('/uploads/' . $picture->path) }}" alt="{{ $picture->name }}" height="200">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
