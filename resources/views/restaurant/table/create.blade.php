<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('Restaurant\TableController@store'), 'method' => 'post', 'id' => 'table_add_form' , 'enctype' => 'multipart/form-data' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'restaurant.add_table' )</h4>
    </div>

    <div class="modal-body">

      @if(count($business_locations) == 1)
        @php 
            $default_location = current(array_keys($business_locations->toArray())) 
        @endphp
      @else
        @php $default_location = null; @endphp
      @endif
      <div class="form-group">
        {!! Form::label('location_id', __('purchase.business_location').':*') !!}
        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
      </div>
      
      <div class="form-group">
        {!! Form::label('name', __( 'restaurant.table_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'restaurant.table_name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('charges', 'Room Charges' . ':*') !!}
        {!! Form::number('charges', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required', 'placeholder' => 'Room Charges']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'restaurant.short_description' ) . ':') !!}
          {!! Form::text('description', null, ['class' => 'form-control','placeholder' => __( 'restaurant.short_description' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('pictures', 'Room Pictures') !!}
        {!! Form::file('pictures[]', ['class' => 'form-control', 'multiple' => 'multiple', 'placeholder' => 'Room Pictures']) !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-submit">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->