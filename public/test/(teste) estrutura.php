Estrutura de Diretórios Atualizada - GED ESTRELA
GED_estrela/
│
├── app/
│   │── Classes
│   │   └── Mailer.php
│   │
│   ├── config/
│   │   ├── constants.php
│   │   ├── database.php
│   │   ├── email_config.php         
│   │   └── error_handler.php
│   │
│   ├── controllers/
│   │   ├── auth_controller.php
│   │   └── session_controller.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Document.php
│   │   ├── Certificate.php
│   │   └── Imposto.php
│   │
│   ├── helpers/
│   │   └── SessionManager.php
│   │
│   ├── classes/                      
│   │   ├── Auth.php                  
│   │   ├── Mailer.php                
│   │   ├── ErrorHandler.php          
│   │   └── Database.php              
│   │
│   ├── includes/
│   │   ├── auth/
│   │   │   ├── Auth.php
│   │   │   ├── login.php
│   │   │   └── recovery.php
│   │   │
│   │   ├── base/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── base_page.php
│   │   │
│   │   └── utils/
│   │       └── functions.php
│   |
│   ├── sessions/
│   │
│   ├── modals/
│   │   ├── error_modal.php
│   │   ├── success_modal.php
│   │   └── confirmation_modal.php
│   │
│   ├── logs/                         
│   │   ├── error_logs/               
│   │   └── email_logs/               
│   │    
│   │
│   ├── templates/
│   │   └── emails/
│   │       └── password_recovery.html
│   │
│   ├── menu/
│   │   ├── admin_menu.php
│   │   ├── editor_menu.php
│   │   ├── tax_menu.php
│   │   ├── employee_menu.php
│   │   ├── financial_menu.php
│   │   ├── client_menu.php
│   │   └── default_menu.php
│   │
│   └── init.php
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── login.css
│   │   └── dashboard.css
│   │
│   ├── js/
│   │   └── main.js
│   │
│   └── images/
│       ├── logo.png
│
├── modules/
│   ├── usuarios/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── view.php
│   │
│   ├── empresas/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── view.php
│   │
│   ├── documentos/
│   │   ├── index.php
│   │   ├── upload.php
│   │   ├── edit.php
│   │   └── view.php
│   │
│   ├── certificados/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── view.php
│   │
│   └── impostos/
│       ├── index.php
│       ├── create.php
│       ├── edit.php
│       ├── view.php
│       └── boletos/
│           ├── index.php
│           ├── create.php
│           └── view.php
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── recovery.php
│   └── test/                         
│       ├── test_email.php            
│       ├── test_db.php              
│       └── test_upload.php         
│
├── uploads/
│   ├── documents/
│   ├── certificates/
│   ├── boletos/
│   └── profile_images/
│
├── vendor/                           
│   └── phpmailer/                    
│       └── phpmailer/
│           └── src/ 
│               ├── PHPMailer.php
│               ├── SMTP.php
│               └── Exception.php
│
├── .htaccess/                          
└── index.php


