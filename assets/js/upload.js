class VideoUploader {
    constructor() {
        this.uploadArea = document.getElementById('upload-area');
        this.fileInput = document.getElementById('file-input');
        this.uploadProgress = document.getElementById('upload-progress');
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        this.uploadedFiles = [];
        
        this.init();
    }
    
    init() {
        // Click para seleccionar archivo
        this.uploadArea.addEventListener('click', () => {
            this.fileInput.click();
        });
        
        // Selección de archivo
        this.fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });
        
        // Drag and drop
        this.uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.uploadArea.classList.add('dragging');
        });
        
        this.uploadArea.addEventListener('dragleave', () => {
            this.uploadArea.classList.remove('dragging');
        });
        
        this.uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            this.uploadArea.classList.remove('dragging');
            this.handleFiles(e.dataTransfer.files);
        });
    }
    
    handleFiles(files) {
        for (let file of files) {
            if (this.validateFile(file)) {
                this.uploadFile(file);
            }
        }
    }
    
    validateFile(file) {
        const allowedTypes = ['video/mp4', 'video/webm', 'video/x-matroska', 'video/avi', 'video/quicktime'];
        const maxSize = 5 * 1024 * 1024 * 1024; // 5GB
        
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(mp4|webm|mkv|avi|mov|flv|wmv)$/i)) {
            showNotification('Formato de archivo no permitido', 'error');
            return false;
        }
        
        if (file.size > maxSize) {
            showNotification('El archivo excede el tamaño máximo de 5GB', 'error');
            return false;
        }
        
        return true;
    }
    
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('video', file);
        formData.append('title', file.name.replace(/\.[^/.]+$/, ''));
        
        // Generar miniatura
        const thumbnail = await this.generateThumbnail(file);
        if (thumbnail) {
            formData.append('thumbnail', thumbnail);
        }
        
        // Mostrar progreso
        this.uploadProgress.style.display = 'block';
        this.progressBar.style.width = '0%';
        this.progressText.textContent = '0%';
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                this.progressBar.style.width = percentComplete + '%';
                this.progressText.textContent = Math.round(percentComplete) + '%';
            }
        });
        
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('Video subido exitosamente');
                    this.uploadedFiles.push(response);
                    this.displayUploadedFile(response);
                } else {
                    showNotification(response.message || 'Error al subir el video', 'error');
                }
            } else {
                showNotification('Error al subir el video', 'error');
            }
            this.resetProgress();
        });
        
        xhr.addEventListener('error', () => {
            showNotification('Error de conexión', 'error');
            this.resetProgress();
        });
        
        xhr.open('POST', '/api/upload.php');
        xhr.send(formData);
    }
    
    async generateThumbnail(videoFile) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            video.addEventListener('loadedmetadata', () => {
                video.currentTime = video.duration * 0.1;
            });
            
            video.addEventListener('seeked', () => {
                canvas.width = 320;
                canvas.height = 180;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                canvas.toBlob((blob) => {
                    resolve(blob);
                }, 'image/jpeg', 0.8);
            });
            
            video.addEventListener('error', () => {
                resolve(null);
            });
            
            video.src = URL.createObjectURL(videoFile);
        });
    }
    
    displayUploadedFile(fileInfo) {
        const fileList = document.getElementById('uploaded-files');
        if (!fileList) return;
        
        const fileItem = document.createElement('div');
        fileItem.className = 'uploaded-file-item';
        fileItem.innerHTML = `
            <div class="file-info">
                <h4>${fileInfo.title || 'Sin título'}</h4>
                <p>Código embed: ${fileInfo.embed_code}</p>
            </div>
            <div class="file-actions">
                <button onclick="copyToClipboard('${location.origin}/watch.php?v=${fileInfo.embed_code}')" class="btn btn-secondary">
                    Copiar URL
                </button>
                <a href="/watch.php?v=${fileInfo.embed_code}" class="btn">Ver Video</a>
            </div>
        `;
        fileList.appendChild(fileItem);
    }
    
    resetProgress() {
        setTimeout(() => {
            this.uploadProgress.style.display = 'none';
            this.progressBar.style.width = '0%';
            this.progressText.textContent = '0%';
        }, 1000);
    }
}

// Inicializar uploader si existe el elemento
if (document.getElementById('upload-area')) {
    new VideoUploader();
}