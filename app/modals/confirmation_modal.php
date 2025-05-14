<?php
/**
 * Modal para confirmação de ações
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
 * Classe para gerenciar modais de confirmação usando Bootstrap 5.3
 */
class ConfirmationModal {
    
    /**
     * Exibe um modal de confirmação
     * 
     * @param string $message Mensagem de confirmação
     * @param string $title Título do modal
     * @param array $options Opções adicionais para configurar o modal
     * @return string HTML do modal
     */
    public static function render($message, $title = 'Confirmação', $options = []) {
        // Configurações padrão
        $defaultOptions = [
            'size' => 'md', // sm, md, lg, xl
            'dismissable' => true,
            'backdrop' => 'static', // true, false, 'static'
            'buttons' => [
                [
                    'text' => 'Cancelar',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Confirmar',
                    'class' => 'btn-primary confirm-btn',
                    'dismiss' => true
                ]
            ],
            'id' => 'confirmationModal_' . uniqid(),
            'show' => false, // Se deve ser exibido automaticamente
            'headerClass' => 'bg-primary text-white',
            'confirmUrl' => null, // URL para redirecionar ou enviar após confirmação
            'confirmMethod' => 'GET', // Método HTTP para envio (GET ou POST)
            'confirmData' => null, // Dados adicionais para envio via POST
            'confirmCallback' => null, // Nome da função JavaScript para chamar após confirmação
            'callbackData' => null, // Dados a serem passados para a função de callback
            'dangerMode' => false, // Se a ação é perigosa (altera cores)
        ];
        
        // Mescla opções fornecidas com as padrão
        $options = array_merge($defaultOptions, $options);
        
        // Ajusta classes se estiver em modo de perigo
        if ($options['dangerMode']) {
            $options['headerClass'] = 'bg-danger text-white';
            $options['buttons'][1]['class'] = 'btn-danger confirm-btn';
        }
        
        // HTML do modal
        $html = '
        <div class="modal fade" id="' . $options['id'] . '" tabindex="-1" aria-labelledby="' . $options['id'] . 'Label" aria-hidden="true" data-bs-backdrop="' . $options['backdrop'] . '" data-bs-keyboard="false">
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
        
        if ($options['dangerMode']) {
            $html .= '
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Atenção: Esta ação não pode ser desfeita.
                        </div>';
        }
        
        $html .= '
                    </div>';
        
        // Adiciona botões ao rodapé
        $html .= '
                    <div class="modal-footer">';
        
        foreach ($options['buttons'] as $button) {
            $dismissAttr = isset($button['dismiss']) && $button['dismiss'] ? ' data-bs-dismiss="modal"' : '';
            $isConfirmBtn = isset($button['class']) && strpos($button['class'], 'confirm-btn') !== false;
            $confirmAttr = $isConfirmBtn ? ' id="' . $options['id'] . '_confirm"' : '';
            
            $html .= '
                        <button type="button" class="btn ' . $button['class'] . '"' . $dismissAttr . $confirmAttr . '>' . 
                            htmlspecialchars($button['text']) . 
                        '</button>';
        }
        
        $html .= '
                    </div>
                </div>
            </div>
        </div>';
        
        // Script para inicializar o modal e gerenciar ações
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Inicializa o modal
                var confirmationModal = new bootstrap.Modal(document.getElementById("' . $options['id'] . '"));
                
                // Botão de confirmação
                const confirmButton = document.getElementById("' . $options['id'] . '_confirm");
                if (confirmButton) {
                    confirmButton.addEventListener("click", function() {';
        
        // Adiciona ação de confirmação baseada nas opções
        if ($options['confirmUrl']) {
            if ($options['confirmMethod'] === 'POST') {
                $html .= '
                        // Criar um formulário para envio via POST
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = "' . $options['confirmUrl'] . '";
                        form.style.display = "none";
                        
                        // Adicionar token CSRF se disponível
                        if (typeof csrfToken !== "undefined") {
                            const csrfInput = document.createElement("input");
                            csrfInput.type = "hidden";
                            csrfInput.name = "csrf_token";
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }';
                
                // Adiciona dados extras se fornecidos
                if ($options['confirmData'] && is_array($options['confirmData'])) {
                    foreach ($options['confirmData'] as $key => $value) {
                        $html .= '
                        const input_' . $key . ' = document.createElement("input");
                        input_' . $key . '.type = "hidden";
                        input_' . $key . '.name = "' . $key . '";
                        input_' . $key . '.value = "' . $value . '";
                        form.appendChild(input_' . $key . ');';
                    }
                }
                
                $html .= '
                        document.body.appendChild(form);
                        form.submit();';
            } else {
                // Método GET
                $html .= '
                        window.location.href = "' . $options['confirmUrl'] . '";';
            }
        } elseif ($options['confirmCallback']) {
            // Chama uma função JavaScript
            $callbackData = $options['callbackData'] ? json_encode($options['callbackData']) : 'null';
            $html .= '
                        if (typeof ' . $options['confirmCallback'] . ' === "function") {
                            ' . $options['confirmCallback'] . '(' . $callbackData . ');
                        } else {
                            console.error("Função de callback não encontrada: ' . $options['confirmCallback'] . '");
                        }';
        }
        
        $html .= '
                    });
                }
                
                // Exibir o modal automaticamente se necessário
                if (' . ($options['show'] ? 'true' : 'false') . ') {
                    confirmationModal.show();
                }
                
                // Função global para exibir este modal
                window.show' . ucfirst(str_replace('_', '', $options['id'])) . ' = function() {
                    confirmationModal.show();
                };
            });
        </script>';
        
        return $html;
    }
    
    /**
     * Cria um botão que quando clicado abre um modal de confirmação
     * 
     * @param string $buttonText Texto do botão
     * @param string $message Mensagem de confirmação
     * @param array $buttonOptions Opções do botão
     * @param array $modalOptions Opções do modal
     * @return string HTML do botão e do modal
     */
    public static function renderWithButton($buttonText, $message, $buttonOptions = [], $modalOptions = []) {
        // Configurações padrão do botão
        $defaultButtonOptions = [
            'class' => 'btn-primary',
            'icon' => null, // Classe do ícone (Bootstrap Icons)
            'id' => 'btn_' . uniqid(),
            'attributes' => [] // Atributos HTML adicionais
        ];
        
        $buttonOptions = array_merge($defaultButtonOptions, $buttonOptions);
        
        // Configura ID do modal baseado no ID do botão
        if (!isset($modalOptions['id'])) {
            $modalOptions['id'] = 'confirmationModal_' . substr($buttonOptions['id'], 4);
        }
        
        // Gera atributos HTML adicionais
        $attributes = '';
        foreach ($buttonOptions['attributes'] as $attr => $value) {
            $attributes .= ' ' . $attr . '="' . htmlspecialchars($value) . '"';
        }
        
        // HTML do botão
        $html = '
        <button type="button" class="btn ' . $buttonOptions['class'] . '" id="' . $buttonOptions['id'] . '"' . $attributes . '>';
        
        if ($buttonOptions['icon']) {
            $html .= '<i class="bi bi-' . $buttonOptions['icon'] . ' me-1"></i> ';
        }
        
        $html .= htmlspecialchars($buttonText) . '</button>';
        
        // Adiciona o modal
        $html .= self::render($message, isset($modalOptions['title']) ? $modalOptions['title'] : 'Confirmação', $modalOptions);
        
        // Script para conectar o botão ao modal
        $html .= '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const button = document.getElementById("' . $buttonOptions['id'] . '");
                const modal = new bootstrap.Modal(document.getElementById("' . $modalOptions['id'] . '"));
                
                if (button) {
                    button.addEventListener("click", function() {
                        modal.show();
                    });
                }
            });
        </script>';
        
        return $html;
    }
    
    /**
     * Cria um modal de confirmação para exclusão
     * 
     * @param string $entity Nome da entidade a ser excluída
     * @param string $redirectUrl URL para redirecionar após confirmação
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderDelete($entity, $redirectUrl, $options = []) {
        $message = 'Tem certeza de que deseja excluir este(a) ' . $entity . '?';
        
        $defaultOptions = [
            'title' => 'Confirmar Exclusão',
            'dangerMode' => true,
            'confirmUrl' => $redirectUrl,
            'buttons' => [
                [
                    'text' => 'Cancelar',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Excluir',
                    'class' => 'btn-danger confirm-btn',
                    'dismiss' => true
                ]
            ],
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        return self::render($message, $options['title'], $options);
    }
    
    /**
     * Cria um modal de confirmação para sair sem salvar
     * 
     * @param string $redirectUrl URL para redirecionar após confirmação
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderUnsavedChanges($redirectUrl, $options = []) {
        $message = 'Existem alterações não salvas. Se continuar, todas as alterações serão perdidas.';
        
        $defaultOptions = [
            'title' => 'Alterações Não Salvas',
            'dangerMode' => true,
            'confirmUrl' => $redirectUrl,
            'buttons' => [
                [
                    'text' => 'Cancelar',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Sair sem Salvar',
                    'class' => 'btn-danger confirm-btn',
                    'dismiss' => true
                ]
            ],
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        return self::render($message, $options['title'], $options);
    }
    
    /**
     * Cria um modal de confirmação para logout
     * 
     * @param string $redirectUrl URL para redirecionar após confirmação (opcional)
     * @param array $options Opções adicionais
     * @return string HTML do modal
     */
    public static function renderLogout($redirectUrl = null, $options = []) {
        if ($redirectUrl === null) {
            $redirectUrl = BASE_URL . '/app/controllers/auth_controller.php?action=logout';
        }
        
        $message = 'Tem certeza de que deseja sair do sistema?';
        
        $defaultOptions = [
            'title' => 'Confirmar Logout',
            'dangerMode' => false,
            'confirmUrl' => $redirectUrl,
            'buttons' => [
                [
                    'text' => 'Cancelar',
                    'class' => 'btn-secondary',
                    'dismiss' => true
                ],
                [
                    'text' => 'Sair',
                    'class' => 'btn-primary confirm-btn',
                    'dismiss' => true
                ]
            ],
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        return self::render($message, $options['title'], $options);
    }
}