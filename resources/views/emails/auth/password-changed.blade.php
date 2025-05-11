@extends('emails.layouts.app')

@section('title', 'Senha alterada com sucesso')

@section('heading')
    <div class="logo text-center">
        <img src="{{ asset('img/logo/DevAction.png') }}" alt="DevAction Logo">
    </div>
    <div class="text-center font-semibold text-xl text-white">
        Sua senha foi alterada
    </div>
@endsection

@section('content')
    <p class="text-slate-300 mb-4">
        Informamos que sua senha foi alterada com sucesso. Caso essa ação não tenha sido realizada por você,
        recomendamos que tome providências imediatas.
    </p>

    <ul class="text-slate-400 text-sm mb-6 space-y-1">
        <li>🌐 <strong>IP de origem:</strong> {{ $ip }}</li>
        <li>📍 <strong>Localização:</strong> {{ $city }}, {{ $country }}</li>
        <li>💻 <strong>Dispositivo:</strong> {{ $userAgent }}</li>
    </ul>

    <p class="text-slate-500 text-xs">
        Este e-mail é apenas informativo. Nenhuma ação adicional é necessária se você reconhece esta alteração.
    </p>
@endsection

