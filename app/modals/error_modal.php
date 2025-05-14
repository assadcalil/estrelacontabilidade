<?php
/**
 * Modal para exibição de erros do sistema
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
 * Classe para gerenciar modais de erro usando Bootstrap 5.3
 */
class ErrorModal {
    
    /**
     * Exibe um modal de erro com uma mensagem
     * 
     * @param string $message Mensagem de erro
     * @param string $title Título do modal
     * @param array $options Opções adicionais para configurar o modal
     * @return string HTML do modal
     */
    public static function render($message, $title = 'Erro', $options = []) {
        // Configurações padrão
        $defaultOptions = [
            'size' => 'md', // sm, md, lg, xl
            'dismissable' => true,
            'backdrop' => 'static', // true, false, 'static'
            'buttons' => [
                [
                    'text' => 'Fechar',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ]
            ],
            'id' => 'errorModal_' . uniqid(),
            'show' => true, // Se deve ser exibido automaticamente
            'headerClass' => 'bg-danger text-white',
            'details' => null // Detalhes técnicos adicionais
        ];
        
        // Mescla opções fornecidas com as padrão
        $options = array_merge($defaultOptions, $options);
        
        // HTML do modal
        $html = '
        <div class="modal fade' . ($options['show'] ? ' show d-block' : '') . '" id="' . $options['id'] . '" tabindex="-1" aria-labelledby="' . $options['id'] . 'Label" aria-hidden="' . (!$options['show'] ? 'true' : 'false') . '" data-bs-backdrop="' . $options['backdrop'] . '" data-bs-keyboard="false">
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
                        <p>' . nl2br(htmlspecialchars($message)) . '</p>';
        
        // Adiciona detalhes técnicos se fornecidos
        if ($options['details']) {
            $html .= '
                        <div class="alert alert-secondary mt-3 small">
                            <strong>Detalhes técnicos:</strong><br>
                            ' . nl2br(htmlspecialchars($options['details'])) . '
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
        if ($options['show']) {
            $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var errorModal = new bootstrap.Modal(document.getElementById("' . $options['id'] . '"));
                
                // Remover backdrop manualmente ao fechar o modal
                document.getElementById("' . $options['id'] . '").addEventListener("hidden.bs.modal", function() {
                    const backdrop = document.querySelector(".modal-backdrop");
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                    document.body.classList.remove("modal-open");
                    document.body.style.overflow = "";
                    document.body.style.paddingRight = "";
                });
            });
        </script>';
        }
        
        return $html;
    }
    
    /**
     * Exibe um modal de erro para uma exceção
     * 
     * @param Exception $exception Exceção a ser exibida
     * @param bool $showDetails Se deve exibir detalhes técnicos
     * @param array $options Opções adicionais para configurar o modal
     * @return string HTML do modal
     */
    public static function renderException($exception, $showDetails = false, $options = []) {
        $message = $exception->getMessage();
        
        $details = null;
        if ($showDetails) {
            $details = 'Arquivo: ' . $exception->getFile() . ' (linha ' . $exception->getLine() . ')' . "\n\n";
            $details .= 'Stack Trace:' . "\n" . $exception->getTraceAsString();
        }
        
        $options['details'] = $details;
        
        return self::render($message, 'Erro no Sistema', $options);
    }
    
    /**
     * Exibe um modal de erro para acesso não autorizado
     * 
     * @param string $message Mensagem personalizada (opcional)
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderUnauthorized($message = null, $options = []) {
        if ($message === null) {
            $message = 'Você não tem permissão para acessar este recurso.';
        }
        
        $defaultOptions = [
            'headerClass' => 'bg-warning text-dark',
            'buttons' => [
                [
                    'text' => 'Voltar',
                    'class' => 'btn-primary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Ir para Login',
                    'class' => 'btn-secondary',
                    'dismiss' => false
                ]
            ]
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $html = self::render($message, 'Acesso Restrito', $options);
        
        // Adiciona script para botão de login
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const buttons = document.querySelector("#' . $options['id'] . ' .modal-footer").querySelectorAll("button");
                if (buttons.length > 1) {
                    buttons[1].addEventListener("click", function() {
                        window.location.href = "' . BASE_URL . '/public/login.php";
                    });
                }
            });
        </script>';
        
        return $html;
    }
    
    /**
     * Exibe um modal de sessão expirada
     * 
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderSessionExpired($options = []) {
        $message = 'Sua sessão expirou por inatividade. Por favor, faça login novamente para continuar.';
        
        $defaultOptions = [
            'headerClass' => 'bg-info text-white',
            'dismissable' => false,
            'buttons' => [
                [
                    'text' => 'Fazer Login',
                    'class' => 'btn-primary',
                    'dismiss' => true
                ]
            ]
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $html = self::render($message, 'Sessão Expirada', $options);
        
        // Adiciona script para redirecionar para a página de login
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const buttons = document.querySelector("#' . $options['id'] . ' .modal-footer").querySelectorAll("button");
                if (buttons.length > 0) {
                    buttons[0].addEventListener("click", function() {
                        window.location.href = "' . BASE_URL . '/public/login.php";
                    });
                }
                
                // Redirecionar após 3 segundos
                setTimeout(function() {
                    window.location.href = "' . BASE_URL . '/public/login.php";
                }, 3000);
            });
        </script>';
        
        return $html;
    }
    
    /**
     * Exibe um modal de aviso de múltiplas sessões
     * 
     * @param string $message Mensagem personalizada
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderMultipleSession($message = null, $options = []) {
        if ($message === null) {
            $message = 'Sua conta está em uso em outro dispositivo ou navegador. Se não for você, recomendamos alterar sua senha.';
        }
        
        $defaultOptions = [
            'headerClass' => 'bg-warning text-dark',
            'dismissable' => false,
            'buttons' => [
                [
                    'text' => 'Continuar aqui',
                    'class' => 'btn-primary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Sair',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ]
            ]
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $html = self::render($message, 'Múltiplas Sessões Detectadas', $options);
        
        // Adiciona script para botões
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const buttons = document.querySelector("#' . $options['id'] . ' .modal-footer").querySelectorAll("button");
                if (buttons.length > 1) {
                    // Botão "Continuar aqui"
                    buttons[0].addEventListener("click", function() {
                        // Enviar solicitação para encerrar outras sessões
                        fetch("' . BASE_URL . '/app/controllers/session_controller.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "action=force_single_session&token=' . $_SESSION['token'] ?? '' . '"
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "success") {
                                window.location.reload();
                            } else {
                                alert("Erro ao processar a solicitação. Por favor, tente novamente.");
                            }
                        })
                        .catch(error => {
                            console.error("Erro:", error);
                        });
                    });
                    
                    // Botão "Sair"
                    buttons[1].addEventListener("click", function() {
                        window.location.href = "' . BASE_URL . '/app/controllers/auth_controller.php?action=logout";
                    });
                }
            });
        </script>';
        
        return $html;
    }
}