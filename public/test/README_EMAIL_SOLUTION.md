# Solução de E-mail para GED ESTRELA

Este pacote contém uma solução para corrigir problemas de envio de e-mails no sistema GED ESTRELA.

## Arquivos Instalados

1. **PHPMailer**: `vendor/phpmailer/phpmailer`
   - Biblioteca PHP para envio de e-mails via SMTP
   - Versão 6.8.1 ou superior

2. **Classe Mailer Otimizada**: `app/classes/Mailer.php`
   - Classe PHP com métodos para envio de e-mail com tratamento específico de erros
   - Suporte para debug e log de erros
   - Método específico para envio de e-mails de recuperação de senha

3. **Página de Teste de E-mail**: `public/test/test_email.php`
   - Interface web para testar o envio de e-mails
   - Mostra logs detalhados de debug do SMTP
   - Permite diagnosticar problemas de configuração

4. **Template de E-mail**: `app/templates/emails/password_recovery.html`
   - Template HTML responsivo para e-mails de recuperação de senha
   - Layout moderno e compatível com a maioria dos clientes de e-mail

5. **Configurações**: `app/config/constants.php`
   - Configurações SMTP do Gmail para a conta recuperacaoestrela@gmail.com

## Como Usar

1. **Para testar se o sistema está funcionando corretamente:**
   - Acesse a URL: `http://seu-site.com/public/test/test_email.php`
   - Digite seu e-mail e clique em "Enviar E-mail Agora"
   - Se tudo estiver funcionando, você receberá um e-mail de teste

2. **Para usar a classe Mailer em seu código:**
   ```php
   require_once BASE_PATH . '/app/classes/Mailer.php';
   
   $mailer = new Mailer();
   $mailer->enableDebug(true); // Ativa o modo de depuração
   
   // Enviar e-mail simples
   $mailer->send(
       'destinatario@example.com',
       'Assunto do E-mail',
       '<p>Conteúdo HTML do e-mail</p>'
   );
   
   // Enviar e-mail de recuperação de senha
   $mailer->sendPasswordRecovery(
       'usuario@example.com',
       'Nome do Usuário',
       'token-de-recuperacao-123'
   );
   ```

## Configurações SMTP Atuais

- **Servidor SMTP**: smtp.gmail.com
- **Porta**: 587 (TLS)
- **Usuário**: recuperacaoestrela@gmail.com
- **Senha**: sgyrmsgdaxiqvupb (Senha de aplicativo gerada no Google)

## Solução de Problemas

Se você encontrar problemas no envio de e-mails:

1. Verifique se o PHPMailer está instalado corretamente
2. Certifique-se de que o servidor tem acesso à internet e às portas SMTP (587 ou 465)
3. Verifique se a senha do aplicativo no Gmail ainda é válida
4. Consulte os logs de erro em `app/logs/email_debug.log`

## Suporte

Para problemas com esta solução, contate o suporte técnico.