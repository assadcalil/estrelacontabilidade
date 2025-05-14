<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Contabilidade Estrela 2.0 - Cadastro de Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: white;
        }
        .logo {
            padding: 20px 0;
            text-align: center;
            border-bottom: 1px solid #495057;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0;
        }
        .nav-link:hover {
            background-color: #495057;
            color: white;
        }
        .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .form-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .page-header {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="logo">
                    <h4>Contabilidade Estrela</h4>
                </div>
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-users me-2"></i>
                                Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-building me-2"></i>
                                Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-file-alt me-2"></i>
                                Documentos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-certificate me-2"></i>
                                Certificados
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-calculator me-2"></i>
                                Impostos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-user-plus me-2"></i> Cadastrar Novo Usuário</h2>
                    <a href="list.php" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Listar Usuários
                    </a>
                </div>

                <!-- Display errors if any -->
                <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>

                <!-- Display success message if any -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- User Creation Form -->
                <div class="form-container">
                    <form action="/users/create" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">Nome Completo *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_SESSION['form_data']['email']) ? $_SESSION['form_data']['email'] : ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-bold">Senha *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">A senha deve ter pelo menos 6 caracteres</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label fw-bold">Tipo de Usuário *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($userTypes as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-bold">Telefone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($_SESSION['form_data']['phone']) ? $_SESSION['form_data']['phone'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="document" class="form-label fw-bold">CPF/CNPJ</label>
                                    <input type="text" class="form-control" id="document" name="document" 
                                           value="<?php echo isset($_SESSION['form_data']['document']) ? $_SESSION['form_data']['document'] : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-bold">Endereço</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo isset($_SESSION['form_data']['address']) ? $_SESSION['form_data']['address'] : ''; ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label fw-bold">Cidade</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo isset($_SESSION['form_data']['city']) ? $_SESSION['form_data']['city'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="state" class="form-label fw-bold">Estado</label>
                                    <input type="text" class="form-control" id="state" name="state" maxlength="2" 
                                           value="<?php echo isset($_SESSION['form_data']['state']) ? $_SESSION['form_data']['state'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label fw-bold">CEP</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                           value="<?php echo isset($_SESSION['form_data']['postal_code']) ? $_SESSION['form_data']['postal_code'] : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="status" name="status" 
                                       <?php echo (!isset($_SESSION['form_data']['status']) || $_SESSION['form_data']['status'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status">
                                    Usuário Ativo
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="list.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Cadastrar Usuário</button>
                        </div>
                    </form>
                </div>

                <?php
                // Clear form data after displaying
                if (isset($_SESSION['form_data'])) {
                    unset($_SESSION['form_data']);
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Máscara para telefone
        $(document).ready(function() {
            $('#phone').on('input', function() {
                var phone = $(this).val().replace(/\D/g, '');
                if (phone.length > 10) {
                    phone = phone.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else if (phone.length > 5) {
                    phone = phone.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                } else if (phone.length > 2) {
                    phone = phone.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                }
                $(this).val(phone);
            });

            // Máscara para CPF/CNPJ
            $('#document').on('input', function() {
                var doc = $(this).val().replace(/\D/g, '');
                if (doc.length > 11) {
                    // CNPJ
                    doc = doc.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/, '$1.$2.$3/$4-$5');
                } else {
                    // CPF
                    doc = doc.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2}).*/, '$1.$2.$3-$4');
                }
                $(this).val(doc);
            });

            // Máscara para CEP
            $('#postal_code').on('input', function() {
                var cep = $(this).val().replace(/\D/g, '');
                cep = cep.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
                $(this).val(cep);
            });
        });
    </script>
</body>
</html>