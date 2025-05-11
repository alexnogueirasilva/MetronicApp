@extends('emails.layouts.app')

@section('title', 'Recuperação de Senha')

@section('heading')
    <div style="text-align: center; margin-bottom: 1rem;">
        <img src="{{ url(asset('img/logo/DevAction.png')) }}"
             alt="DevAction Logo"
             style="height: 48px;">
    </div>
    <div style="text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 1rem;">
        Recupere sua senha
    </div>
@endsection

@section('content')
    <p style="color: #cbd5e1; font-size: 14px; line-height: 1.5;">
        Olá, recebemos uma solicitação para redefinir sua senha.
    </p>

    <p style="color: #94a3b8; font-size: 14px; line-height: 1.5; margin-bottom: 2rem;">
        Clique no botão abaixo para escolher uma nova senha. Este link é válido por tempo limitado.
    </p>

    <div style="text-align: center; margin-bottom: 2rem;">
        <a href="{{ $url }}"
           style="display: inline-block; background-color: #3b82f6; color: white; padding: 12px 24px;
           border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px;">
            Redefinir Senha
        </a>
    </div>

    <p style="color: #64748b; font-size: 12px;">
        Se você não solicitou essa alteração, nenhuma ação é necessária.
    </p>
@endsection
