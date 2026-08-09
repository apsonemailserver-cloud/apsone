@extends('layout.admin')

@section('title', 'Tambah Dokumen')

@section('styles')
    @include('document.admin.styles')
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y document-admin-page">
        <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
            @include('document.admin._form', [
                'submitLabel' => 'Simpan Dokumen',
            ])
        </form>
    </div>
@endsection

@section('scripts')
    @include('document.admin.form-scripts')
@endsection
