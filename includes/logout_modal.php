<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="custom-modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div class="modal-content" style="background-color: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="margin-bottom: 1.5rem; color: #ef5350;">
            <i class="fas fa-sign-out-alt" style="font-size: 3rem;"></i>
        </div>
        <h2 style="margin-bottom: 1rem; color: #0d2137;">Confirm Logout</h2>
        <p style="color: #6c757d; margin-bottom: 2rem;">Are you sure you want to end your session and logout of the system?</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button onclick="closeLogoutModal()" style="padding: 10px 25px; border-radius: 8px; border: 1px solid #ddd; background: white; cursor: pointer; font-weight: 600;">Cancel</button>
            <button id="confirmLogoutBtn" onclick="performLogout()" style="padding: 10px 25px; border-radius: 8px; background: #ef5350; color: white; border: none; cursor: pointer; font-weight: 600;">Logout</button>
        </div>
    </div>
</div>

<script>
let currentLogoutUrl = '';

function showLogoutModal(event, logoutUrl) {
    if (event) event.preventDefault();
    console.log('Opening logout modal. Target URL:', logoutUrl);
    
    currentLogoutUrl = logoutUrl;
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        console.error('Logout modal element not found!');
        // Fallback: If modal fails, just go to the URL
        window.location.href = logoutUrl;
    }
}

function performLogout() {
    console.log('Performing logout redirect to:', currentLogoutUrl);
    if (currentLogoutUrl) {
        window.location.href = currentLogoutUrl;
    } else {
        console.error('No logout URL specified!');
        window.location.href = './logout';
    }
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

// Close modal if clicking outside the content
window.addEventListener('click', function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeLogoutModal();
    }
});
</script>
