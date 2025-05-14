<?php
/**
 * Rodapé padrão do sistema
 * 
 * @author Thiago Calil Assad
 * @created <?= date('Y-m-d') ?>
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}
?>
        </div>
        <!-- Fim do container-fluid -->
    </div>
    <!-- Fim do content -->
</div>
<!-- Fim do wrapper -->

<!-- Rodapé -->
<footer class="footer mt-auto py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">&copy; <?= date('Y') ?> - <?= SYSTEM_NAME ?> v<?= SYSTEM_VERSION ?></span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">Desenvolvido por Thiago Calil Assad</span>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

<!-- Script principal -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<!-- Scripts adicionais -->
<?php if (isset($extraJS)): ?>
    <?= $extraJS ?>
<?php endif; ?>

<script>
    // Inicialização de componentes e configurações globais
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Inicializar popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Auto-dismiss de alertas após 5 segundos
        var alertList = [].slice.call(document.querySelectorAll('.alert:not(.alert-permanent)'));
        alertList.forEach(function(alert) {
            setTimeout(function() {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
        
        // Validação de senhas no modal de alteração de senha
        var changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            var newPassword = document.getElementById('new_password');
            var confirmPassword = document.getElementById('confirm_password');
            var feedback = document.getElementById('passwordMatchFeedback');
            var submitButton = document.getElementById('changePasswordSubmit');
            
            // Verificar correspondência das senhas
            const checkPasswordMatch = function() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.classList.add('is-invalid');
                    feedback.style.display = 'block';
                    submitButton.disabled = true;
                    return false;
                } else {
                    confirmPassword.classList.remove('is-invalid');
                    feedback.style.display = 'none';
                    submitButton.disabled = false;
                    return true;
                }
            };
            
            // Adicionar validação nos eventos de input
            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
            
            // Validar no envio do formulário
            changePasswordForm.addEventListener('submit', function(event) {
                if (!checkPasswordMatch()) {
                    event.preventDefault();
                }
            });
        }
        
        // Toggle de visibilidade da senha
        var toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var passwordInput = document.getElementById(targetId);
                var icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Sidebar toggle 
        document.getElementById('sidebarCollapseBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('expanded');
            
            // Alterna o ícone
            const icon = this.querySelector('i');
            if (icon.classList.contains('bi-arrow-left-circle')) {
                icon.classList.remove('bi-arrow-left-circle');
                icon.classList.add('bi-arrow-right-circle');
            } else {
                icon.classList.remove('bi-arrow-right-circle');
                icon.classList.add('bi-arrow-left-circle');
            }
            
            // Salvar estado no localStorage
            localStorage.setItem('sidebar-collapsed', document.getElementById('sidebar').classList.contains('collapsed'));
        });
        
        // Recuperar estado do sidebar do localStorage
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('content').classList.add('expanded');
            const icon = document.getElementById('sidebarCollapseBtn').querySelector('i');
            icon.classList.remove('bi-arrow-left-circle');
            icon.classList.add('bi-arrow-right-circle');
        }
        
        // Menu mobile
        document.getElementById('sidebarToggleTop').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('mobile-show');
        });
    });
    
    // Função para trocar de empresa
    function switchCompany(companyId) {
        // Criar um formulário e enviar
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= BASE_URL ?>/app/controllers/auth_controller.php?action=switch_company';
        
        // Campo CSRF token
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $_SESSION['token'] ?? '' ?>';
        form.appendChild(csrfInput);
        
        // Campo company_id
        var companyInput = document.createElement('input');
        companyInput.type = 'hidden';
        companyInput.name = 'company_id';
        companyInput.value = companyId;
        form.appendChild(companyInput);
        
        // Adicionar ao body e enviar
        document.body.appendChild(form);
        form.submit();
    }
</script>
</body>
</html>