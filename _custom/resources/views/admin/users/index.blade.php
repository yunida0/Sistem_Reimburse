@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('page-title', 'Pengguna')

@section('content')
<div class="py-4 space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Tambah Pengguna</a>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Peran</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departemen</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Atasan</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                        <td class="px-5 py-3">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                                {{ $u->role->label() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $u->department?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $u->manager?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if($u->is_active)
                                <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">Aktif</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.users.edit', $u) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </x-card>
</div>
@endsection
