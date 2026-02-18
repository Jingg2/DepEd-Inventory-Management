<!-- Edit School Modal -->
<div id="editSchoolModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-school"></i> Edit School Info
            </h2>
            <button class="close-modal" onclick="closeEditSchoolModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editSchoolForm" style="padding: 25px;">
            <input type="hidden" name="action" value="update_school">
            <input type="hidden" name="id" id="edit-school-id">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #475569;">School ID</label>
                <input type="text" name="school_id" id="edit-school-school-id" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #475569;">School Name</label>
                <input type="text" name="school_name" id="edit-school-name" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #475569;">Address</label>
                <textarea name="address" id="edit-school-address" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; min-height: 80px;"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #475569;">Contact No.</label>
                <input type="text" name="contact_no" id="edit-school-contact" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
            </div>
            
            <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeEditSchoolModal()" style="padding: 12px 20px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #64748b; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 12px 24px; border: none; border-radius: 8px; background: #10b981; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSchoolModal() {
    document.getElementById('editSchoolModal').style.display = 'block';
}

function closeEditSchoolModal() {
    document.getElementById('editSchoolModal').style.display = 'none';
}

document.getElementById('editSchoolForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(window.basePath + 'supply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // If name changed, we may need to redirect or refresh
                const currentSchool = new URLSearchParams(window.location.search).get('school');
                if (currentSchool !== data.school_name) {
                    window.location.href = window.basePath + 'controlled_assets/school_items?school=' + encodeURIComponent(data.school_name);
                } else {
                    window.location.reload();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred.'
        });
    });
});
</script>
