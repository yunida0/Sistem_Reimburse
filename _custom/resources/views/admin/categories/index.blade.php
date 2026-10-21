@extends('layouts.app')
@section('title', 'Kategori')
@section('page-title', 'Kategori')
@section('content')
<div class="py-4 space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('admin.categories.create') }}" class="btn-primary">+ Tambah Kategori</a>
    </div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Wajib Bukti</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($categories as $cat)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $cat->name }}</td>
                        <td class="px-5 py-3 font-mono text-slate-600">{{ $cat->code }}</td>
                        <td class="px-5 py-3">
                            @if($cat->requires_receipt)
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Ya</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Tidak</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($cat->is_active)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Aktif</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3"><a href="{{ route('admin.categories.edit', $cat) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">{{ $categories->links() }}</div>
    </x-card>
</div>
@endsection
