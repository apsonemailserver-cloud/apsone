@if(isset($flight))
<div class="modal fade" id="viewFlightModal{{ $flight->id }}" tabindex="-1" aria-labelledby="viewFlightModalLabel{{ $flight->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewFlightModalLabel{{ $flight->id }}">Flight Detail - {{ $flight->flight_number ?? ($flight->ex_flight . ($flight->to_flight ? ' / ' . $flight->to_flight : '')) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Airline/Station:</strong> {{ $flight->airline ?? ($flight->station ?? '-') }}</p>
                <p><strong>Registration:</strong> {{ $flight->registasi ?? ($flight->aircraft_reg ?? '-') }}</p>
                <p><strong>Type:</strong> {{ $flight->type ?? '-' }}</p>
                <p><strong>Arrival/Time:</strong> {{ $flight->arrival ?? ($flight->start_time ?? '-') }}</p>
                <hr>
                <h6>Pengerjaan Flight</h6>
                <ol type="1" style="margin-left: 1rem;">
                    @if(!empty($flight->details) && (is_array($flight->details) || is_object($flight->details)))
                        @forelse($flight->details as $detail)
                        <li>
                            NIP: {{ $detail->schedule->user->id ?? 'N/A' }} |
                            Nama: {{ $detail->schedule->user->fullname ?? 'N/A' }} |
                            Qantas: {{ isset($detail->schedule->user->is_qantas) && $detail->schedule->user->is_qantas == 1 ? 'Iya' : 'Tidak' }}
                        </li>
                        @empty
                        <li>No flight details available.</li>
                        @endforelse
                    @elseif(!empty($flight->users))
                        @forelse($flight->users as $user)
                        <li>
                            NIP: {{ $user->id ?? 'N/A' }} |
                            Nama: {{ $user->fullname ?? 'N/A' }} |
                            Qantas: {{ isset($user->is_qantas) && $user->is_qantas == 1 ? 'Iya' : 'Tidak' }}
                        </li>
                        @empty
                        <li>No staff assigned.</li>
                        @endforelse
                    @else
                        <li>No details available.</li>
                    @endif
                </ol>
            </div>
        </div>
    </div>
</div>
@endif
