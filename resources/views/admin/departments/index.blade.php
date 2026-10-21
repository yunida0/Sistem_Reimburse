@extends('layouts.app')
@section('title', 'Departemen')
@section('page-title', 'Departemen')

@section('content')
<div class="py-4 space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('admin.departments.create') }}" class="btn-primary">+ Tambah Departemen</a>
    </div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pengguna</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($departments as $dept)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $dept->name }}</td>
                        <td class="px-5 py-3 font-mono text-slate-600">{{ $dept->code }}</td>
                        <td class="px-5 py-3 text-right text-slate-600">{{ $dept->users_count }}</td>
                        <td class="px-5 py-3">
                            @if($dept->is_active)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Aktif</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.departments.edit', $dept) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">{{ $departments->links() }}</div>
    </x-card>
</div>
@endsection
