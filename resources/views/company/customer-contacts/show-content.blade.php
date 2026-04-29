<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-user mr-2"></i>
                    Contact Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Name:</strong></div>
                    <div class="col-sm-8">{{ $customerContact->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Position:</strong></div>
                    <div class="col-sm-8">{{ $customerContact->position ?: 'N/A' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Email:</strong></div>
                    <div class="col-sm-8">
                        @if($customerContact->email)
                            <a href="mailto:{{ $customerContact->email }}" class="text-primary">
                                <i class="fas fa-envelope mr-1"></i>{{ $customerContact->email }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Phone:</strong></div>
                    <div class="col-sm-8">
                        @if($customerContact->phone)
                            <a href="tel:{{ $customerContact->phone }}" class="text-success">
                                <i class="fas fa-phone mr-1"></i>{{ $customerContact->phone }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Status:</strong></div>
                    <div class="col-sm-8">
                        <span class="badge badge-{{ $customerContact->is_active ? 'success' : 'danger' }} badge-pill">
                            {{ $customerContact->is_active_text }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-building mr-2"></i>
                    Customer Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Customer:</strong></div>
                    <div class="col-sm-8">
                        <i class="fas fa-building text-primary mr-2"></i>
                        {{ $customerContact->customer->name }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Customer Type:</strong></div>
                    <div class="col-sm-8">{{ $customerContact->customer->customer_type ?: 'N/A' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Customer Email:</strong></div>
                    <div class="col-sm-8">
                        @if($customerContact->customer->email)
                            <a href="mailto:{{ $customerContact->customer->email }}" class="text-primary">
                                <i class="fas fa-envelope mr-1"></i>{{ $customerContact->customer->email }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong>Customer Phone:</strong></div>
                    <div class="col-sm-8">
                        @if($customerContact->customer->phone)
                            <a href="tel:{{ $customerContact->customer->phone }}" class="text-success">
                                <i class="fas fa-phone mr-1"></i>{{ $customerContact->customer->phone }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    System Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Created By:</strong></div>
                            <div class="col-sm-8">
                                <i class="fas fa-user-circle text-secondary mr-2"></i>
                                {{ $customerContact->createdBy->name ?? 'System' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Created At:</strong></div>
                            <div class="col-sm-8">{{ $customerContact->created_at->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Updated By:</strong></div>
                            <div class="col-sm-8">
                                <i class="fas fa-user-circle text-secondary mr-2"></i>
                                {{ $customerContact->updatedBy->name ?? 'System' }}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Updated At:</strong></div>
                            <div class="col-sm-8">{{ $customerContact->updated_at->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
