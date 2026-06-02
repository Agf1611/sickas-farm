<x-filament-widgets::widget>
    @php
        $warnings = $this->warnings();
        $attentionBatches = $this->attentionBatches();
        $activities = $this->recentActivities();
    @endphp

    <div class="sickas-ops-grid">
        <section class="sickas-panel">
            <div class="sickas-section-title">
                <h2>Peringatan Penting</h2>
                <p>Hal yang perlu dicek lebih dulu.</p>
            </div>

            <div class="sickas-warning-list">
                @foreach ($warnings as $warning)
                    <div class="sickas-warning-item">
                        <div>
                            <p>{{ $warning['label'] }}</p>
                            <span>{{ $warning['description'] }}</span>
                        </div>
                        <strong class="sickas-badge sickas-badge-{{ $warning['tone'] }}">
                            {{ $warning['value'] }}
                        </strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sickas-panel sickas-attention-panel">
            <div class="sickas-section-title">
                <h2>Batch Perlu Perhatian</h2>
                <p>Prioritas berdasarkan ADG, timbang ulang, dan status pertumbuhan.</p>
            </div>

            @if ($attentionBatches)
                <div class="sickas-table-wrap">
                    <table class="sickas-dashboard-table">
                        <thead>
                            <tr>
                                <th>Kode Batch</th>
                                <th>Jenis</th>
                                <th>Kandang</th>
                                <th class="sickas-text-right">Jumlah</th>
                                <th class="sickas-text-right">ADG</th>
                                <th>Status</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attentionBatches as $row)
                                <tr>
                                    <td class="sickas-strong">{{ $row['batch_code'] }}</td>
                                    <td>{{ $row['livestock_type_name'] }}</td>
                                    <td>{{ $row['pen_name'] }}</td>
                                    <td class="sickas-text-right">{{ $row['head_count'] }} ekor</td>
                                    <td class="sickas-text-right">{{ $this->formatAdg($row['adg']) }}</td>
                                    <td>
                                        <x-filament::badge :color="$this->statusColor($row['status'])">
                                            {{ $row['status'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="sickas-muted">{{ $row['recommendation_short'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="sickas-empty-dark">
                    Belum ada batch aktif yang perlu perhatian khusus.
                </div>
            @endif
        </section>

        <section class="sickas-panel">
            <div class="sickas-section-title">
                <h2>Aktivitas Terbaru</h2>
                <p>Ringkasan input terakhir dari modul utama.</p>
            </div>

            @if ($activities)
                <div class="sickas-activity-list">
                    @foreach ($activities as $activity)
                        <div class="sickas-activity-item">
                            <div>
                                <span class="sickas-badge sickas-badge-{{ $activity['tone'] }}">
                                        {{ $activity['type'] }}
                                    </span>
                                <p>{{ $activity['title'] }}</p>
                                <small>{{ $activity['description'] }}</small>
                            </div>
                            <time>{{ $activity['date_label'] }}</time>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="sickas-empty-dark">
                    Belum ada aktivitas yang tercatat.
                </div>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
