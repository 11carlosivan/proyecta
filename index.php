<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Mostrar errores si existen
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>PROYECTA - Inicio de Sesión</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "primary-dark": "#0c6bc5",
                        "secondary": "#6c757d",
                        "success": "#28a745",
                        "danger": "#dc3545",
                        "warning": "#ffc107",
                        "info": "#17a2b8",
                        "light": "#f8f9fa",
                        "dark": "#343a40",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "sans": ["Inter", "system-ui", "-apple-system", "sans-serif"],
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "full": "9999px",
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                    },
                },
            },
        }
    </script>
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #137fec 0%, #0c6bc5 50%, #101922 100%);
        }
        
        .logo-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(19, 127, 236, 0.1);
        }
        
        .btn-hover {
            transition: all 0.2s ease;
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(19, 127, 236, 0.2);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display min-h-screen flex flex-col">
    <!-- Header/Navbar -->
    <nav class="bg-white dark:bg-[#111418] border-b border-gray-200 dark:border-[#283039]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <div class="size-10 bg-primary rounded-lg flex items-center justify-center shadow-lg">
                            <span class="material-symbols-outlined text-white text-xl">rocket_launch</span>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">PROYECTA</h1>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#283039] text-gray-600 dark:text-gray-300">
                        <span class="material-symbols-outlined dark:hidden">dark_mode</span>
                        <span class="material-symbols-outlined hidden dark:inline">light_mode</span>
                    </button>
                    <a href="register.php" class="text-primary hover:text-primary-dark font-medium">
                        Registrarse
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Left Column: Hero Content -->
            <div class="space-y-8 animate-fade-in">
                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-gray-900 dark:text-white leading-tight">
                        Organiza. Ejecuta.
                        <span class="text-primary block">Triunfa.</span>
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300 mt-4">
                        La plataforma de gestión ágil que impulsa resultados. 
                        Desde pequeñas tareas hasta grandes proyectos.
                    </p>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span class="text-gray-700 dark:text-gray-300">Gestión de tareas tipo Kanban</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span class="text-gray-700 dark:text-gray-300">Seguimiento de tiempo y progreso</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span class="text-gray-700 dark:text-gray-300">Colaboración en equipo en tiempo real</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-green-500">check_circle</span>
                        <span class="text-gray-700 dark:text-gray-300">Reportes y análisis detallados</span>
                    </div>
                </div>
                
                <div class="pt-4">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        Únete a miles de equipos que ya usan PROYECTA para mejorar su productividad.
                    </p>
                </div>
            </div>
            
            <!-- Right Column: Login Form -->
            <div class="animate-slide-up">
                <div class="bg-white dark:bg-[#1c2127] rounded-2xl border border-gray-200 dark:border-[#283039] shadow-xl p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Iniciar Sesión</h2>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">
                            Accede a tu cuenta para continuar
                        </p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-red-500">error</span>
                            <div>
                                <p class="text-red-700 dark:text-red-300 font-medium">Error de autenticación</p>
                                <p class="text-red-600 dark:text-red-400 text-sm mt-1">
                                    Credenciales incorrectas. Por favor, intenta de nuevo.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-green-500">check_circle</span>
                            <div>
                                <p class="text-green-700 dark:text-green-300 font-medium">¡Registro exitoso!</p>
                                <p class="text-green-600 dark:text-green-400 text-sm mt-1">
                                    Tu cuenta ha sido creada. Ya puedes iniciar sesión.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form action="login.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Correo electrónico
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">mail</span>
                                <input type="email" name="email" required
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-[#111418] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent input-focus"
                                       placeholder="nombre@empresa.com">
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Contraseña
                                </label>
                                <a href="#" class="text-sm text-primary hover:text-primary-dark hover:underline">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                                <input type="password" name="password" required
                                       class="w-full pl-10 pr-10 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-[#111418] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent input-focus"
                                       placeholder="••••••••">
                                <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" id="togglePassword">
                                    <span class="material-symbols-outlined">visibility</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary focus:ring-primary">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Recordar sesión</span>
                            </label>
                        </div>
                        
                        <button type="submit" 
                                class="w-full py-3 px-4 bg-primary hover:bg-primary-dark text-white font-semibold rounded-lg btn-hover transition-all duration-200 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">login</span>
                            Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="mt-8">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white dark:bg-[#1c2127] text-gray-500 dark:text-gray-400">
                                    O continúa con
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button type="button" class="w-full py-2.5 px-4 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="text-sm font-medium">Google</span>
                            </button>
                            
                            <button type="button" class="w-full py-2.5 px-4 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                                </svg>
                                <span class="text-sm font-medium">GitHub</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center">
                        <p class="text-gray-600 dark:text-gray-400">
                            ¿No tienes una cuenta?
                            <a href="register.php" class="text-primary font-semibold hover:text-primary-dark hover:underline ml-1">
                                Regístrate gratis
                            </a>
                        </p>
                    </div>
                </div>
                
                <!-- Trust Badges -->
                <div class="mt-6 flex items-center justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-green-500">lock</span>
                        SSL Seguro
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-blue-500">shield</span>
                        Datos encriptados
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-purple-500">workspace_premium</span>
                        GDPR Compliant
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white dark:bg-[#111418] border-t border-gray-200 dark:border-[#283039] py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="size-8 bg-primary rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">rocket_launch</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">PROYECTA</span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        Plataforma de Organización y Ejecución de Tareas Ágiles
                    </p>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                        Producto
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Características</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Precios</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">API</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Documentación</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                        Empresa
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Nosotros</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Blog</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Carreras</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Contacto</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                        Soporte
                    </h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Centro de ayuda</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Comunidad</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Status</a></li>
                        <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-primary text-sm">Reportar bug</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        © 2024 PROYECTA. Todos los derechos reservados.
                    </p>
                    <div class="mt-4 md:mt-0 flex gap-6">
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">GitHub</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">LinkedIn</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const icon = this.querySelector('.material-symbols-outlined');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
            }
        });
        
        // Toggle theme
        document.getElementById('theme-toggle')?.addEventListener('click', function() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
        
        // Check saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]');
            const password = this.querySelector('input[name="password"]');
            
            if (!email.value || !password.value) {
                e.preventDefault();
                alert('Por favor, completa todos los campos');
            }
        });
        
        // Add some interactive effects
        document.querySelectorAll('.btn-hover').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>