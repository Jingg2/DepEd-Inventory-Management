document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById("registrationModal");
    const openBtn = document.getElementById("openModalBtn");
    const closeBtn = document.getElementById("closeModalBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const employeeForm = document.getElementById("employeeForm");
    const tableBody = document.getElementById("employeeTableBody");

    if (openBtn) {
        openBtn.onclick = () => modal.style.display = "block";
    }

    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = "none";
    }

    if (cancelBtn) {
        cancelBtn.onclick = () => modal.style.display = "none";
    }

    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Handle department toggle is now handled inline in employee.php for better reliability

    // Auto-open modal if there's an error message
    const errorMsg = document.querySelector('.error-message');
    if (errorMsg && modal) {
        modal.style.display = "block";
    }

    // AJAX submission removed to simplify flow and avoid raw JSON output
});
