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
        this.hasStorage = this.uploadArea.getAttribute('data-has-storage') === 'true';

        this.uploadArea.addEventListener('click', (e) => {
            e.preventDefault(); 
            if (!this.checkStorageConfig()) return;
            this.fileInput.click();
        });
        
        this.fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) this.uploadFile(e.target.files[0]);
        });
        
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
            if (confirm('Configure su almacenamiento.\n\nEs necesario configurar sus credenciales antes de subir videos.\n\n¿Desea ir a la configuración ahora?')) {
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
            // CORRECCIÓN: Intentar leer JSON siempre, incluso si es error (400, 500)
            let response = null;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (e) {
                console.error("Error parseando respuesta:", xhr.responseText);
            }

            if (xhr.status === 200) {
                if (response && response.success) {
                    this.progressText.textContent = '¡Éxito! Redirigiendo...';
                    setTimeout(() => {
                        window.location.href = response.redirect_url || '/admin/videos.php';
                    }, 1000);
                } else {
                    alert('Error: ' + (response ? response.message : 'Error desconocido'));
                    this.resetUI();
                }
            } else if (xhr.status === 403) {
                 if (response && response.message) alert(response.message);
                 else alert('Acceso denegado. Configure su almacenamiento.');
                 window.location.href = '/account.php';
                 this.resetUI();
            } else {
                // AQUÍ ESTABA EL PROBLEMA: Ahora mostramos el mensaje real del backend
                const msg = response && response.message ? response.message : 'Error del servidor (' + xhr.status + ')';
                alert('Error de subida: ' + msg);
                this.resetUI();
            }
        });
        
        xhr.addEventListener('error', () => {
            alert('Error de red al intentar subir el archivo.');
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
