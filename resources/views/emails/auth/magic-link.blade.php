@extends('emails.layouts.app')

@section('content')
<div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px;">
    <h2 style="color: #333;">Link de acesso à sua conta</h2>
    
    <p>Você solicitou um link para acessar sua conta. Clique no botão abaixo para entrar:</p>
    
    <div style="margin: 30px 0;">
        <a href="{{ $magicLink }}" 
           style="background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
            Entrar na minha conta
        </a>
    </div>
    
    <p>Este link expira em {{ $expiration }}.</p>
    
    <p>Se você não solicitou este link, ignore este e-mail.</p>
    
    <p style="margin-top: 30px; font-size: 12px; color: #666;">
        Se o botão acima não funcionar, copie e cole o link a seguir no seu navegador:<br>
        <span style="color: #0066cc;">{{ $magicLink }}</span>
    </p>
</div>
@endsection
