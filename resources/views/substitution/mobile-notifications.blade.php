{{-- Mobile Substitute Notifications Component --}}
<div id="substitute-notifications" class="mobile-notifications-container">
    {{-- Notification Permission Banner --}}
    <div id="notification-permission-banner" class="notification-banner" style="display: none;">
        <div class="banner-content">
            <div class="banner-icon">
                <i class="fas fa-bell-slash"></i>
            </div>
            <div class="banner-text">
                <h4>Enable Notifications</h4>
                <p>Get instant alerts for substitute assignments and reminders</p>
            </div>
            <div class="banner-actions">
                <button id="enable-notifications-btn" class="btn btn-primary btn-sm">
                    <i class="fas fa-bell"></i> Enable
                </button>
                <button id="dismiss-banner-btn" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Active Notifications List --}}
    <div id="active-notifications" class="notifications-list">
        {{-- Notifications will be populated by JavaScript --}}
    </div>

    {{-- Notification Settings Panel --}}
    <div id="notification-settings" class="settings-panel" style="display: none;">
        <div class="settings-header">
            <h5><i class="fas fa-cog"></i> Notification Settings</h5>
            <button id="close-settings-btn" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="settings-content">
            <div class="setting-group">
                <label class="setting-label">
                    <input type="checkbox" id="vibration-enabled" checked>
                    <span class="checkmark"></span>
                    Vibration
                </label>
                <small class="text-muted">Vibrate device for important notifications</small>
            </div>

            <div class="setting-group">
                <label class="setting-label">
                    <input type="checkbox" id="sound-enabled" checked>
                    <span class="checkmark"></span>
                    Sound Alerts
                </label>
                <small class="text-muted">Play sound for notifications</small>
            </div>

            <div class="setting-group">
                <label class="setting-label">
                    <input type="checkbox" id="reminder-enabled" checked>
                    <span class="checkmark"></span>
                    Class Reminders
                </label>
                <small class="text-muted">Get reminders 15 minutes before class</small>
            </div>

            <div class="setting-group">
                <label class="setting-label">
                    <input type="checkbox" id="emergency-priority" checked>
                    <span class="checkmark"></span>
                    Emergency Priority
                </label>
                <small class="text-muted">High priority for emergency assignments</small>
            </div>
        </div>

        <div class="settings-footer">
            <button id="save-settings-btn" class="btn btn-primary btn-sm">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </div>

    {{-- Quick Actions Floating Button --}}
    <div id="notification-fab" class="floating-action-button">
        <button id="fab-main-btn" class="fab-button">
            <i class="fas fa-bell"></i>
            <span id="notification-count" class="notification-badge" style="display: none;">0</span>
        </button>
        
        <div id="fab-menu" class="fab-menu" style="display: none;">
            <button id="fab-settings-btn" class="fab-menu-item" title="Settings">
                <i class="fas fa-cog"></i>
            </button>
            <button id="fab-refresh-btn" class="fab-menu-item" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="fab-clear-btn" class="fab-menu-item" title="Clear All">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>

{{-- Notification Templates --}}
<script type="text/template" id="notification-template">
    <div class="notification-item" data-id="{{id}}" data-type="{{type}}">
        <div class="notification-icon {{priority}}">
            <i class="{{icon}}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-header">
                <h6 class="notification-title">{{title}}</h6>
                <span class="notification-time">{{time}}</span>
            </div>
            <p class="notification-message">{{message}}</p>
            {{#if actions}}
            <div class="notification-actions">
                {{#each actions}}
                <button class="btn btn-sm {{class}}" data-action="{{action}}" data-id="{{../id}}">
                    <i class="{{icon}}"></i> {{text}}
                </button>
                {{/each}}
            </div>
            {{/if}}
        </div>
        <button class="notification-dismiss" data-id="{{id}}">
            <i class="fas fa-times"></i>
        </button>
    </div>
</script>

<style>
/* Mobile Notifications Styles */
.mobile-notifications-container {
    position: relative;
    z-index: 1000;
}

.notification-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.banner-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.banner-icon {
    font-size: 1.5rem;
    opacity: 0.9;
}

.banner-text {
    flex: 1;
}

.banner-text h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.banner-text p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.banner-actions {
    display: flex;
    gap: 0.5rem;
}

.notifications-list {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
}

.notification-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.notification-item.high {
    border-left: 4px solid #dc3545;
    background: #fff5f5;
}

.notification-item.medium {
    border-left: 4px solid #ffc107;
}

.notification-item.low {
    border-left: 4px solid #28a745;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.notification-icon.high {
    background: #dc3545;
}

.notification-icon.medium {
    background: #ffc107;
}

.notification-icon.low {
    background: #28a745;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.notification-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #333;
}

.notification-time {
    font-size: 0.8rem;
    color: #6c757d;
    white-space: nowrap;
}

.notification-message {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: #555;
    line-height: 1.4;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.notification-dismiss {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    color: #6c757d;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.notification-dismiss:hover {
    background: #f8f9fa;
    color: #dc3545;
}

.settings-panel {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 1rem;
}

.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.settings-header h5 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.settings-content {
    padding: 1rem;
}

.setting-group {
    margin-bottom: 1rem;
}

.setting-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.setting-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #007bff;
}

.settings-footer {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.floating-action-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 1050;
}

.fab-button {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0,123,255,0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    position: relative;
}

.fab-button:hover {
    background: #0056b3;
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,123,255,0.5);
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

.fab-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.fab-menu-item {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    color: #333;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.fab-menu-item:hover {
    background: #f8f9fa;
    transform: scale(1.1);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .banner-content {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .banner-actions {
        justify-content: center;
    }
    
    .notification-item {
        padding: 0.75rem;
        gap: 0.75rem;
    }
    
    .notification-header {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
    }
    
    .notification-actions {
        justify-content: flex-start;
    }
    
    .floating-action-button {
        bottom: 1rem;
        right: 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .notification-item {
        background: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .notification-title {
        color: #f7fafc;
    }
    
    .notification-message {
        color: #cbd5e0;
    }
    
    .settings-panel {
        background: #2d3748;
        border-color: #4a5568;
    }
    
    .settings-header {
        background: #1a202c;
        border-color: #4a5568;
    }
    
    .settings-footer {
        background: #1a202c;
        border-color: #4a5568;
    }
}
</style>

<script>
// Initialize mobile substitute notifications
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the substitute notification system
    if (typeof SubstituteNotificationManager !== 'undefined') {
        window.substituteNotifications = new SubstituteNotificationManager({
            apiEndpoint: '{{ route("api.substitute.notifications") }}',
            updateInterval: 30000, // 30 seconds
            enableVibration: true,
            enableSound: true,
            enableReminders: true
        });
        
        window.substituteNotifications.init();
    }
});
</script>