<?php
/**
 * Modal para exibição de mensagens de sucesso
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

/**
 * Classe para gerenciar modais de sucesso usando Bootstrap 5.3
 */
class SuccessModal {
    
    /**
     * Exibe um modal de sucesso com uma mensagem
     * 
     * @param string $message Mensagem de sucesso
     * @param string $title Título do modal
     * @param array $options Opções adicionais para configurar o modal
     * @return string HTML do modal
     */
    public static function render($message, $title = 'Sucesso', $options = []) {
        // Configurações padrão
        $defaultOptions = [
            'size' => 'md', // sm, md, lg, xl
            'dismissable' => true,
            'backdrop' => true, // true, false, 'static'
            'buttons' => [
                [
                    'text' => 'OK',
                    'class' => 'btn-success',
                    'dismiss' => true
                ]
            ],
            'id' => 'successModal_' . uniqid(),
            'show' => true, // Se deve ser exibido automaticamente
            'headerClass' => 'bg-success text-white',
            'autoClose' => 3, // Tempo em segundos para fechar automaticamente (0 para desativar)
            'redirect' => null, // URL para redirecionar após fechar
            'reload' => false, // Se deve recarregar a página atual após fechar
        ];
        
        // Mescla opções fornecidas com as padrão
        $options = array_merge($defaultOptions, $options);
        
        // HTML do modal
        $html = '
        <div class="modal fade' . ($options['show'] ? ' show d-block' : '') . '" id="' . $options['id'] . '" tabindex="-1" aria-labelledby="' . $options['id'] . 'Label" aria-hidden="' . (!$options['show'] ? 'true' : 'false') . '" data-bs-backdrop="' . $options['backdrop'] . '">
            <div class="modal-dialog modal-' . $options['size'] . ' modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header ' . $options['headerClass'] . '">
                        <h5 class="modal-title" id="' . $options['id'] . 'Label">' . htmlspecialchars($title) . '</h5>';
        
        if ($options['dismissable']) {
            $html .= '
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>';
        }
        
        $html .= '
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <p class="text-center">' . nl2br(htmlspecialchars($message)) . '</p>';
        
        // Adiciona contador de tempo se autoClose estiver ativado
        if ($options['autoClose'] > 0) {
            $html .= '
                        <div class="text-center text-muted small mt-3">
                            Esta mensagem será fechada em <span id="' . $options['id'] . '_countdown">' . $options['autoClose'] . '</span> segundos.
                        </div>';
        }
        
        $html .= '
                    </div>';
        
        // Adiciona botões ao rodapé se houver
        if (!empty($options['buttons'])) {
            $html .= '
                    <div class="modal-footer">';
            
            foreach ($options['buttons'] as $button) {
                $dismissAttr = isset($button['dismiss']) && $button['dismiss'] ? ' data-bs-dismiss="modal"' : '';
                $html .= '
                        <button type="button" class="btn ' . $button['class'] . '"' . $dismissAttr . '>' . 
                            htmlspecialchars($button['text']) . 
                        '</button>';
            }
            
            $html .= '
                    </div>';
        }
        
        $html .= '
                </div>
            </div>
        </div>';
        
        // Adiciona o backdrop se o modal deve ser exibido
        if ($options['show']) {
            $html .= '
        <div class="modal-backdrop fade show"></div>';
        }
        
        // Script para inicializar o modal
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var successModal = new bootstrap.Modal(document.getElementById("' . $options['id'] . '"));
                
                // Remover backdrop manualmente ao fechar o modal
                document.getElementById("' . $options['id'] . '").addEventListener("hidden.bs.modal", function() {
                    const backdrop = document.querySelector(".modal-backdrop");
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                    document.body.classList.remove("modal-open");
                    document.body.style.overflow = "";
                    document.body.style.paddingRight = "";';
        
        // Adiciona redirecionamento ou recarga após fechar o modal
        if ($options['redirect']) {
            $html .= '
                    window.location.href = "' . $options['redirect'] . '";';
        } elseif ($options['reload']) {
            $html .= '
                    window.location.reload();';
        }
        
        $html .= '
                });';
        
        // Adiciona contagem regressiva se autoClose estiver ativado
        if ($options['autoClose'] > 0) {
            $html .= '
                // Iniciar contagem regressiva
                let countdown = ' . $options['autoClose'] . ';
                const countdownElement = document.getElementById("' . $options['id'] . '_countdown");
                
                const countdownInterval = setInterval(function() {
                    countdown--;
                    if (countdownElement) {
                        countdownElement.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        successModal.hide();
                    }
                }, 1000);';
        }
        
        $html .= '
            });
        </script>';
        
        return $html;
    }
    
    /**
     * Exibe um modal de sucesso para operação de criação
     * 
     * @param string $entity Nome da entidade criada
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderCreated($entity, $options = []) {
        $message = $entity . ' criado(a) com sucesso!';
        
        return self::render($message, 'Operação Concluída', $options);
    }
    
    /**
     * Exibe um modal de sucesso para operação de atualização
     * 
     * @param string $entity Nome da entidade atualizada
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderUpdated($entity, $options = []) {
        $message = $entity . ' atualizado(a) com sucesso!';
        
        return self::render($message, 'Operação Concluída', $options);
    }
    
    /**
     * Exibe um modal de sucesso para operação de exclusão
     * 
     * @param string $entity Nome da entidade excluída
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderDeleted($entity, $options = []) {
        $message = $entity . ' excluído(a) com sucesso!';
        
        return self::render($message, 'Operação Concluída', $options);
    }
    
    /**
     * Exibe um modal de sucesso para operação de envio de e-mail
     * 
     * @param string $recipient Destinatário do e-mail (opcional)
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderEmailSent($recipient = null, $options = []) {
        $message = 'E-mail enviado com sucesso!';
        
        if ($recipient) {
            $message = 'E-mail enviado com sucesso para ' . $recipient . '!';
        }
        
        return self::render($message, 'E-mail Enviado', $options);
    }
    
    /**
     * Exibe um modal de sucesso para operação genérica
     * 
     * @param string $operation Nome da operação concluída
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderOperation($operation, $options = []) {
        $message = 'Operação "' . $operation . '" concluída com sucesso!';
        
        return self::render($message, 'Operação Concluída', $options);
    }
}