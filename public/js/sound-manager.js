/**
 * Sound Manager for Bell Timing System
 * Handles all audio functionality including bell sounds, notifications, and volume control
 */

class SoundManager {
    constructor() {
        this.sounds = new Map();
        this.isEnabled = true;
        this.volume = 0.7;
        this.currentlyPlaying = null;
        this.audioContext = null;
        this.gainNode = null;
        
        this.init();
    }

    /**
     * Initialize the sound manager
     */
    async init() {
        try {
            // Initialize Web Audio API for better control
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.gainNode = this.audioContext.createGain();
            this.gainNode.connect(this.audioContext.destination);
            this.gainNode.gain.value = this.volume;

            // Load sound preferences from localStorage
            this.loadPreferences();
            
            // Preload sound files
            await this.preloadSounds();
            
            // Set up audio context resume (required for autoplay policy)
            this.setupAudioContextResume();
            
            console.log('Sound Manager initialized successfully');
        } catch (error) {
            console.error('Error initializing Sound Manager:', error);
            this.fallbackToBasicAudio();
        }
    }

    /**
     * Fallback to basic HTML5 audio if Web Audio API fails
     */
    fallbackToBasicAudio() {
        console.log('Falling back to basic HTML5 audio');
        this.audioContext = null;
        this.gainNode = null;
    }

    /**
     * Setup audio context resume for autoplay policy compliance
     */
    setupAudioContextResume() {
        if (!this.audioContext) return;

        const resumeAudio = () => {
            if (this.audioContext.state === 'suspended') {
                this.audioContext.resume().then(() => {
                    console.log('Audio context resumed');
                });
            }
        };

        // Resume on user interaction
        document.addEventListener('click', resumeAudio, { once: true });
        document.addEventListener('touchstart', resumeAudio, { once: true });
        document.addEventListener('keydown', resumeAudio, { once: true });
    }

    /**
     * Preload all sound files
     */
    async preloadSounds() {
        const soundFiles = {
            'school-bell': '/sounds/school-bell.mp3',
            'warning-beep': '/sounds/warning-beep.mp3',
            'notification': '/sounds/notification.mp3',
            'period-change': '/sounds/notification.mp3', // Use notification sound for period changes
            'emergency': '/sounds/warning-beep.mp3' // Use warning beep for emergencies
        };

        const loadPromises = Object.entries(soundFiles).map(([key, url]) => 
            this.loadSound(key, url)
        );

        try {
            await Promise.all(loadPromises);
            console.log('All sounds preloaded successfully');
        } catch (error) {
            console.error('Error preloading sounds:', error);
        }
    }

    /**
     * Load a single sound file
     */
    async loadSound(key, url) {
        try {
            if (this.audioContext) {
                // Use Web Audio API
                const response = await fetch(url);
                const arrayBuffer = await response.arrayBuffer();
                const audioBuffer = await this.audioContext.decodeAudioData(arrayBuffer);
                
                this.sounds.set(key, {
                    type: 'webaudio',
                    buffer: audioBuffer,
                    url: url
                });
            } else {
                // Use HTML5 Audio
                const audio = new Audio(url);
                audio.preload = 'auto';
                
                return new Promise((resolve, reject) => {
                    audio.addEventListener('canplaythrough', () => {
                        this.sounds.set(key, {
                            type: 'html5',
                            audio: audio,
                            url: url
                        });
                        resolve();
                    });
                    
                    audio.addEventListener('error', (e) => {
                        console.warn(`Failed to load sound ${key}:`, e);
                        // Create a silent placeholder
                        this.sounds.set(key, {
                            type: 'silent',
                            url: url
                        });
                        resolve(); // Don't reject, just use silent placeholder
                    });
                });
            }
        } catch (error) {
            console.warn(`Failed to load sound ${key}:`, error);
            // Create a silent placeholder
            this.sounds.set(key, {
                type: 'silent',
                url: url
            });
        }
    }

    /**
     * Play a sound by key
     */
    async playSound(soundKey, options = {}) {
        if (!this.isEnabled) {
            console.log('Sound is disabled');
            return;
        }

        const sound = this.sounds.get(soundKey);
        if (!sound) {
            console.warn(`Sound ${soundKey} not found`);
            return;
        }

        if (sound.type === 'silent') {
            console.log(`Playing silent sound: ${soundKey}`);
            return;
        }

        try {
            // Stop currently playing sound if specified
            if (options.stopCurrent && this.currentlyPlaying) {
                this.stopCurrentSound();
            }

            if (sound.type === 'webaudio' && this.audioContext) {
                await this.playWebAudioSound(sound, options);
            } else if (sound.type === 'html5') {
                await this.playHTML5Sound(sound, options);
            }

            // Trigger custom event
            this.dispatchSoundEvent('soundPlayed', { soundKey, options });
        } catch (error) {
            console.error(`Error playing sound ${soundKey}:`, error);
        }
    }

    /**
     * Play sound using Web Audio API
     */
    async playWebAudioSound(sound, options = {}) {
        if (!this.audioContext || this.audioContext.state === 'suspended') {
            await this.audioContext?.resume();
        }

        const source = this.audioContext.createBufferSource();
        source.buffer = sound.buffer;
        
        // Apply volume
        const gainNode = this.audioContext.createGain();
        gainNode.gain.value = (options.volume !== undefined ? options.volume : this.volume);
        
        source.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Set loop if specified
        if (options.loop) {
            source.loop = true;
        }
        
        // Store reference for stopping
        this.currentlyPlaying = {
            source: source,
            type: 'webaudio'
        };
        
        // Clean up when finished
        source.onended = () => {
            if (this.currentlyPlaying?.source === source) {
                this.currentlyPlaying = null;
            }
        };
        
        source.start(0);
        
        // Auto-stop after duration if specified
        if (options.duration) {
            setTimeout(() => {
                if (this.currentlyPlaying?.source === source) {
                    source.stop();
                }
            }, options.duration);
        }
    }

