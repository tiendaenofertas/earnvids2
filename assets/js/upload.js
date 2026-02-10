class VideoUploader {
    constructor() {
        this.uploadArea = document.getElementById('upload-area');
        this.fileInput = document.getElementById('file-input');
        this.uploadProgress = document.getElementById('upload-progress');
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        
        if(this.uploadArea && this.fileInput) {
            this.init();
        }
    }
    
    init() {
        // --- VALIDACIÓN DE ALMACENAMIENTO ---
        // Verificar si el usuario tiene permiso para subir
        this.hasStorage = this.uploadArea.getAttribute('data-has-storage') === 'true';

        // Evento Click
        this.uploadArea.addEventListener('click', (e) => {
            e.preventDefault(); 
            if (!this.checkStorageConfig()) return; // Si no hay config, detiene aquí
            this.fileInput.click();
        });
        
        // Evento Cambio de Archivo
        this.fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) this.uploadFile(e.target.files[0]);
        });
        
        // Eventos Drag & Drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.uploadArea.addEventListener(eventName, (e) => {
                e.preventDefault(); e.stopPropagation();
            }, false);
        });

        this.uploadArea.addEventListener('dragover', () => {
            if (this.hasStorage) this.uploadArea.classList.add('dragging');
        });
        
        this.uploadArea.addEventListener('dragleave', () => this.uploadArea.classList.remove('dragging'));
        
        this.uploadArea.addEventListener('drop', (e) => {
            this.uploadArea.classList.remove('dragging');
            
            if (!this.checkStorageConfig()) return; // Validación al soltar el archivo
            
            if(e.dataTransfer.files.length > 0) this.uploadFile(e.dataTransfer.files[0]);
        });
    }
    
    // Función centralizada para verificar y mostrar alerta
    checkStorageConfig() {
        if (!this.hasStorage) {
            // Mostrar mensaje de advertencia y opción de ir a configuración
            if (confirm('Configure su almacenamiento.\n\nEs necesario configurar sus credenciales (Access Key y Secret Key) antes de subir videos.\n\n¿Desea ir a la configuración ahora?')) {
                window.location.href = '/account.php';
            }
            return false;
        }
        return true;
    }
    
    async uploadFile(file) {
        if(file.size > 5 * 1024 * 1024 * 1024) {
            alert('El archivo es demasiado grande (Máx 5GB)');
            return;
        }

        const formData = new FormData();
        formData.append('video', file);
        formData.append('title', file.name.replace(/\.[^/.]+$/, ""));
        
        this.uploadProgress.style.display = 'block';
        this.uploadArea.style.display = 'none';
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                this.progressBar.style.width = pct + '%';
                this.progressText.textContent = pct + '%';
                if(pct >= 100) this.progressText.textContent = 'Procesando... por favor espera.';
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.progressText.textContent = '¡Éxito! Redirigiendo...';
                        setTimeout(() => {
                            window.location.href = response.redirect_url || '/admin/videos.php';
                        }, 1000);
                    } else {
                        // Manejar error específico de almacenamiento si viniera del backend
                        if (response.error_code === 'NO_STORAGE_CONFIG') {
                            alert(response.message);
                            window.location.href = '/account.php';
                        } else {
                            alert('Error: ' + response.message);
                        }
                        this.resetUI();
                    }
                } catch (e) {
                    console.error(xhr.responseText);
                    alert('Error inesperado del servidor.');
                    this.resetUI();
                }
            } else if (xhr.status === 403) {
                 // Capturar el error 403 específico de la API
                 try {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message);
                    window.location.href = '/account.php';
                 } catch(e) {
                    alert('Acceso denegado. Configure su almacenamiento.');
                 }
                 this.resetUI();
            } else {
                alert('Error de subida: ' + xhr.status);
                this.resetUI();
            }
        });
        
        xhr.addEventListener('error', () => {
            alert('Error de red.');
            this.resetUI();
        });
        
        xhr.open('POST', '/api/upload.php');
        xhr.send(formData);
    }
    
    resetUI() {
        this.uploadProgress.style.display = 'none';
        this.uploadArea.style.display = 'block';
        this.progressBar.style.width = '0%';
        this.fileInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('upload-area')) new VideoUploader();
});
