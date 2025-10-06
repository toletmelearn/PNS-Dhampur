{{-- Session Timeout Warning Modal --}}
<div id="sessionTimeoutModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="sessionTimeoutModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-warning">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="sessionTimeoutModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Session Timeout Warning
                </h5>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-clock text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="mb-3">Your session will expire soon!</h6>
                    <p id="timeoutMessage" class="mb-3">
                        Your session will expire in <span id="timeRemaining" class="fw-bold text-danger">5</span> minutes due to inactivity.
                    </p>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Click "Stay Logged In" to extend your session, or "Logout" to end your session now.
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" id="extendSessionBtn">
                    <i class="fas fa-refresh me-1"></i>
                    Stay Logged In
                </button>
                <button type="button" class="btn btn-danger" id="logoutNowBtn">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Logout Now
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Session Status Indicator --}}
<div id="sessionStatusIndicator" class="position-fixed" style="top: 10px; right: 10px; z-index: 1050; display: none;">
    <div class="badge bg-info text-white p-2 rounded-pill">
        <i class="fas fa-clock me-1"></i>
        Session: <span id="sessionTimeLeft">--</span> min
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let sessionCheckInterval;
    let timeoutWarningShown = false;
    let sessionTimeoutModal;
    
    // Initialize modal
    if (typeof bootstrap !== 'undefined') {
        sessionTimeoutModal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
    } else if (typeof $ !== 'undefined') {
        sessionTimeoutModal = $('#sessionTimeoutModal');
    }
    
    // Check session status every minute
    function checkSessionStatus() {
        fetch('/api/session/timeout-warning', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.warning && !timeoutWarningShown) {
                showTimeoutWarning(data.time_remaining);
                timeoutWarningShown = true;
            } else if (!data.warning) {
                timeoutWarningShown = false;
                hideTimeoutWarning();
            }
            
            // Update session indicator
            updateSessionIndicator(data.time_remaining || 0);
        })
        .catch(error => {
            console.error('Session check failed:', error);
        });
    }
    
    // Show timeout warning modal
    function showTimeoutWarning(timeRemaining) {
        document.getElementById('timeRemaining').textContent = timeRemaining;
        
        if (typeof bootstrap !== 'undefined') {
            sessionTimeoutModal.show();
        } else if (typeof $ !== 'undefined') {
            sessionTimeoutModal.modal('show');
        }
    }
    
    // Hide timeout warning modal
    function hideTimeoutWarning() {
        if (typeof bootstrap !== 'undefined') {
            sessionTimeoutModal.hide();
        } else if (typeof $ !== 'undefined') {
            sessionTimeoutModal.modal('hide');
        }
    }
    
    // Update session status indicator
    function updateSessionIndicator(timeRemaining) {
        const indicator = document.getElementById('sessionStatusIndicator');
        const timeLeft = document.getElementById('sessionTimeLeft');
        
        if (timeRemaining > 0) {
            timeLeft.textContent = timeRemaining;
            indicator.style.display = 'block';
            
            // Change color based on time remaining
            const badge = indicator.querySelector('.badge');
            if (timeRemaining <= 5) {
                badge.className = 'badge bg-danger text-white p-2 rounded-pill';
            } else if (timeRemaining <= 10) {
                badge.className = 'badge bg-warning text-dark p-2 rounded-pill';
            } else {
                badge.className = 'badge bg-info text-white p-2 rounded-pill';
            }
        } else {
            indicator.style.display = 'none';
        }
    }
    
    // Extend session
    document.getElementById('extendSessionBtn').addEventListener('click', function() {
        fetch('/api/session/extend', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                hideTimeoutWarning();
                timeoutWarningShown = false;
                
                // Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.success('Session extended successfully');
                } else {
                    alert('Session extended successfully');
                }
            }
        })
        .catch(error => {
            console.error('Session extension failed:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to extend session');
            } else {
                alert('Failed to extend session');
            }
        });
    });
    
    // Logout now
    document.getElementById('logoutNowBtn').addEventListener('click', function() {
        fetch('/api/session/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '')
            },
            credentials: 'same-origin'
        })
        .then(() => {
            window.location.href = '/login';
        })
        .catch(error => {
            console.error('Logout failed:', error);
            // Force redirect even if API call fails
            window.location.href = '/login';
        });
    });
    
    // Start session monitoring
    sessionCheckInterval = setInterval(checkSessionStatus, 60000); // Check every minute
    
    // Initial check
    checkSessionStatus();
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (sessionCheckInterval) {
            clearInterval(sessionCheckInterval);
        }
    });
});
</script>

<style>
#sessionStatusIndicator {
    transition: all 0.3s ease;
}

#sessionStatusIndicator:hover {
    transform: scale(1.05);
}

.modal-content.border-warning {
    border: 2px solid #ffc107 !important;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}
</style>