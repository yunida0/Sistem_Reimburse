@extends('layouts.app')

@section('title', 'Buat Pengajuan')
@section('page-title', 'Buat Pengajuan Baru')

@section('content')
@include('reimbursements._form', ['categories' => $categories])
@endsection
