<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
            <select class="form-control select2" id="edit_customer_id" name="customer_id" required>
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ $customerContact->customer_id == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_name" name="name" value="{{ $customerContact->name }}" required>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_position" class="form-label">Position</label>
            <input type="text" class="form-control" id="edit_position" name="position" value="{{ $customerContact->position }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="edit_email" name="email" value="{{ $customerContact->email }}">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="edit_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="edit_phone" name="phone" value="{{ $customerContact->phone }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1" {{ $customerContact->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="edit_is_active">
                    Active
                </label>
            </div>
        </div>
    </div>
</div>
