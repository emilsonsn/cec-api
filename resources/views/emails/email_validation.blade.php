<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valide seu email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #0044cc;
            font-size: 24px;
        }
        p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        a.linkBtn {
            display: inline-block;
            background-color: #0044cc;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        a.linkBtn:hover {
            background-color: #003399;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <h1>Validação de email</h1>
        <p>Olá, seja bem vindo ao {{ env('APP_NAME') }}</p>
        <p>Acesse essa link para validarmos seu email:</p>
        <p><a class="linkBtn" href="{{env('APP_URL') . '/api/user/email_validate/' . $code }}">Validar meu email</a></p>
        <p>Se você não se cadastrou em nossa plataforma, por favor, ignore este e-mail.</p>
        <div class="footer">
            <p>© {{ date('Y') }} {{ env('APP_NAME') }} . Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
