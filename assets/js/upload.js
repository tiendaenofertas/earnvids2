// assets/js/upload.js - Mejora en reporte de errores
class VideoUploader {
    constructor() {
        this.uploadArea = document.getElementById('upload-area');
        this.fileInput = document.getElementById('file-input');
        this.uploadProgress = document.getElementById('upload-progress');
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        
        // Verificar si el usuario tiene almacenamiento configurado
        this.hasStorage = this.uploadArea && this.uploadArea.getAttribute('data-has-storage') === 'true';
        
        if(this.uploadArea && this.fileInput) {
            this.init();
        }
    }
    
    init() {
        // Evento Click
        this.uploadArea.addEventListener('click', (e) => {
            e.preventDefault(); 
            if (!this.checkStorageConfig()) return;
            this.fileInput.click();
        });
        
        // Evento Cambio
        this.fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) this.uploadFile(e.target.files[0]);
        });
        
        // Drag & Drop
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
            if (!this.checkStorageConfig()) return;
            if(e.dataTransfer.files.length > 0) this.uploadFile(e.dataTransfer.files[0]);
        });
    }
    
    checkStorageConfig() {
        if (!this.hasStorage) {
            // Verificar si es admin (generalmente data-has-storage es true para admin)
            // Si llegamos aquí es usuario normal sin config
            if (confirm('Configure su almacenamiento.\n\nEs necesario configurar sus credenciales antes de subir videos.\n\n¿Desea ir a la configuración ahora?')) {
                window.location.href = '/Cuenta';
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
                if(pct >= 100) this.progressText.textContent = 'Procesando en servidor...';
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.progressText.textContent = '¡Éxito!';
                        setTimeout(() => {
                            window.location.href = response.redirect_url || '/MisVideos';
                        }, 1000);
                    } else {
                        // Manejar errores de lógica (400)
                        if (response.error_code === 'NO_STORAGE_CONFIG') {
                            alert(response.message);
                            window.location.href = '/Cuenta';
                        } else {
                            alert('Error: ' + response.message);
                        }
                        this.resetUI();
                    }
                } catch (e) {
                    console.error("Respuesta no JSON:", xhr.responseText);
                    alert('Error inesperado: El servidor devolvió datos inválidos.');
                    this.resetUI();
                }
            } else {
                // AQUÍ ESTÁ EL CAMBIO IMPORTANTE PARA DEBUG
                // Intentamos leer el mensaje de error que envía el PHP modificado
                let serverMsg = "Error desconocido";
                try {
                    const errJson = JSON.parse(xhr.responseText);
                    serverMsg = errJson.message || xhr.statusText;
                } catch(e) {
                    // Si no es JSON, mostrar parte del texto (puede ser error HTML de Apache/PHP)
                    serverMsg = xhr.responseText.substring(0, 150) + "..."; 
                }
                
                alert('Error de subida (' + xhr.status + '):\n' + serverMsg);
                this.resetUI();
            }
        });
        
        xhr.addEventListener('error', () => {
            alert('Error de red. Verifica tu conexión.');
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