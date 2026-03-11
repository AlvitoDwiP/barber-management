@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => 'Belum ada data laporan.',
])

<div class="admin-card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td class="px-4 py-3 text-slate-700">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($headers), 1) }}" class="px-4 py-6 text-center text-slate-500">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

