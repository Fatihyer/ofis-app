<select class="form-control form-control-sm vehicule-select" data-id="{{ $row->id }}">
    @foreach($vehicules as $id => $name)
        <option value="{{ $id }}" {{ $row->vehicule_id == $id ? 'selected' : '' }}>{{ $name }}</option>
    @endforeach
</select>
