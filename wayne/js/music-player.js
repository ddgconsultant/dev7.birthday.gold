// Music Player JavaScript

class MusicPlayer {
    constructor() {
        this.audio = document.getElementById('audioPlayer');
        this.playPauseBtn = document.getElementById('playPauseBtn');
        this.progressBar = document.getElementById('progressBar');
        this.progressFilled = document.getElementById('progressFilled');
        this.progressHandle = document.getElementById('progressHandle');
        this.currentTimeDisplay = document.getElementById('currentTime');
        this.durationDisplay = document.getElementById('duration');
        this.volumeSlider = document.getElementById('volumeSlider');
        this.volumeBtn = document.getElementById('volumeBtn');
        this.fileInput = document.getElementById('fileInput');
        this.trackTitle = document.getElementById('trackTitle');
        this.trackArtist = document.getElementById('trackArtist');
        
        this.isDragging = false;
        this.isPlaying = false;
        
        this.init();
    }
    
    init() {
        // Play/Pause button
        this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        
        // File input
        this.fileInput.addEventListener('change', (e) => this.loadFile(e));
        
        // Audio events
        this.audio.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.onEnded());
        
        // Progress bar interactions
        this.progressBar.addEventListener('click', (e) => this.seek(e));
        this.progressBar.addEventListener('mousedown', (e) => this.startDragging(e));
        document.addEventListener('mousemove', (e) => this.drag(e));
        document.addEventListener('mouseup', () => this.stopDragging());
        
        // Volume control
        this.volumeSlider.addEventListener('input', (e) => this.updateVolume(e));
        this.volumeBtn.addEventListener('click', () => this.toggleMute());
        
        // Set initial volume
        this.audio.volume = this.volumeSlider.value / 100;
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
    }
    
    loadFile(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('audio/')) {
            const url = URL.createObjectURL(file);
            this.audio.src = url;
            
            // Update track info
            const fileName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            this.trackTitle.textContent = fileName;
            this.trackArtist.textContent = 'Local File';
            
            // Reset player
            this.isPlaying = false;
            this.updatePlayPauseButton();
            
            // Try to extract metadata if available
            this.extractMetadata(file);
        }
    }
    
    // You can also load a URL directly
    loadURL(url, title = 'Unknown Track', artist = 'Unknown Artist') {
        this.audio.src = url;
        this.trackTitle.textContent = title;
        this.trackArtist.textContent = artist;
        this.isPlaying = false;
        this.updatePlayPauseButton();
    }
    
    extractMetadata(file) {
        // Basic metadata extraction (can be enhanced with libraries like jsmediatags)
        if (file.name) {
            // Simple parsing of filename for demo
            const parts = file.name.replace(/\.[^/.]+$/, "").split(' - ');
            if (parts.length >= 2) {
                this.trackArtist.textContent = parts[0];
                this.trackTitle.textContent = parts[1];
            }
        }
    }
    
    togglePlayPause() {
        if (this.audio.src) {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        }
    }
    
    play() {
        this.audio.play();
        this.isPlaying = true;
        this.updatePlayPauseButton();
    }
    
    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.updatePlayPauseButton();
    }
    
    updatePlayPauseButton() {
        const playIcon = this.playPauseBtn.querySelector('.play-icon');
        const pauseIcon = this.playPauseBtn.querySelector('.pause-icon');
        
        if (this.isPlaying) {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'block';
        } else {
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
        }
    }
    
    onLoadedMetadata() {
        this.durationDisplay.textContent = this.formatTime(this.audio.duration);
    }
    
    updateProgress() {
        if (!this.isDragging && this.audio.duration) {
            const percent = (this.audio.currentTime / this.audio.duration) * 100;
            this.progressFilled.style.width = percent + '%';
            this.progressHandle.style.left = percent + '%';
            this.currentTimeDisplay.textContent = this.formatTime(this.audio.currentTime);
        }
    }
    
    seek(event) {
        if (this.audio.duration) {
            const rect = this.progressBar.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            this.audio.currentTime = percent * this.audio.duration;
        }
    }
    
    startDragging(event) {
        if (this.audio.duration) {
            this.isDragging = true;
            this.seek(event);
        }
    }
    
    drag(event) {
        if (this.isDragging) {
            this.seek(event);
            
            // Update visual progress while dragging
            const rect = this.progressBar.getBoundingClientRect();
            const percent = Math.min(Math.max(0, (event.clientX - rect.left) / rect.width), 1) * 100;
            this.progressFilled.style.width = percent + '%';
            this.progressHandle.style.left = percent + '%';
            
            // Update time display while dragging
            if (this.audio.duration) {
                const time = (percent / 100) * this.audio.duration;
                this.currentTimeDisplay.textContent = this.formatTime(time);
            }
        }
    }
    
    stopDragging() {
        this.isDragging = false;
    }
    
    updateVolume(event) {
        const volume = event.target.value / 100;
        this.audio.volume = volume;
        this.updateVolumeIcon(volume);
    }
    
    toggleMute() {
        if (this.audio.volume > 0) {
            this.previousVolume = this.audio.volume;
            this.audio.volume = 0;
            this.volumeSlider.value = 0;
        } else {
            this.audio.volume = this.previousVolume || 0.7;
            this.volumeSlider.value = this.audio.volume * 100;
        }
        this.updateVolumeIcon(this.audio.volume);
    }
    
    updateVolumeIcon(volume) {
        if (volume === 0) {
            this.volumeBtn.textContent = '🔇';
        } else if (volume < 0.5) {
            this.volumeBtn.textContent = '🔉';
        } else {
            this.volumeBtn.textContent = '🔊';
        }
    }
    
    onEnded() {
        this.isPlaying = false;
        this.updatePlayPauseButton();
        this.progressFilled.style.width = '0%';
        this.progressHandle.style.left = '0%';
        this.currentTimeDisplay.textContent = '0:00';
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    
    handleKeyboard(event) {
        // Only handle if player is focused or no input is focused
        if (document.activeElement.tagName === 'INPUT' || 
            document.activeElement.tagName === 'TEXTAREA') {
            return;
        }
        
        switch(event.key) {
            case ' ':
                event.preventDefault();
                this.togglePlayPause();
                break;
            case 'ArrowLeft':
                event.preventDefault();
                this.skip(-5); // Skip back 5 seconds
                break;
            case 'ArrowRight':
                event.preventDefault();
                this.skip(5); // Skip forward 5 seconds
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.changeVolume(0.1); // Increase volume
                break;
            case 'ArrowDown':
                event.preventDefault();
                this.changeVolume(-0.1); // Decrease volume
                break;
        }
    }
    
    skip(seconds) {
        if (this.audio.duration) {
            this.audio.currentTime = Math.min(
                Math.max(0, this.audio.currentTime + seconds),
                this.audio.duration
            );
        }
    }
    
    changeVolume(delta) {
        const newVolume = Math.min(Math.max(0, this.audio.volume + delta), 1);
        this.audio.volume = newVolume;
        this.volumeSlider.value = newVolume * 100;
        this.updateVolumeIcon(newVolume);
    }
}

// Initialize music player when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.musicPlayer = new MusicPlayer();
    
    // Example: Load a default track (you can remove this or add your own URL)
    // window.musicPlayer.loadURL('path/to/your/audio.mp3', 'Track Title', 'Artist Name');
});