    /**
     * Play sound using HTML5 Audio
     */
    async playHTML5Sound(sound, options = {}) {
        const audio = sound.audio.cloneNode();
        
        // Apply volume
        audio.volume = (options.volume !== undefined ? options.volume : this.volume);
        
        // Set loop if specified
        if (options.loop) {
            audio.loop = true;
        }
        
        // Store reference for stopping
        this.currentlyPlaying = {
            audio: audio,
            type: 'html5'
        };
        
        // Clean up when finished
        audio.onended = () => {
            if (this.currentlyPlaying?.audio === audio) {
                this.currentlyPlaying = null;
            }
        };
        
        await audio.play();
        
        // Auto-stop after duration if specified
        if (options.duration) {
            setTimeout(() => {
                if (this.currentlyPlaying?.audio === audio) {
                    audio.pause();
                    audio.currentTime = 0;
                }
            }, options.duration);
        }
    }

    /**
     * Stop currently playing sound
     */
    stopCurrentSound() {
        if (!this.currentlyPlaying) return;

        try {
            if (this.currentlyPlaying.type === 'webaudio') {
                this.currentlyPlaying.source.stop();
            } else if (this.currentlyPlaying.type === 'html5') {
                this.currentlyPlaying.audio.pause();
                this.currentlyPlaying.audio.currentTime = 0;
            }
        } catch (error) {
            console.error('Error stopping sound:', error);
        }

        this.currentlyPlaying = null;
    }

    /**
     * Stop all sounds
     */
    stopAllSounds() {
        this.stopCurrentSound();
        
        // Stop any other HTML5 audio elements
        this.sounds.forEach(sound => {
            if (sound.type === 'html5' && !sound.audio.paused) {
                sound.audio.pause();
                sound.audio.currentTime = 0;
            }
        });
    }

    /**
     * Set master volume (0.0 to 1.0)
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        
        if (this.gainNode) {
            this.gainNode.gain.value = this.volume;
        }
        
        // Update HTML5 audio volumes
        this.sounds.forEach(sound => {
            if (sound.type === 'html5') {
                sound.audio.volume = this.volume;
            }
        });
        
        this.savePreferences();
        this.dispatchSoundEvent('volumeChanged', { volume: this.volume });
    }

    /**
     * Get current volume
     */
    getVolume() {
        return this.volume;
    }

    /**
     * Enable/disable sound
     */
    setEnabled(enabled) {
        this.isEnabled = enabled;
        
        if (!enabled) {
            this.stopAllSounds();
        }
        
        this.savePreferences();
        this.dispatchSoundEvent('enabledChanged', { enabled: this.isEnabled });
    }

    /**
     * Check if sound is enabled
     */
    isEnabledState() {
        return this.isEnabled;
    }

    /**
     * Play bell sound for period changes
     */
    async playBellSound(options = {}) {
        await this.playSound('school-bell', {
            volume: options.volume || this.volume,
            ...options
        });
    }

    /**
     * Play warning sound
     */
    async playWarningSound(options = {}) {
        await this.playSound('warning-beep', {
            volume: options.volume || this.volume,
            ...options
        });
    }

    /**
     * Play notification sound
     */
    async playNotificationSound(options = {}) {
        await this.playSound('notification', {
            volume: options.volume || 0.5, // Quieter for notifications
            ...options
        });
    }

    /**
     * Play emergency alert sound
     */
    async playEmergencySound(options = {}) {
        await this.playSound('emergency', {
            volume: options.volume || 1.0, // Full volume for emergencies
            loop: options.loop !== false, // Loop by default
            ...options
        });
    }

    /**
     * Test sound functionality
     */
    async testSound(soundKey = 'notification') {
        console.log(`Testing sound: ${soundKey}`);
        await this.playSound(soundKey, { volume: 0.5, duration: 2000 });
    }

    /**
     * Load preferences from localStorage
     */
    loadPreferences() {
        try {
            const prefs = localStorage.getItem('bellSoundPreferences');
            if (prefs) {
                const parsed = JSON.parse(prefs);
                this.isEnabled = parsed.enabled !== false; // Default to true
                this.volume = parsed.volume !== undefined ? parsed.volume : 0.7;
            }
        } catch (error) {
            console.error('Error loading sound preferences:', error);
        }
    }

    /**
     * Save preferences to localStorage
     */
    savePreferences() {
        try {
            const prefs = {
                enabled: this.isEnabled,
                volume: this.volume
            };
            localStorage.setItem('bellSoundPreferences', JSON.stringify(prefs));
        } catch (error) {
            console.error('Error saving sound preferences:', error);
        }
    }

    /**
     * Dispatch custom sound events
     */
    dispatchSoundEvent(eventType, data) {
        const event = new CustomEvent(`bellSound:${eventType}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }

    /**
     * Get sound manager status
     */
    getStatus() {
        return {
            enabled: this.isEnabled,
            volume: this.volume,
            soundsLoaded: this.sounds.size,
            audioContext: !!this.audioContext,
            currentlyPlaying: !!this.currentlyPlaying
        };
    }

    /**
     * Cleanup resources
     */
    destroy() {
        this.stopAllSounds();
        
        if (this.audioContext) {
            this.audioContext.close();
        }
        
        this.sounds.clear();
        this.currentlyPlaying = null;
    }
}

// Create global sound manager instance
window.bellSoundManager = new SoundManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SoundManager;
}

console.log('Sound Manager loaded successfully');