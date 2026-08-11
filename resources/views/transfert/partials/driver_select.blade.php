<select class="form-control form-control-sm driver-select" data-id="{{ $row->id }}">
    @foreach($drivers as $id => $name)
        <option value="{{ $id }}" {{ $row->driver_id == $id ? 'selected' : '' }}>{{ $name }}</option>
    @endforeach
</select>
