@php
    $bpIssues = $bottomPriceEvaluation['issues'] ?? [];
    $bpRequiredLevel = $bottomPriceEvaluation['required_level'] ?? null;
    $bpLineIssues = collect($bpIssues)->filter(fn ($issue) => ($issue['type'] ?? null) !== 'missing_details');

    // Bottom price / discount figures are approval-sensitive pricing data.
    // Only users who can approve quotations at some level (not plain
    // Marketing staff) may see this breakdown.
    $bpCanViewApprovalDetail = auth()->user()?->canApprove('quotations') ?? false;
@endphp

@if($bpCanViewApprovalDetail && !empty($bottomPriceEvaluation['requires_approval']) && $quotation->status === 'waiting_for_approval')
<div class="card mb-3" style="border-left: 4px solid #f59e0b;">
    <div class="card-body">
        <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <i class="fas fa-exclamation-triangle" style="color:#f59e0b;font-size:1.4rem;margin-top:2px;"></i>
            <div style="flex:1;min-width:260px;">
                <h6 style="margin:0 0 4px;font-weight:600;color:#92400e;">
                    @if($bpRequiredLevel)
                        Butuh approval level {{ $bpRequiredLevel['level_name'] }}
                    @else
                        Butuh approval
                    @endif
                </h6>
                <div style="font-size:13px;color:#6b7280;">
                    @if($bpRequiredLevel)
                        Ada harga di bawah <em>bottom price</em>. Level {{ $bpRequiredLevel['level_name'] }}
                        boleh menyetujui diskon sampai
                        {{ rtrim(rtrim(number_format($bpRequiredLevel['max_discount_percentage'], 2, ',', '.'), '0'), ',') }}%.
                    @else
                        Tidak ada level approval yang berwenang atas diskon sebesar ini.
                        Hubungi admin untuk mengatur <strong>Master Price Slab</strong> (Marketing).
                    @endif
                </div>
                @if(!$canApproveQuotation)
                    <div style="margin-top:8px;font-size:13px;color:#b45309;">
                        <i class="fas fa-lock"></i>
                        Level Anda
                        @if($currentUserApprovalLevel)
                            ({{ $currentUserApprovalLevel->level_name }} &mdash; maks
                            {{ rtrim(rtrim(number_format((float) $currentUserApprovalLevel->max_discount_percentage, 2, ',', '.'), '0'), ',') }}%)
                        @endif
                        belum mencukupi untuk menyetujui quotation ini.
                    </div>
                @endif
            </div>
        </div>

        @if($bpLineIssues->isNotEmpty())
        <div style="overflow-x:auto;margin-top:14px;">
            <table class="table table-sm" style="margin:0;font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th>Ruang</th>
                        <th>Rental</th>
                        <th class="text-end">Harga Quotation</th>
                        <th class="text-end">Bottom Price</th>
                        <th class="text-end">Diskon</th>
                        <th>Butuh Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bpLineIssues as $issue)
                        <tr>
                            <td>{{ $issue['room_name'] ?? '-' }}</td>
                            <td>{{ $issue['rental_name'] ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format((float) ($issue['unit_price'] ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if(!empty($issue['bottom_price']))
                                    Rp {{ number_format((float) $issue['bottom_price'], 0, ',', '.') }}
                                @else
                                    <span class="text-muted">belum diatur</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(isset($issue['discount_percentage']) && $issue['discount_percentage'] !== null)
                                    <strong style="color:#b91c1c;">
                                        {{ rtrim(rtrim(number_format((float) $issue['discount_percentage'], 2, ',', '.'), '0'), ',') }}%
                                    </strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($issue['required_level_name']))
                                    <span class="badge bg-warning text-dark">{{ $issue['required_level_name'] }}</span>
                                @else
                                    <span class="badge bg-secondary">tidak tercakup</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif
